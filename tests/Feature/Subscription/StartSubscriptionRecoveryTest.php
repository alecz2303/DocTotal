<?php

namespace Tests\Feature\Subscription;

use App\Actions\Billing\StartSubscriptionRecovery;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use LogicException;
use Tests\TestCase;

class StartSubscriptionRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_payment_starts_subscription_recovery(): void
    {
        $subscription = $this->createSubscription();

        $failedAt = Carbon::parse(
            '2026-09-26 16:37:22'
        );

        $subscription = app(
            StartSubscriptionRecovery::class
        )->execute(
            $subscription,
            $failedAt
        );

        $this->assertSame(
            Subscription::STATUS_PAST_DUE,
            $subscription->status
        );

        $this->assertTrue(
            $subscription->past_due_since->equalTo(
                '2026-09-26 16:37:22'
            )
        );

        $this->assertTrue(
            $subscription->grace_ends_at->equalTo(
                '2026-10-03 16:37:22'
            )
        );

        $this->assertTrue(
            $subscription->next_retry_at->equalTo(
                '2026-09-27 16:37:22'
            )
        );

        $this->assertSame(
            0,
            $subscription->retry_count
        );
    }

    public function test_recovery_preserves_exact_failure_time(): void
    {
        $subscription = $this->createSubscription();

        $failedAt = Carbon::parse(
            '2026-11-30 23:47:53'
        );

        $subscription = app(
            StartSubscriptionRecovery::class
        )->execute(
            $subscription,
            $failedAt
        );

        $this->assertSame(
            '23:47:53',
            $subscription
                ->past_due_since
                ->format('H:i:s')
        );

        $this->assertSame(
            '23:47:53',
            $subscription
                ->grace_ends_at
                ->format('H:i:s')
        );

        $this->assertSame(
            '23:47:53',
            $subscription
                ->next_retry_at
                ->format('H:i:s')
        );
    }

    public function test_recovery_uses_billing_configuration(): void
    {
        config([
            'billing.grace_period_days' => 10,

            'billing.retry_schedule_hours' => [
                12,
                48,
            ],
        ]);

        $subscription = $this->createSubscription();

        $failedAt = Carbon::parse(
            '2026-09-01 08:15:30'
        );

        $subscription = app(
            StartSubscriptionRecovery::class
        )->execute(
            $subscription,
            $failedAt
        );

        $this->assertTrue(
            $subscription->grace_ends_at->equalTo(
                '2026-09-11 08:15:30'
            )
        );

        $this->assertTrue(
            $subscription->next_retry_at->equalTo(
                '2026-09-01 20:15:30'
            )
        );
    }

    public function test_recovery_cannot_start_twice(): void
    {
        $subscription = $this->createSubscription();

        $subscription->update([
            'status' =>
            Subscription::STATUS_PAST_DUE,
        ]);

        $this->expectException(
            LogicException::class
        );

        app(
            StartSubscriptionRecovery::class
        )->execute(
            $subscription,
            now()
        );
    }

    public function test_cancelled_subscription_cannot_enter_recovery(): void
    {
        $subscription = $this->createSubscription();

        $subscription->update([
            'status' =>
            Subscription::STATUS_CANCELLED,
        ]);

        $this->expectException(
            LogicException::class
        );

        app(
            StartSubscriptionRecovery::class
        )->execute(
            $subscription,
            now()
        );
    }

    public function test_invalid_grace_period_configuration_is_rejected(): void
    {
        config([
            'billing.grace_period_days' => 0,
        ]);

        $subscription = $this->createSubscription();

        $this->expectException(
            LogicException::class
        );

        app(
            StartSubscriptionRecovery::class
        )->execute(
            $subscription,
            now()
        );
    }

    public function test_missing_retry_schedule_is_rejected(): void
    {
        config([
            'billing.retry_schedule_hours' => [],
        ]);

        $subscription = $this->createSubscription();

        $this->expectException(
            LogicException::class
        );

        app(
            StartSubscriptionRecovery::class
        )->execute(
            $subscription,
            now()
        );
    }

    private function createSubscription(): Subscription
    {
        $tenant = Tenant::create([
            'name' => 'Consultorio Test',
            'slug' => 'consultorio-test',
            'status' => 'active',
            'onboarding_completed_at' => now(),
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        $startsAt = Carbon::parse(
            '2026-08-26 16:37:22'
        );

        $periodEndsAt = Carbon::parse(
            '2026-09-26 16:37:22'
        );

        return Subscription::create([
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
        ]);
    }
}
