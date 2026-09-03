<?php

namespace Tests\Unit\Models;

use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TenantServiceAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_trial_has_service_access(): void
    {
        Carbon::setTestNow('2026-09-02 12:00:00');

        $tenant = $this->createTenant([
            'status' => 'trial',
            'trial_started_at' => now()->subDays(2),
            'trial_ends_at' => now()->addDay(),
        ]);

        $this->assertTrue($tenant->hasAccessToService());
        $this->assertSame(
            'trial_active',
            $tenant->effectiveServiceStatus()
        );

        Carbon::setTestNow();
    }

    public function test_expired_trial_without_subscription_has_no_access(): void
    {
        Carbon::setTestNow('2026-09-02 12:00:00');

        $tenant = $this->createTenant([
            'status' => 'trial',
            'trial_started_at' => now()->subDays(4),
            'trial_ends_at' => now()->subDay(),
        ]);

        $this->assertFalse($tenant->hasAccessToService());
        $this->assertSame(
            'trial_expired',
            $tenant->effectiveServiceStatus()
        );

        Carbon::setTestNow();
    }

    public function test_active_subscription_grants_access_even_with_old_trial_dates(): void
    {
        Carbon::setTestNow('2026-09-02 12:00:00');

        $tenant = $this->createTenant([
            'status' => 'active',
            'trial_started_at' => now()->subMonth(),
            'trial_ends_at' => now()->subWeeks(3),
        ]);

        $this->createSubscription(
            $tenant,
            Subscription::STATUS_ACTIVE
        );

        $this->assertTrue($tenant->hasAccessToService());
        $this->assertSame(
            'active',
            $tenant->effectiveServiceStatus()
        );

        Carbon::setTestNow();
    }

    public function test_past_due_subscription_in_grace_period_grants_access(): void
    {
        Carbon::setTestNow('2026-09-02 12:00:00');

        $tenant = $this->createTenant([
            'status' => 'active',
        ]);

        $this->createSubscription(
            $tenant,
            Subscription::STATUS_PAST_DUE,
            [
                'grace_ends_at' => now()->addDays(2),
            ]
        );

        $this->assertTrue($tenant->hasAccessToService());
        $this->assertSame(
            'grace_period',
            $tenant->effectiveServiceStatus()
        );

        Carbon::setTestNow();
    }

    public function test_expired_grace_period_has_no_access(): void
    {
        Carbon::setTestNow('2026-09-02 12:00:00');

        $tenant = $this->createTenant([
            'status' => 'active',
        ]);

        $this->createSubscription(
            $tenant,
            Subscription::STATUS_PAST_DUE,
            [
                'grace_ends_at' => now()->subMinute(),
            ]
        );

        $this->assertFalse($tenant->hasAccessToService());
        $this->assertSame(
            'no_access',
            $tenant->effectiveServiceStatus()
        );

        Carbon::setTestNow();
    }

    public function test_suspended_tenant_never_has_access_even_with_current_subscription(): void
    {
        Carbon::setTestNow('2026-09-02 12:00:00');

        $tenant = $this->createTenant([
            'status' => 'suspended',
            'suspended_at' => now(),
        ]);

        $this->createSubscription(
            $tenant,
            Subscription::STATUS_ACTIVE
        );

        $this->assertFalse($tenant->hasAccessToService());
        $this->assertSame(
            'suspended',
            $tenant->effectiveServiceStatus()
        );

        Carbon::setTestNow();
    }

    private function createTenant(array $overrides = []): Tenant
    {
        return Tenant::query()->create(array_merge([
            'name' => 'Tenant Test',
            'slug' => 'tenant-test-'.uniqid(),
            'status' => 'trial',
        ], $overrides));
    }

    private function createSubscription(
        Tenant $tenant,
        string $status,
        array $overrides = []
    ): Subscription {
        return Subscription::query()
            ->withoutGlobalScopes()
            ->create(array_merge([
                'tenant_id' => $tenant->id,
                'billing_cycle' =>
                    Subscription::BILLING_CYCLE_MONTHLY,
                'status' => $status,
                'starts_at' => now()->subMonth(),
                'current_period_starts_at' => now()->subDay(),
                'current_period_ends_at' => now()->addMonth(),
                'billing_amount' => 10000,
                'billing_currency' => 'MXN',
            ], $overrides));
    }
}
