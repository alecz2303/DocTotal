<?php

namespace Tests\Feature\Billing;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\Billing\TenantCommercialStatusPresenter;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TenantCommercialStatusPresenterTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_active_trial_exposes_remaining_days_and_billing_action(): void
    {
        Carbon::setTestNow('2026-09-06 10:00:00');

        $tenant = $this->createTenant([
            'status' => 'trial',
            'trial_started_at' => now()->subDays(10),
            'trial_ends_at' => now()->addDays(5),
        ]);

        $notice = app(TenantCommercialStatusPresenter::class)
            ->noticeFor($tenant);

        $this->assertSame('info', $notice['type']);
        $this->assertSame('Periodo de prueba', $notice['title']);
        $this->assertStringContainsString('5 días', $notice['message']);
        $this->assertStringContainsString('11/09/2026', $notice['message']);
        $this->assertSame('settings.billing', $notice['action_route']);
    }

    public function test_trial_near_expiration_is_presented_as_warning(): void
    {
        Carbon::setTestNow('2026-09-06 10:00:00');

        $tenant = $this->createTenant([
            'status' => 'trial',
            'trial_started_at' => now()->subDays(10),
            'trial_ends_at' => now()->addDays(2),
        ]);

        $notice = app(TenantCommercialStatusPresenter::class)
            ->noticeFor($tenant);

        $this->assertSame('warning', $notice['type']);
        $this->assertSame('Periodo de prueba', $notice['title']);
        $this->assertStringContainsString('2 días', $notice['message']);
    }

    public function test_active_subscription_does_not_generate_commercial_notice(): void
    {
        Carbon::setTestNow('2026-09-06 10:00:00');

        $tenant = $this->createTenant([
            'status' => 'active',
        ]);

        $this->createSubscription($tenant, [
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $notice = app(TenantCommercialStatusPresenter::class)
            ->noticeFor($tenant);

        $this->assertNull($notice);
    }

    public function test_past_due_subscription_uses_existing_grace_period_date(): void
    {
        Carbon::setTestNow('2026-09-06 10:00:00');

        $tenant = $this->createTenant([
            'status' => 'active',
        ]);

        $this->createSubscription($tenant, [
            'status' => Subscription::STATUS_PAST_DUE,
            'past_due_since' => now()->subDay(),
            'grace_ends_at' => now()->addDays(6),
        ]);

        $notice = app(TenantCommercialStatusPresenter::class)
            ->noticeFor($tenant);

        $this->assertSame('warning', $notice['type']);
        $this->assertSame('Pago pendiente', $notice['title']);
        $this->assertStringContainsString('12/09/2026', $notice['message']);
        $this->assertSame('Resolver pago', $notice['action_label']);
    }

    public function test_latest_failed_payment_is_exposed_when_no_current_subscription_exists(): void
    {
        Carbon::setTestNow('2026-09-06 10:00:00');

        $tenant = $this->createTenant([
            'status' => 'active',
        ]);

        $this->createPayment($tenant, Payment::STATUS_FAILED);

        $notice = app(TenantCommercialStatusPresenter::class)
            ->noticeFor($tenant);

        $this->assertSame('danger', $notice['type']);
        $this->assertSame('No pudimos procesar tu último pago', $notice['title']);
        $this->assertSame('Revisar pago', $notice['action_label']);
    }

    public function test_latest_pending_payment_is_exposed_when_no_current_subscription_exists(): void
    {
        Carbon::setTestNow('2026-09-06 10:00:00');

        $tenant = $this->createTenant([
            'status' => 'active',
        ]);

        $this->createPayment($tenant, Payment::STATUS_PENDING);

        $notice = app(TenantCommercialStatusPresenter::class)
            ->noticeFor($tenant);

        $this->assertSame('warning', $notice['type']);
        $this->assertSame('Tienes un pago pendiente', $notice['title']);
        $this->assertSame('Continuar en facturación', $notice['action_label']);
    }

    private function createTenant(array $attributes = []): Tenant
    {
        $tenant = Tenant::create(array_merge([
            'name' => 'Consultorio Comercial Test',
            'slug' => 'consultorio-comercial-' . uniqid(),
            'status' => 'active',
            'onboarding_completed_at' => now(),
        ], $attributes));

        app(TenantContext::class)->set($tenant);

        return $tenant;
    }

    private function createSubscription(
        Tenant $tenant,
        array $attributes = []
    ): Subscription {
        app(TenantContext::class)->set($tenant);

        $startsAt = now()->subDay();
        $periodEndsAt = now()->addMonth();

        return Subscription::create(array_merge([
            'billing_cycle' => Subscription::BILLING_CYCLE_MONTHLY,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => $startsAt,
            'current_period_starts_at' => $startsAt,
            'current_period_ends_at' => $periodEndsAt,
            'next_billing_at' => $periodEndsAt,
            'past_due_since' => null,
            'grace_ends_at' => null,
            'next_retry_at' => null,
            'retry_count' => 0,
            'cancel_at_period_end' => false,
            'cancelled_at' => null,
        ], $attributes));
    }

    private function createPayment(Tenant $tenant, string $status): Payment
    {
        app(TenantContext::class)->set($tenant);

        return Payment::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => null,
            'billing_cycle' => Subscription::BILLING_CYCLE_MONTHLY,
            'amount' => 60000,
            'currency' => 'MXN',
            'status' => $status,
            'attempted_at' => now(),
            'failed_at' => $status === Payment::STATUS_FAILED ? now() : null,
            'idempotency_key' => 'commercial-status-' . uniqid(),
        ]);
    }
}
