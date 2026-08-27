<?php

namespace Tests\Feature\Subscription;

use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TenantSubscriptionAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_tenant_with_active_trial_has_access(): void
    {
        Carbon::setTestNow(
            '2026-08-26 10:30:00'
        );

        $tenant = $this->createTenant([
            'status' => 'trial',
            'trial_started_at' =>
            now()->subDays(5),
            'trial_ends_at' =>
            now()->addDays(5),
        ]);

        $this->assertTrue(
            $tenant->hasAccessToService()
        );
    }

    public function test_tenant_with_expired_trial_and_no_subscription_has_no_access(): void
    {
        Carbon::setTestNow(
            '2026-08-26 10:30:00'
        );

        $tenant = $this->createTenant([
            'status' => 'trial',
            'trial_started_at' =>
            now()->subDays(10),
            'trial_ends_at' =>
            now()->subSecond(),
        ]);

        $this->assertFalse(
            $tenant->hasAccessToService()
        );
    }

    public function test_tenant_with_current_active_subscription_has_access(): void
    {
        Carbon::setTestNow(
            '2026-08-26 10:30:00'
        );

        $tenant = $this->createTenant([
            'status' => 'active',
            'trial_started_at' =>
            now()->subMonth(),
            'trial_ends_at' =>
            now()->subDays(15),
        ]);

        $this->createSubscription(
            $tenant,
            periodEndsAt: now()->addMonth()
        );

        $this->assertTrue(
            $tenant->hasCurrentSubscription()
        );

        $this->assertTrue(
            $tenant->hasAccessToService()
        );
    }

    public function test_expired_subscription_does_not_grant_access(): void
    {
        Carbon::setTestNow(
            '2026-08-26 10:30:00'
        );

        $tenant = $this->createTenant([
            'status' => 'active',
            'trial_started_at' =>
            now()->subMonths(2),
            'trial_ends_at' =>
            now()->subMonth(),
        ]);

        $this->createSubscription(
            $tenant,
            periodStartsAt: now()->subMonth(),
            periodEndsAt: now()->subSecond()
        );

        $this->assertFalse(
            $tenant->hasCurrentSubscription()
        );

        $this->assertFalse(
            $tenant->hasAccessToService()
        );
    }

    public function test_past_due_subscription_can_still_grant_access_while_tenant_is_not_suspended(): void
    {
        Carbon::setTestNow(
            '2026-08-26 10:30:00'
        );

        $tenant = $this->createTenant([
            'status' => 'active',
            'trial_started_at' =>
            now()->subMonths(2),
            'trial_ends_at' =>
            now()->subMonth(),
        ]);

        $this->createSubscription(
            $tenant,
            status: Subscription::STATUS_PAST_DUE,
            periodStartsAt: now()->subMonth(),
            periodEndsAt: now()->subDay(),
            pastDueSince: now()->subDay(),
            graceEndsAt: now()->addDays(6),
            nextRetryAt: now()->addDay(),
            retryCount: 0,
        );

        $this->assertTrue(
            $tenant->hasCurrentSubscription()
        );

        $this->assertTrue(
            $tenant->hasAccessToService()
        );
    }

    public function test_past_due_subscription_does_not_grant_access_when_tenant_is_suspended(): void
    {
        Carbon::setTestNow(
            '2026-08-26 10:30:00'
        );

        $tenant = $this->createTenant([
            'status' => 'suspended',
            'trial_started_at' =>
            now()->subMonths(2),
            'trial_ends_at' =>
            now()->subMonth(),
            'suspended_at' =>
            now(),
        ]);

        $this->createSubscription(
            $tenant,
            status: Subscription::STATUS_PAST_DUE,
            periodStartsAt: now()->subMonth(),
            periodEndsAt: now()->subDay(),
            pastDueSince: now()->subDay(),
            graceEndsAt: now()->addDays(6),
            nextRetryAt: now()->addDay(),
            retryCount: 0,
        );

        $this->assertTrue(
            $tenant->hasCurrentSubscription()
        );

        $this->assertFalse(
            $tenant->hasAccessToService()
        );
    }

    public function test_cancelled_subscription_does_not_grant_access(): void
    {
        Carbon::setTestNow(
            '2026-08-26 10:30:00'
        );

        $tenant = $this->createTenant([
            'status' => 'active',
            'trial_started_at' =>
            now()->subMonths(2),
            'trial_ends_at' =>
            now()->subMonth(),
        ]);

        $this->createSubscription(
            $tenant,
            status: Subscription::STATUS_CANCELLED,
            periodEndsAt: now()->addDays(5)
        );

        $this->assertFalse(
            $tenant->hasCurrentSubscription()
        );

        $this->assertFalse(
            $tenant->hasAccessToService()
        );
    }

    public function test_suspended_tenant_has_no_access_even_with_current_subscription(): void
    {
        Carbon::setTestNow(
            '2026-08-26 10:30:00'
        );

        $tenant = $this->createTenant([
            'status' => 'suspended',
            'trial_started_at' =>
            now()->subMonth(),
            'trial_ends_at' =>
            now()->subDays(10),
            'suspended_at' =>
            now(),
        ]);

        $this->createSubscription(
            $tenant,
            periodEndsAt: now()->addMonth()
        );

        $this->assertTrue(
            $tenant->hasCurrentSubscription()
        );

        $this->assertFalse(
            $tenant->hasAccessToService()
        );
    }

    public function test_cancelled_tenant_has_no_access_even_with_current_subscription(): void
    {
        Carbon::setTestNow(
            '2026-08-26 10:30:00'
        );

        $tenant = $this->createTenant([
            'status' => 'cancelled',
            'trial_started_at' =>
            now()->subMonth(),
            'trial_ends_at' =>
            now()->subDays(10),
        ]);

        $this->createSubscription(
            $tenant,
            periodEndsAt: now()->addMonth()
        );

        $this->assertTrue(
            $tenant->hasCurrentSubscription()
        );

        $this->assertFalse(
            $tenant->hasAccessToService()
        );
    }

    public function test_current_subscription_returns_the_active_current_subscription(): void
    {
        Carbon::setTestNow(
            '2026-08-26 10:30:00'
        );

        $tenant = $this->createTenant([
            'status' => 'active',
        ]);

        $subscription =
            $this->createSubscription(
                $tenant,
                periodEndsAt: now()->addMonth()
            );

        $current =
            $tenant->currentSubscription();

        $this->assertNotNull(
            $current
        );

        $this->assertTrue(
            $current->is($subscription)
        );
    }

    public function test_subscription_expiring_at_exact_current_instant_is_not_current(): void
    {
        Carbon::setTestNow(
            '2026-08-26 10:30:00'
        );

        $tenant = $this->createTenant([
            'status' => 'active',
            'trial_started_at' =>
            now()->subMonth(),
            'trial_ends_at' =>
            now()->subDays(10),
        ]);

        $this->createSubscription(
            $tenant,
            periodStartsAt: now()->subMonth(),
            periodEndsAt: now()
        );

        $this->assertFalse(
            $tenant->hasCurrentSubscription()
        );

        $this->assertFalse(
            $tenant->hasAccessToService()
        );
    }

    private function createTenant(
        array $attributes = []
    ): Tenant {
        return Tenant::create(
            array_merge([
                'name' =>
                'Consultorio Test',

                'slug' =>
                'consultorio-test',

                'onboarding_completed_at' =>
                now(),
            ], $attributes)
        );
    }

    private function createSubscription(
        Tenant $tenant,
        string $billingCycle =
        Subscription::BILLING_CYCLE_MONTHLY,
        string $status =
        Subscription::STATUS_ACTIVE,
        mixed $periodStartsAt = null,
        mixed $periodEndsAt = null,
        mixed $pastDueSince = null,
        mixed $graceEndsAt = null,
        mixed $nextRetryAt = null,
        int $retryCount = 0,
    ): Subscription {
        app(TenantContext::class)->set(
            $tenant
        );

        $periodStartsAt ??=
            now();

        $periodEndsAt ??=
            $billingCycle ===
            Subscription::BILLING_CYCLE_YEARLY
            ? now()->addYear()
            : now()->addMonth();

        return Subscription::create([
            'billing_cycle' =>
            $billingCycle,

            'status' =>
            $status,

            'starts_at' =>
            $periodStartsAt,

            'current_period_starts_at' =>
            $periodStartsAt,

            'current_period_ends_at' =>
            $periodEndsAt,

            'next_billing_at' =>
            $status ===
                Subscription::STATUS_CANCELLED
                ? null
                : $periodEndsAt,

            'past_due_since' =>
            $pastDueSince,

            'grace_ends_at' =>
            $graceEndsAt,

            'next_retry_at' =>
            $nextRetryAt,

            'retry_count' =>
            $retryCount,

            'cancel_at_period_end' =>
            false,

            'cancelled_at' =>
            $status ===
                Subscription::STATUS_CANCELLED
                ? now()
                : null,
        ]);
    }
}
