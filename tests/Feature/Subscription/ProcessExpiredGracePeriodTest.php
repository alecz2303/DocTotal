<?php

namespace Tests\Feature\Subscription;

use App\Actions\Billing\ProcessExpiredGracePeriod;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use LogicException;
use Tests\TestCase;

class ProcessExpiredGracePeriodTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_tenant_is_not_suspended_before_grace_period_ends(): void
    {
        Carbon::setTestNow(
            '2026-10-03 16:37:21'
        );

        [$tenant, $subscription] =
            $this->createPastDueSubscription();

        app(
            ProcessExpiredGracePeriod::class
        )->execute($subscription);

        $tenant->refresh();

        $this->assertSame(
            'active',
            $tenant->status
        );

        $this->assertNull(
            $tenant->suspended_at
        );
    }

    public function test_tenant_is_suspended_at_exact_grace_period_end(): void
    {
        Carbon::setTestNow(
            '2026-10-03 16:37:22'
        );

        [$tenant, $subscription] =
            $this->createPastDueSubscription();

        app(
            ProcessExpiredGracePeriod::class
        )->execute($subscription);

        $tenant->refresh();

        $this->assertSame(
            'suspended',
            $tenant->status
        );

        $this->assertNotNull(
            $tenant->suspended_at
        );

        $this->assertTrue(
            $tenant
                ->suspended_at
                ->equalTo(now())
        );
    }

    public function test_subscription_remains_past_due_after_tenant_is_suspended(): void
    {
        Carbon::setTestNow(
            '2026-10-03 16:37:22'
        );

        [, $subscription] =
            $this->createPastDueSubscription();

        app(
            ProcessExpiredGracePeriod::class
        )->execute($subscription);

        $subscription->refresh();

        $this->assertSame(
            Subscription::STATUS_PAST_DUE,
            $subscription->status
        );
    }

    public function test_next_retry_is_cleared_when_grace_period_expires(): void
    {
        Carbon::setTestNow(
            '2026-10-03 16:37:22'
        );

        [, $subscription] =
            $this->createPastDueSubscription();

        app(
            ProcessExpiredGracePeriod::class
        )->execute($subscription);

        $subscription->refresh();

        $this->assertNull(
            $subscription->next_retry_at
        );
    }

    public function test_suspended_tenant_has_no_service_access(): void
    {
        Carbon::setTestNow(
            '2026-10-03 16:37:21'
        );

        [$tenant, $subscription] =
            $this->createPastDueSubscription();

        $this->assertTrue(
            $tenant->hasAccessToService()
        );

        Carbon::setTestNow(
            '2026-10-03 16:37:22'
        );

        app(
            ProcessExpiredGracePeriod::class
        )->execute($subscription);

        $tenant->refresh();

        $this->assertSame(
            'suspended',
            $tenant->status
        );

        $this->assertFalse(
            $tenant->hasAccessToService()
        );
    }

    public function test_processing_already_suspended_tenant_is_idempotent(): void
    {
        Carbon::setTestNow(
            '2026-10-04 09:00:00'
        );

        [$tenant, $subscription] =
            $this->createPastDueSubscription(
                tenantStatus: 'suspended'
            );

        $originalSuspendedAt =
            Carbon::parse(
                '2026-10-03 16:37:22'
            );

        $tenant->update([
            'suspended_at' =>
            $originalSuspendedAt,
        ]);

        app(
            ProcessExpiredGracePeriod::class
        )->execute($subscription);

        $tenant->refresh();

        $this->assertSame(
            'suspended',
            $tenant->status
        );

        $this->assertTrue(
            $tenant
                ->suspended_at
                ->equalTo(
                    $originalSuspendedAt
                )
        );
    }

    public function test_active_subscription_is_ignored(): void
    {
        Carbon::setTestNow(
            '2026-10-04 09:00:00'
        );

        [$tenant, $subscription] =
            $this->createPastDueSubscription();

        $subscription->update([
            'status' =>
            Subscription::STATUS_ACTIVE,
        ]);

        app(
            ProcessExpiredGracePeriod::class
        )->execute($subscription);

        $tenant->refresh();

        $this->assertSame(
            'active',
            $tenant->status
        );
    }

    public function test_past_due_subscription_without_grace_end_is_rejected(): void
    {
        Carbon::setTestNow(
            '2026-10-04 09:00:00'
        );

        [, $subscription] =
            $this->createPastDueSubscription();

        $subscription->update([
            'grace_ends_at' =>
            null,
        ]);

        $this->expectException(
            LogicException::class
        );

        app(
            ProcessExpiredGracePeriod::class
        )->execute($subscription);
    }

    private function createPastDueSubscription(
        string $tenantStatus = 'active',
    ): array {
        $tenant = Tenant::create([
            'name' =>
            'Consultorio Grace',

            'slug' =>
            'consultorio-grace-' . uniqid(),

            'status' =>
            $tenantStatus,

            'onboarding_completed_at' =>
            now(),
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        $subscription =
            Subscription::create([
                'billing_cycle' =>
                Subscription::BILLING_CYCLE_MONTHLY,

                'status' =>
                Subscription::STATUS_PAST_DUE,

                'starts_at' =>
                Carbon::parse(
                    '2026-08-26 16:37:22'
                ),

                'current_period_starts_at' =>
                Carbon::parse(
                    '2026-08-26 16:37:22'
                ),

                'current_period_ends_at' =>
                Carbon::parse(
                    '2026-09-26 16:37:22'
                ),

                'next_billing_at' =>
                Carbon::parse(
                    '2026-09-26 16:37:22'
                ),

                'past_due_since' =>
                Carbon::parse(
                    '2026-09-26 16:37:22'
                ),

                'grace_ends_at' =>
                Carbon::parse(
                    '2026-10-03 16:37:22'
                ),

                'next_retry_at' =>
                Carbon::parse(
                    '2026-10-02 16:37:22'
                ),

                'retry_count' =>
                3,

                'cancel_at_period_end' =>
                false,

                'cancelled_at' =>
                null,
            ]);

        return [
            $tenant,
            $subscription,
        ];
    }
}
