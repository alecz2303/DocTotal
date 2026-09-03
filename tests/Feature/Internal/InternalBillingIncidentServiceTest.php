<?php

namespace Tests\Feature\Internal;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\Internal\InternalBillingIncidentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalBillingIncidentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_reads_billing_incidents_across_tenants_without_tenant_context(): void
    {
        $firstTenant = $this->createTenant('Alpha', 'alpha');
        $secondTenant = $this->createTenant('Beta', 'beta');

        $this->createFailedPayment($firstTenant, 'alpha-payment');
        $this->createFailedPayment($secondTenant, 'beta-payment');

        $this->createPastDueSubscription($firstTenant, now()->addDays(2));
        $this->createPastDueSubscription($secondTenant, now()->subDay());

        $summary = app(InternalBillingIncidentService::class)->summary();

        $this->assertSame(2, $summary['failed_payments']);
        $this->assertSame(2, $summary['past_due_subscriptions']);
        $this->assertSame(1, $summary['past_due_in_grace']);
        $this->assertSame(1, $summary['past_due_grace_expired']);
    }

    public function test_failed_payments_returns_only_failed_payments_across_tenants(): void
    {
        $firstTenant = $this->createTenant('Alpha', 'alpha');
        $secondTenant = $this->createTenant('Beta', 'beta');

        $this->createFailedPayment($firstTenant, 'alpha-failed');
        $this->createFailedPayment($secondTenant, 'beta-failed');

        Payment::query()
            ->withoutGlobalScopes()
            ->create([
                'tenant_id' => $firstTenant->id,
                'subscription_id' => null,
                'amount' => 10000,
                'currency' => 'MXN',
                'status' => Payment::STATUS_SUCCEEDED,
                'attempted_at' => now(),
                'paid_at' => now(),
                'provider' => 'test',
                'idempotency_key' => 'alpha-succeeded',
            ]);

        $payments = app(InternalBillingIncidentService::class)->failedPayments();

        $this->assertSame(2, $payments->total());
        $this->assertTrue(
            $payments->getCollection()->every(
                fn (Payment $payment): bool => $payment->status === Payment::STATUS_FAILED
            )
        );

        $this->assertEqualsCanonicalizing(
            [$firstTenant->id, $secondTenant->id],
            $payments->getCollection()->pluck('tenant_id')->unique()->values()->all()
        );
    }

    public function test_past_due_subscriptions_returns_only_past_due_across_tenants(): void
    {
        $firstTenant = $this->createTenant('Alpha', 'alpha');
        $secondTenant = $this->createTenant('Beta', 'beta');

        $this->createPastDueSubscription($firstTenant, now()->addDays(2));
        $this->createPastDueSubscription($secondTenant, now()->subDay());

        Subscription::query()
            ->withoutGlobalScopes()
            ->create([
                'tenant_id' => $firstTenant->id,
                'billing_cycle' => Subscription::BILLING_CYCLE_MONTHLY,
                'status' => Subscription::STATUS_ACTIVE,
                'starts_at' => now()->subMonth(),
                'current_period_starts_at' => now()->subDay(),
                'current_period_ends_at' => now()->addMonth(),
                'next_billing_at' => now()->addMonth(),
                'billing_amount' => 10000,
                'billing_currency' => 'MXN',
            ]);

        $subscriptions = app(InternalBillingIncidentService::class)->pastDueSubscriptions();

        $this->assertSame(2, $subscriptions->total());
        $this->assertTrue(
            $subscriptions->getCollection()->every(
                fn (Subscription $subscription): bool => $subscription->status === Subscription::STATUS_PAST_DUE
            )
        );

        $this->assertEqualsCanonicalizing(
            [$firstTenant->id, $secondTenant->id],
            $subscriptions->getCollection()->pluck('tenant_id')->unique()->values()->all()
        );
    }

    private function createTenant(string $name, string $slug): Tenant
    {
        return Tenant::query()->create([
            'name' => $name,
            'slug' => $slug,
            'status' => 'trial',
        ]);
    }

    private function createFailedPayment(Tenant $tenant, string $idempotencyKey): Payment
    {
        return Payment::query()
            ->withoutGlobalScopes()
            ->create([
                'tenant_id' => $tenant->id,
                'subscription_id' => null,
                'amount' => 12500,
                'currency' => 'MXN',
                'status' => Payment::STATUS_FAILED,
                'attempted_at' => now()->subHour(),
                'failed_at' => now()->subHour(),
                'failure_code' => 'card_declined',
                'failure_message' => 'Pago rechazado de prueba.',
                'provider' => 'test',
                'idempotency_key' => $idempotencyKey,
            ]);
    }

    private function createPastDueSubscription(Tenant $tenant, $graceEndsAt): Subscription
    {
        return Subscription::query()
            ->withoutGlobalScopes()
            ->create([
                'tenant_id' => $tenant->id,
                'billing_cycle' => Subscription::BILLING_CYCLE_MONTHLY,
                'status' => Subscription::STATUS_PAST_DUE,
                'starts_at' => now()->subMonths(2),
                'current_period_starts_at' => now()->subMonth(),
                'current_period_ends_at' => now()->subDay(),
                'next_billing_at' => now()->subDay(),
                'past_due_since' => now()->subDays(2),
                'grace_ends_at' => $graceEndsAt,
                'next_retry_at' => now()->addHour(),
                'retry_count' => 1,
                'billing_amount' => 12500,
                'billing_currency' => 'MXN',
                'pending_billing_cycle' => null,
            ]);
    }
}
