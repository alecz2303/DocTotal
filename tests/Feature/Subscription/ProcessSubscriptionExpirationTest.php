<?php

namespace Tests\Feature\Subscription;

use App\Actions\Subscription\ProcessSubscriptionExpiration;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ProcessSubscriptionExpirationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_scheduled_cancellation_does_not_cancel_before_period_end(): void
    {
        Carbon::setTestNow(
            '2026-10-14 16:37:21'
        );

        $subscription =
            $this->createSubscription(
                periodEndsAt: Carbon::parse(
                    '2026-10-14 16:37:22'
                ),
                cancelAtPeriodEnd: true,
            );

        app(
            ProcessSubscriptionExpiration::class
        )->execute($subscription);

        $subscription->refresh();

        $this->assertSame(
            Subscription::STATUS_ACTIVE,
            $subscription->status
        );

        $this->assertTrue(
            $subscription->cancel_at_period_end
        );

        $this->assertNull(
            $subscription->cancelled_at
        );
    }

    public function test_scheduled_cancellation_is_processed_at_exact_period_end(): void
    {
        Carbon::setTestNow(
            '2026-10-14 16:37:22'
        );

        $subscription =
            $this->createSubscription(
                periodEndsAt: Carbon::parse(
                    '2026-10-14 16:37:22'
                ),
                cancelAtPeriodEnd: true,
            );

        app(
            ProcessSubscriptionExpiration::class
        )->execute($subscription);

        $subscription->refresh();

        $this->assertSame(
            Subscription::STATUS_CANCELLED,
            $subscription->status
        );

        $this->assertFalse(
            $subscription->cancel_at_period_end
        );

        $this->assertNotNull(
            $subscription->cancelled_at
        );

        $this->assertTrue(
            $subscription
                ->cancelled_at
                ->equalTo(now())
        );

        $this->assertNull(
            $subscription->next_billing_at
        );
    }

    public function test_scheduled_cancellation_is_processed_after_period_end(): void
    {
        Carbon::setTestNow(
            '2026-10-15 09:00:00'
        );

        $subscription =
            $this->createSubscription(
                periodEndsAt: Carbon::parse(
                    '2026-10-14 16:37:22'
                ),
                cancelAtPeriodEnd: true,
            );

        app(
            ProcessSubscriptionExpiration::class
        )->execute($subscription);

        $this->assertSame(
            Subscription::STATUS_CANCELLED,
            $subscription->status
        );
    }

    public function test_active_subscription_without_scheduled_cancellation_is_not_cancelled(): void
    {
        Carbon::setTestNow(
            '2026-10-15 09:00:00'
        );

        $subscription =
            $this->createSubscription(
                periodEndsAt: Carbon::parse(
                    '2026-10-14 16:37:22'
                ),
                cancelAtPeriodEnd: false,
            );

        app(
            ProcessSubscriptionExpiration::class
        )->execute($subscription);

        $this->assertSame(
            Subscription::STATUS_ACTIVE,
            $subscription->status
        );

        $this->assertNull(
            $subscription->cancelled_at
        );
    }

    public function test_already_cancelled_subscription_is_unchanged(): void
    {
        Carbon::setTestNow(
            '2026-10-15 09:00:00'
        );

        $cancelledAt = Carbon::parse(
            '2026-10-14 16:37:22'
        );

        $subscription =
            $this->createSubscription(
                status: Subscription::STATUS_CANCELLED,

                periodEndsAt: $cancelledAt,

                cancelAtPeriodEnd: false,

                cancelledAt: $cancelledAt,
            );

        app(
            ProcessSubscriptionExpiration::class
        )->execute($subscription);

        $subscription->refresh();

        $this->assertSame(
            Subscription::STATUS_CANCELLED,
            $subscription->status
        );

        $this->assertTrue(
            $subscription
                ->cancelled_at
                ->equalTo($cancelledAt)
        );
    }

    public function test_expiration_clears_pending_plan_change(): void
    {
        Carbon::setTestNow(
            '2026-10-14 16:37:22'
        );

        $subscription =
            $this->createSubscription(
                periodEndsAt: Carbon::parse(
                    '2026-10-14 16:37:22'
                ),
                cancelAtPeriodEnd: true,
            );

        $subscription->update([
            'pending_billing_cycle' =>
            Subscription::BILLING_CYCLE_YEARLY,
        ]);

        app(
            ProcessSubscriptionExpiration::class
        )->execute($subscription);

        $subscription->refresh();

        $this->assertSame(
            Subscription::STATUS_CANCELLED,
            $subscription->status
        );

        $this->assertNull(
            $subscription->pending_billing_cycle
        );
    }

    private function createSubscription(
        string $status =
        Subscription::STATUS_ACTIVE,
        mixed $periodEndsAt = null,
        bool $cancelAtPeriodEnd = false,
        mixed $cancelledAt = null,
    ): Subscription {
        $tenant = Tenant::create([
            'name' =>
            'Consultorio Test',

            'slug' =>
            'consultorio-test',

            'status' =>
            'active',

            'onboarding_completed_at' =>
            now(),
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        $startsAt = Carbon::parse(
            '2026-09-14 16:37:22'
        );

        $periodEndsAt ??=
            Carbon::parse(
                '2026-10-14 16:37:22'
            );

        return Subscription::create([
            'billing_cycle' =>
            Subscription::BILLING_CYCLE_MONTHLY,

            'status' =>
            $status,

            'starts_at' =>
            $startsAt,

            'current_period_starts_at' =>
            $startsAt,

            'current_period_ends_at' =>
            $periodEndsAt,

            'next_billing_at' =>
            $status ===
                Subscription::STATUS_CANCELLED
                ? null
                : $periodEndsAt,

            'cancel_at_period_end' =>
            $cancelAtPeriodEnd,

            'cancelled_at' =>
            $cancelledAt,
        ]);
    }
}
