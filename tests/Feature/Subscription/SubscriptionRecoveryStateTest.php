<?php

namespace Tests\Feature\Subscription;

use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SubscriptionRecoveryStateTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_recovery_dates_are_cast_to_datetime(): void
    {
        $subscription = $this->createSubscription([
            'status' => Subscription::STATUS_PAST_DUE,
            'past_due_since' => now(),
            'grace_ends_at' => now()->addDays(7),
            'next_retry_at' => now()->addDay(),
            'retry_count' => 1,
        ]);

        $this->assertInstanceOf(
            Carbon::class,
            $subscription->past_due_since
        );

        $this->assertInstanceOf(
            Carbon::class,
            $subscription->grace_ends_at
        );

        $this->assertInstanceOf(
            Carbon::class,
            $subscription->next_retry_at
        );

        $this->assertSame(
            1,
            $subscription->retry_count
        );
    }

    public function test_past_due_subscription_is_in_grace_period_before_grace_end(): void
    {
        Carbon::setTestNow(
            '2026-09-01 10:00:00'
        );

        $subscription = $this->createSubscription([
            'status' => Subscription::STATUS_PAST_DUE,
            'grace_ends_at' => Carbon::parse(
                '2026-09-08 10:00:00'
            ),
        ]);

        $this->assertTrue(
            $subscription->isInGracePeriod()
        );

        $this->assertFalse(
            $subscription->gracePeriodHasExpired()
        );
    }

    public function test_grace_period_expires_at_exact_grace_end_instant(): void
    {
        Carbon::setTestNow(
            '2026-09-08 10:00:00'
        );

        $subscription = $this->createSubscription([
            'status' => Subscription::STATUS_PAST_DUE,
            'grace_ends_at' => Carbon::parse(
                '2026-09-08 10:00:00'
            ),
        ]);

        $this->assertFalse(
            $subscription->isInGracePeriod()
        );

        $this->assertTrue(
            $subscription->gracePeriodHasExpired()
        );
    }

    public function test_retry_is_not_due_before_next_retry_at(): void
    {
        Carbon::setTestNow(
            '2026-09-02 09:59:59'
        );

        $subscription = $this->createSubscription([
            'status' => Subscription::STATUS_PAST_DUE,
            'next_retry_at' => Carbon::parse(
                '2026-09-02 10:00:00'
            ),
        ]);

        $this->assertFalse(
            $subscription->retryIsDue()
        );
    }

    public function test_retry_is_due_at_exact_next_retry_instant(): void
    {
        Carbon::setTestNow(
            '2026-09-02 10:00:00'
        );

        $subscription = $this->createSubscription([
            'status' => Subscription::STATUS_PAST_DUE,
            'next_retry_at' => Carbon::parse(
                '2026-09-02 10:00:00'
            ),
        ]);

        $this->assertTrue(
            $subscription->retryIsDue()
        );
    }

    public function test_active_subscription_is_not_in_grace_period_even_with_grace_date(): void
    {
        Carbon::setTestNow(
            '2026-09-01 10:00:00'
        );

        $subscription = $this->createSubscription([
            'status' => Subscription::STATUS_ACTIVE,
            'grace_ends_at' => now()->addDays(7),
        ]);

        $this->assertFalse(
            $subscription->isInGracePeriod()
        );

        $this->assertFalse(
            $subscription->gracePeriodHasExpired()
        );
    }

    public function test_active_subscription_never_has_retry_due(): void
    {
        Carbon::setTestNow(
            '2026-09-02 10:00:00'
        );

        $subscription = $this->createSubscription([
            'status' => Subscription::STATUS_ACTIVE,
            'next_retry_at' => now(),
        ]);

        $this->assertFalse(
            $subscription->retryIsDue()
        );
    }

    private function createSubscription(
        array $attributes = []
    ): Subscription {
        $tenant = Tenant::create([
            'name' => 'Consultorio Test',
            'slug' => 'consultorio-test',
            'status' => 'active',
            'onboarding_completed_at' => now(),
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        $startsAt = now();

        $periodEndsAt = $startsAt
            ->copy()
            ->addMonthNoOverflow();

        return Subscription::create(
            array_merge([
                'billing_cycle' =>
                Subscription::BILLING_CYCLE_MONTHLY,

                'status' =>
                Subscription::STATUS_ACTIVE,

                'starts_at' =>
                $startsAt,

                'current_period_starts_at' =>
                $startsAt,

                'current_period_ends_at' =>
                $periodEndsAt,

                'next_billing_at' =>
                $periodEndsAt,

                'past_due_since' =>
                null,

                'grace_ends_at' =>
                null,

                'next_retry_at' =>
                null,

                'retry_count' =>
                0,

                'cancel_at_period_end' =>
                false,

                'cancelled_at' =>
                null,
            ], $attributes)
        );
    }
}
