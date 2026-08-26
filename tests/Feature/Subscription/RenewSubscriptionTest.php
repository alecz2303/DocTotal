<?php

namespace Tests\Feature\Subscription;

use App\Actions\Subscription\RenewSubscription;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use LogicException;
use Tests\TestCase;

class RenewSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_subscription_can_be_renewed(): void
    {
        $subscription = $this->createSubscription(
            startsAt: Carbon::parse(
                '2026-09-14 16:37:22'
            ),
            periodStartsAt: Carbon::parse(
                '2026-09-14 16:37:22'
            ),
            periodEndsAt: Carbon::parse(
                '2026-10-14 16:37:22'
            ),
        );

        $subscription = app(
            RenewSubscription::class
        )->execute($subscription);

        $this->assertTrue(
            $subscription
                ->current_period_starts_at
                ->equalTo(
                    Carbon::parse(
                        '2026-10-14 16:37:22'
                    )
                )
        );

        $this->assertTrue(
            $subscription
                ->current_period_ends_at
                ->equalTo(
                    Carbon::parse(
                        '2026-11-14 16:37:22'
                    )
                )
        );

        $this->assertTrue(
            $subscription
                ->next_billing_at
                ->equalTo(
                    Carbon::parse(
                        '2026-11-14 16:37:22'
                    )
                )
        );
    }

    public function test_monthly_subscription_preserves_original_billing_anchor_after_short_month(): void
    {
        $subscription = $this->createSubscription(
            startsAt: Carbon::parse(
                '2027-01-31 16:37:22'
            ),
            periodStartsAt: Carbon::parse(
                '2027-01-31 16:37:22'
            ),
            periodEndsAt: Carbon::parse(
                '2027-02-28 16:37:22'
            ),
        );

        $subscription = app(
            RenewSubscription::class
        )->execute($subscription);

        $this->assertTrue(
            $subscription
                ->current_period_ends_at
                ->equalTo(
                    Carbon::parse(
                        '2027-03-31 16:37:22'
                    )
                )
        );
    }

    public function test_monthly_subscription_uses_last_valid_day_when_anchor_day_does_not_exist(): void
    {
        $subscription = $this->createSubscription(
            startsAt: Carbon::parse(
                '2027-01-31 16:37:22'
            ),
            periodStartsAt: Carbon::parse(
                '2027-03-31 16:37:22'
            ),
            periodEndsAt: Carbon::parse(
                '2027-04-30 16:37:22'
            ),
        );

        $subscription = app(
            RenewSubscription::class
        )->execute($subscription);

        $this->assertTrue(
            $subscription
                ->current_period_ends_at
                ->equalTo(
                    Carbon::parse(
                        '2027-05-31 16:37:22'
                    )
                )
        );
    }

    public function test_yearly_subscription_can_be_renewed(): void
    {
        $subscription = $this->createSubscription(
            billingCycle: Subscription::BILLING_CYCLE_YEARLY,

            startsAt: Carbon::parse(
                '2026-09-14 21:43:17'
            ),

            periodStartsAt: Carbon::parse(
                '2026-09-14 21:43:17'
            ),

            periodEndsAt: Carbon::parse(
                '2027-09-14 21:43:17'
            ),
        );

        $subscription = app(
            RenewSubscription::class
        )->execute($subscription);

        $this->assertTrue(
            $subscription
                ->current_period_ends_at
                ->equalTo(
                    Carbon::parse(
                        '2028-09-14 21:43:17'
                    )
                )
        );
    }

    public function test_yearly_subscription_restores_leap_day_when_possible(): void
    {
        $subscription = $this->createSubscription(
            billingCycle: Subscription::BILLING_CYCLE_YEARLY,

            startsAt: Carbon::parse(
                '2028-02-29 09:15:30'
            ),

            periodStartsAt: Carbon::parse(
                '2031-02-28 09:15:30'
            ),

            periodEndsAt: Carbon::parse(
                '2032-02-29 09:15:30'
            ),
        );

        $subscription = app(
            RenewSubscription::class
        )->execute($subscription);

        $this->assertTrue(
            $subscription
                ->current_period_ends_at
                ->equalTo(
                    Carbon::parse(
                        '2033-02-28 09:15:30'
                    )
                )
        );
    }

    public function test_past_due_subscription_cannot_be_renewed(): void
    {
        $subscription = $this->createSubscription(
            status: Subscription::STATUS_PAST_DUE
        );

        $this->expectException(
            LogicException::class
        );

        app(
            RenewSubscription::class
        )->execute($subscription);
    }

    public function test_subscription_scheduled_for_cancellation_cannot_be_renewed(): void
    {
        $subscription = $this->createSubscription(
            cancelAtPeriodEnd: true
        );

        $this->expectException(
            LogicException::class
        );

        app(
            RenewSubscription::class
        )->execute($subscription);
    }

    private function createSubscription(
        string $billingCycle =
        Subscription::BILLING_CYCLE_MONTHLY,
        string $status =
        Subscription::STATUS_ACTIVE,
        mixed $startsAt = null,
        mixed $periodStartsAt = null,
        mixed $periodEndsAt = null,
        bool $cancelAtPeriodEnd = false,
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

        $startsAt ??=
            Carbon::parse(
                '2026-09-14 16:37:22'
            );

        $periodStartsAt ??=
            $startsAt->copy();

        $periodEndsAt ??=
            $billingCycle ===
            Subscription::BILLING_CYCLE_YEARLY
            ? $startsAt
            ->copy()
            ->addYearNoOverflow()
            : $startsAt
            ->copy()
            ->addMonthNoOverflow();

        return Subscription::create([
            'billing_cycle' =>
            $billingCycle,

            'status' =>
            $status,

            'starts_at' =>
            $startsAt,

            'current_period_starts_at' =>
            $periodStartsAt,

            'current_period_ends_at' =>
            $periodEndsAt,

            'next_billing_at' =>
            $periodEndsAt,

            'cancel_at_period_end' =>
            $cancelAtPeriodEnd,
        ]);
    }
}
