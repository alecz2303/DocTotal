<?php

namespace Tests\Feature\Subscription;

use App\Actions\Subscription\ActivateSubscription;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

class ActivateSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_subscription_starts_at_exact_payment_datetime(): void
    {
        $tenant = $this->createTenant();

        $paidAt = Carbon::parse(
            '2026-09-14 16:37:22'
        );

        $subscription = app(
            ActivateSubscription::class
        )->execute(
            $tenant,
            Subscription::BILLING_CYCLE_MONTHLY,
            $paidAt
        );

        $this->assertTrue(
            $subscription->starts_at->equalTo(
                $paidAt
            )
        );

        $this->assertTrue(
            $subscription
                ->current_period_starts_at
                ->equalTo($paidAt)
        );

        $this->assertTrue(
            $subscription
                ->current_period_ends_at
                ->equalTo(
                    Carbon::parse(
                        '2026-10-14 16:37:22'
                    )
                )
        );

        $this->assertTrue(
            $subscription
                ->next_billing_at
                ->equalTo(
                    Carbon::parse(
                        '2026-10-14 16:37:22'
                    )
                )
        );
    }

    public function test_yearly_subscription_preserves_exact_payment_time(): void
    {
        $tenant = $this->createTenant();

        $paidAt = Carbon::parse(
            '2026-09-14 21:43:17'
        );

        $subscription = app(
            ActivateSubscription::class
        )->execute(
            $tenant,
            Subscription::BILLING_CYCLE_YEARLY,
            $paidAt
        );

        $this->assertTrue(
            $subscription
                ->current_period_ends_at
                ->equalTo(
                    Carbon::parse(
                        '2027-09-14 21:43:17'
                    )
                )
        );

        $this->assertTrue(
            $subscription
                ->next_billing_at
                ->equalTo(
                    Carbon::parse(
                        '2027-09-14 21:43:17'
                    )
                )
        );
    }

    public function test_monthly_subscription_handles_end_of_month_without_overflow(): void
    {
        $tenant = $this->createTenant();

        $paidAt = Carbon::parse(
            '2027-01-31 16:37:22'
        );

        $subscription = app(
            ActivateSubscription::class
        )->execute(
            $tenant,
            Subscription::BILLING_CYCLE_MONTHLY,
            $paidAt
        );

        $this->assertTrue(
            $subscription
                ->current_period_ends_at
                ->equalTo(
                    Carbon::parse(
                        '2027-02-28 16:37:22'
                    )
                )
        );
    }

    public function test_yearly_subscription_handles_leap_day_without_overflow(): void
    {
        $tenant = $this->createTenant();

        $paidAt = Carbon::parse(
            '2028-02-29 09:15:30'
        );

        $subscription = app(
            ActivateSubscription::class
        )->execute(
            $tenant,
            Subscription::BILLING_CYCLE_YEARLY,
            $paidAt
        );

        $this->assertTrue(
            $subscription
                ->current_period_ends_at
                ->equalTo(
                    Carbon::parse(
                        '2029-02-28 09:15:30'
                    )
                )
        );
    }

    public function test_activation_changes_tenant_from_trial_to_active(): void
    {
        $tenant = $this->createTenant();

        $subscription = app(
            ActivateSubscription::class
        )->execute(
            $tenant,
            Subscription::BILLING_CYCLE_MONTHLY,
            Carbon::parse(
                '2026-09-14 16:37:22'
            )
        );

        $tenant->refresh();

        $this->assertSame(
            'active',
            $tenant->status
        );

        $this->assertSame(
            Subscription::STATUS_ACTIVE,
            $subscription->status
        );
    }

    public function test_activation_clears_previous_suspension_datetime(): void
    {
        $tenant = $this->createTenant([
            'status' => 'suspended',
            'suspended_at' => now()->subDay(),
        ]);

        app(
            ActivateSubscription::class
        )->execute(
            $tenant,
            Subscription::BILLING_CYCLE_MONTHLY,
            Carbon::parse(
                '2026-09-14 16:37:22'
            )
        );

        $tenant->refresh();

        $this->assertSame(
            'active',
            $tenant->status
        );

        $this->assertNull(
            $tenant->suspended_at
        );
    }

    public function test_invalid_billing_cycle_is_rejected(): void
    {
        $tenant = $this->createTenant();

        $this->expectException(
            InvalidArgumentException::class
        );

        app(
            ActivateSubscription::class
        )->execute(
            $tenant,
            'weekly',
            now()
        );
    }

    public function test_tenant_cannot_receive_second_active_subscription(): void
    {
        $tenant = $this->createTenant();

        $action = app(
            ActivateSubscription::class
        );

        $action->execute(
            $tenant,
            Subscription::BILLING_CYCLE_MONTHLY,
            Carbon::parse(
                '2026-09-14 16:37:22'
            )
        );

        $this->expectException(
            LogicException::class
        );

        $action->execute(
            $tenant,
            Subscription::BILLING_CYCLE_YEARLY,
            Carbon::parse(
                '2026-09-15 10:00:00'
            )
        );
    }

    private function createTenant(
        array $attributes = []
    ): Tenant {
        return Tenant::create(
            array_merge([
                'name' => 'Consultorio Test',
                'slug' => 'consultorio-test',
                'status' => 'trial',
                'trial_started_at' =>
                now()->subDays(5),
                'trial_ends_at' =>
                now()->addDays(5),
                'onboarding_completed_at' =>
                now(),
            ], $attributes)
        );
    }

    public function test_tenant_with_past_due_subscription_cannot_receive_new_active_subscription(): void
    {
        $tenant = $this->createTenant([
            'status' => 'active',
        ]);

        \App\Support\TenantContext::class;

        app(\App\Support\TenantContext::class)
            ->set($tenant);

        Subscription::create([
            'billing_cycle' =>
            Subscription::BILLING_CYCLE_MONTHLY,

            'status' =>
            Subscription::STATUS_PAST_DUE,

            'starts_at' =>
            now()->subMonth(),

            'current_period_starts_at' =>
            now()->subMonth(),

            'current_period_ends_at' =>
            now()->addDays(5),

            'next_billing_at' =>
            now(),
        ]);

        $this->expectException(
            LogicException::class
        );

        app(
            ActivateSubscription::class
        )->execute(
            $tenant,
            Subscription::BILLING_CYCLE_MONTHLY,
            now()
        );
    }
}
