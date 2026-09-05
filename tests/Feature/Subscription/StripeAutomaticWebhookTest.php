<?php

namespace Tests\Feature\Subscription;

use App\Models\BillingCustomer;
use App\Models\BillingWebhookEvent;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeAutomaticWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.stripe.webhook_secret' => 'whsec_test_secret',
            'billing.grace_period_days' => 7,
            'billing.retry_schedule_hours' => [24, 72],
        ]);
    }

    public function test_automatic_success_recovers_pending_local_payment_after_provider_success(): void
    {
        [$tenant, $subscription, $payment] = $this->automaticPaymentScenario();
        $previousPeriodEnd = $subscription->current_period_ends_at->copy();

        $payload = $this->payload(
            'evt_auto_success',
            'payment_intent.succeeded',
            $tenant,
            $subscription,
            $payment,
            ['status' => 'succeeded'],
        );

        $this->postSignedPayload($payload)->assertOk();
        $this->postSignedPayload($payload)->assertOk();

        $payment->refresh();
        $subscription->refresh();

        $this->assertTrue($payment->isSucceeded());
        $this->assertTrue($subscription->isActive());
        $this->assertTrue(
            $subscription->current_period_starts_at->equalTo($previousPeriodEnd)
        );
        $this->assertDatabaseCount('billing_webhook_events', 1);
        $this->assertDatabaseHas('billing_webhook_events', [
            'provider_event_id' => 'evt_auto_success',
            'payment_id' => $payment->id,
            'status' => BillingWebhookEvent::STATUS_PROCESSED,
        ]);
    }

    public function test_automatic_failure_starts_recovery_once(): void
    {
        [$tenant, $subscription, $payment] = $this->automaticPaymentScenario();

        $payload = $this->payload(
            'evt_auto_failed',
            'payment_intent.payment_failed',
            $tenant,
            $subscription,
            $payment,
            [
                'status' => 'requires_payment_method',
                'last_payment_error' => [
                    'code' => 'card_declined',
                    'message' => 'Automatic charge declined.',
                ],
            ],
        );

        $this->postSignedPayload($payload)->assertOk();
        $this->postSignedPayload($payload)->assertOk();

        $payment->refresh();
        $subscription->refresh();

        $this->assertTrue($payment->isFailed());
        $this->assertTrue($subscription->isPastDue());
        $this->assertNotNull($subscription->past_due_since);
        $this->assertNotNull($subscription->grace_ends_at);
        $this->assertNotNull($subscription->next_retry_at);
        $this->assertSame(0, $subscription->retry_count);
        $this->assertDatabaseCount('billing_webhook_events', 1);
    }

    private function automaticPaymentScenario(): array
    {
        $tenant = Tenant::create([
            'name' => 'Consultorio Cobro Automático',
            'slug' => 'auto-webhook-'.uniqid(),
            'status' => 'active',
            'onboarding_completed_at' => now(),
        ]);

        BillingCustomer::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'provider' => BillingCustomer::PROVIDER_STRIPE,
            'provider_customer_id' => 'cus_auto_webhook',
        ]);

        $startsAt = now()->subMonth()->startOfDay();
        $periodEnd = now()->addDay()->startOfDay();

        $subscription = Subscription::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::BILLING_CYCLE_MONTHLY,
            'billing_amount' => 60000,
            'billing_currency' => 'MXN',
            'starts_at' => $startsAt,
            'current_period_starts_at' => $startsAt,
            'current_period_ends_at' => $periodEnd,
            'next_billing_at' => $periodEnd,
            'cancel_at_period_end' => false,
            'retry_count' => 0,
        ]);

        $payment = Payment::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'billing_cycle' => $subscription->billing_cycle,
            'gross_amount' => 60000,
            'referral_discount_amount' => 0,
            'promotional_credit_amount' => 0,
            'amount' => 60000,
            'currency' => 'MXN',
            'status' => Payment::STATUS_PENDING,
            'attempted_at' => now(),
            'provider' => 'stripe',
            'provider_payment_id' => 'pi_auto_webhook',
            'idempotency_key' => 'auto-webhook-'.uniqid(),
        ]);

        return [$tenant, $subscription, $payment];
    }

    private function payload(
        string $eventId,
        string $eventType,
        Tenant $tenant,
        Subscription $subscription,
        Payment $payment,
        array $extraIntent,
    ): string {
        $intent = array_replace_recursive([
            'id' => $payment->provider_payment_id,
            'object' => 'payment_intent',
            'amount' => $payment->amount,
            'currency' => strtolower($payment->currency),
            'customer' => 'cus_auto_webhook',
            'metadata' => [
                'doctotal_payment_uuid' => $payment->uuid,
                'tenant_id' => (string) $tenant->id,
                'subscription_id' => (string) $subscription->id,
            ],
        ], $extraIntent);

        return json_encode([
            'id' => $eventId,
            'object' => 'event',
            'type' => $eventType,
            'data' => ['object' => $intent],
        ], JSON_THROW_ON_ERROR);
    }

    private function postSignedPayload(string $payload)
    {
        $timestamp = time();
        $signature = hash_hmac(
            'sha256',
            $timestamp.'.'.$payload,
            'whsec_test_secret'
        );

        return $this->call('POST', '/webhooks/stripe', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => 't='.$timestamp.',v1='.$signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);
    }
}
