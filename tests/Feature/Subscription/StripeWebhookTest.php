<?php

namespace Tests\Feature\Subscription;

use App\Contracts\StripePaymentIntentApi;
use App\Models\BillingCustomer;
use App\Models\BillingWebhookEvent;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\PaymentIntent;
use Tests\Fakes\FakeStripePaymentIntentApi;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    private FakeStripePaymentIntentApi $stripe;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.stripe.webhook_secret' => 'whsec_test_secret']);

        $this->stripe = new FakeStripePaymentIntentApi();

        $this->app->instance(
            StripePaymentIntentApi::class,
            $this->stripe
        );
    }

    public function test_rejects_invalid_signature(): void
    {
        $this->postJson('/webhooks/stripe', ['id' => 'evt_invalid'], [
            'Stripe-Signature' => 'invalid',
        ])->assertStatus(400);

        $this->assertDatabaseCount('billing_webhook_events', 0);
    }

    public function test_ignores_authenticated_irrelevant_event_idempotently(): void
    {
        $payload = json_encode([
            'id' => 'evt_ignored',
            'object' => 'event',
            'type' => 'customer.created',
            'data' => ['object' => ['id' => 'cus_123', 'object' => 'customer']],
        ], JSON_THROW_ON_ERROR);

        $this->postSignedPayload($payload)->assertOk();
        $this->postSignedPayload($payload)->assertOk();

        $this->assertDatabaseCount('billing_webhook_events', 1);
        $this->assertDatabaseHas('billing_webhook_events', [
            'provider_event_id' => 'evt_ignored',
            'status' => BillingWebhookEvent::STATUS_IGNORED,
        ]);
    }

    public function test_failed_webhook_record_can_be_retried_safely(): void
    {
        BillingWebhookEvent::query()->create([
            'provider' => 'stripe',
            'provider_event_id' => 'evt_retry',
            'event_type' => 'customer.created',
            'status' => BillingWebhookEvent::STATUS_FAILED,
            'failure_message' => 'temporary failure',
            'processed_at' => now()->subMinute(),
        ]);

        $payload = json_encode([
            'id' => 'evt_retry',
            'object' => 'event',
            'type' => 'customer.created',
            'data' => ['object' => ['id' => 'cus_retry', 'object' => 'customer']],
        ], JSON_THROW_ON_ERROR);

        $this->postSignedPayload($payload)->assertOk();

        $this->assertDatabaseCount('billing_webhook_events', 1);
        $this->assertDatabaseHas('billing_webhook_events', [
            'provider_event_id' => 'evt_retry',
            'status' => BillingWebhookEvent::STATUS_IGNORED,
            'failure_message' => null,
        ]);
    }

    public function test_authenticated_manual_payment_success_is_applied_once(): void
    {
        [$tenant, $payment] = $this->manualPaymentScenario();

        $this->stripe->returnRetrievedPaymentIntent(
            $this->successfulManualIntent($tenant, $payment)
        );

        $payload = $this->paymentIntentPayload(
            eventId: 'evt_manual_success',
            eventType: 'payment_intent.succeeded',
            tenant: $tenant,
            payment: $payment,
            paymentMode: 'manual',
            extraIntent: ['status' => 'succeeded'],
        );

        $this->postSignedPayload($payload)->assertOk();
        $this->postSignedPayload($payload)->assertOk();

        $payment->refresh();

        $this->assertTrue($payment->isSucceeded());
        $this->assertNotNull($payment->subscription_id);
        $this->assertSame(1, Subscription::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->count());
        $this->assertDatabaseCount('billing_webhook_events', 1);
        $this->assertDatabaseHas('billing_webhook_events', [
            'provider_event_id' => 'evt_manual_success',
            'tenant_id' => $tenant->id,
            'payment_id' => $payment->id,
            'status' => BillingWebhookEvent::STATUS_PROCESSED,
        ]);
        $this->assertSame(
            $payment->provider_payment_id,
            $this->stripe->receivedPaymentIntentId
        );
    }

    public function test_authenticated_manual_payment_failure_updates_local_state(): void
    {
        [$tenant, $payment] = $this->manualPaymentScenario();

        $payload = $this->paymentIntentPayload(
            eventId: 'evt_manual_failed',
            eventType: 'payment_intent.payment_failed',
            tenant: $tenant,
            payment: $payment,
            paymentMode: 'manual',
            extraIntent: [
                'status' => 'requires_payment_method',
                'last_payment_error' => [
                    'code' => 'card_declined',
                    'message' => 'The card was declined.',
                ],
            ],
        );

        $this->postSignedPayload($payload)->assertOk();
        $this->postSignedPayload($payload)->assertOk();

        $payment->refresh();

        $this->assertTrue($payment->isFailed());
        $this->assertSame('card_declined', $payment->failure_code);
        $this->assertSame('The card was declined.', $payment->failure_message);
        $this->assertDatabaseCount('billing_webhook_events', 1);
        $this->assertDatabaseHas('billing_webhook_events', [
            'provider_event_id' => 'evt_manual_failed',
            'payment_id' => $payment->id,
            'status' => BillingWebhookEvent::STATUS_PROCESSED,
        ]);
    }

    public function test_authenticated_manual_payment_cancellation_updates_local_state(): void
    {
        [$tenant, $payment] = $this->manualPaymentScenario();

        $payload = $this->paymentIntentPayload(
            eventId: 'evt_manual_canceled',
            eventType: 'payment_intent.canceled',
            tenant: $tenant,
            payment: $payment,
            paymentMode: 'manual',
            extraIntent: ['status' => 'canceled'],
        );

        $this->postSignedPayload($payload)->assertOk();
        $this->postSignedPayload($payload)->assertOk();

        $payment->refresh();

        $this->assertTrue($payment->isCanceled());
        $this->assertNotNull($payment->canceled_at);
        $this->assertDatabaseCount('billing_webhook_events', 1);
        $this->assertDatabaseHas('billing_webhook_events', [
            'provider_event_id' => 'evt_manual_canceled',
            'payment_id' => $payment->id,
            'status' => BillingWebhookEvent::STATUS_PROCESSED,
        ]);
    }

    public function test_webhook_rejects_payment_identity_from_another_tenant(): void
    {
        [$tenant, $payment] = $this->manualPaymentScenario();

        $otherTenant = Tenant::create([
            'name' => 'Consultorio Ajeno',
            'slug' => 'webhook-other-'.uniqid(),
            'status' => 'trial',
        ]);

        $payload = $this->paymentIntentPayload(
            eventId: 'evt_wrong_tenant',
            eventType: 'payment_intent.payment_failed',
            tenant: $tenant,
            payment: $payment,
            paymentMode: 'manual',
            extraIntent: [
                'status' => 'requires_payment_method',
                'metadata' => [
                    'doctotal_payment_uuid' => $payment->uuid,
                    'doctotal_tenant_id' => (string) $otherTenant->id,
                    'billing_cycle' => $payment->billing_cycle,
                    'payment_mode' => 'manual',
                ],
            ],
        );

        $this->postSignedPayload($payload)->assertStatus(500);

        $this->assertTrue($payment->refresh()->isPending());
        $this->assertDatabaseHas('billing_webhook_events', [
            'provider_event_id' => 'evt_wrong_tenant',
            'status' => BillingWebhookEvent::STATUS_FAILED,
        ]);
    }

    private function manualPaymentScenario(): array
    {
        $tenant = Tenant::create([
            'name' => 'Consultorio Webhook',
            'slug' => 'webhook-'.uniqid(),
            'status' => 'trial',
            'onboarding_completed_at' => now(),
        ]);

        BillingCustomer::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'provider' => BillingCustomer::PROVIDER_STRIPE,
            'provider_customer_id' => 'cus_webhook_123',
        ]);

        $payment = Payment::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'subscription_id' => null,
            'billing_cycle' => Subscription::BILLING_CYCLE_MONTHLY,
            'amount' => 60000,
            'currency' => 'MXN',
            'status' => Payment::STATUS_PENDING,
            'attempted_at' => now(),
            'provider' => 'stripe',
            'provider_payment_id' => 'pi_webhook_manual',
            'idempotency_key' => 'webhook-manual-'.uniqid(),
        ]);

        return [$tenant, $payment];
    }

    private function successfulManualIntent(Tenant $tenant, Payment $payment): PaymentIntent
    {
        return PaymentIntent::constructFrom([
            'id' => $payment->provider_payment_id,
            'amount' => $payment->amount,
            'currency' => strtolower($payment->currency),
            'customer' => 'cus_webhook_123',
            'status' => 'succeeded',
            'metadata' => [
                'doctotal_payment_uuid' => $payment->uuid,
                'doctotal_tenant_id' => (string) $tenant->id,
                'billing_cycle' => $payment->billing_cycle,
                'payment_mode' => 'manual',
            ],
        ]);
    }

    private function paymentIntentPayload(
        string $eventId,
        string $eventType,
        Tenant $tenant,
        Payment $payment,
        string $paymentMode,
        array $extraIntent = [],
    ): string {
        $intent = array_replace_recursive([
            'id' => $payment->provider_payment_id,
            'object' => 'payment_intent',
            'amount' => $payment->amount,
            'currency' => strtolower($payment->currency),
            'customer' => 'cus_webhook_123',
            'metadata' => [
                'doctotal_payment_uuid' => $payment->uuid,
                'doctotal_tenant_id' => (string) $tenant->id,
                'billing_cycle' => $payment->billing_cycle,
                'payment_mode' => $paymentMode,
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
        return $this->call('POST', '/webhooks/stripe', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $this->stripeSignature(
                $payload,
                'whsec_test_secret'
            ),
            'CONTENT_TYPE' => 'application/json',
        ], $payload);
    }

    private function stripeSignature(string $payload, string $secret): string
    {
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        return 't='.$timestamp.',v1='.$signature;
    }
}
