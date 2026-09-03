<?php

namespace Tests\Feature\Subscription;

use App\Actions\Billing\ScheduleSubscriptionPlanChange;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ScheduleSubscriptionPlanChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_paid_yearly_subscription_only_schedules_monthly_for_next_renewal(): void
    {
        $subscription =
            $this->subscription(
                Subscription::STATUS_ACTIVE
            );

        $result = app(
            ScheduleSubscriptionPlanChange::class
        )->execute(
            $subscription,
            Subscription::BILLING_CYCLE_MONTHLY
        );

        $this->assertSame(
            Subscription::BILLING_CYCLE_YEARLY,
            $result->billing_cycle
        );

        $this->assertSame(
            600000,
            $result->billing_amount
        );

        $this->assertSame(
            Subscription::BILLING_CYCLE_MONTHLY,
            $result->pending_billing_cycle
        );

        $this->assertTrue(
            $result->isActive()
        );
    }

    public function test_past_due_yearly_subscription_keeps_current_contract_until_recovery_payment_succeeds(): void
    {
        $subscription =
            $this->subscription(
                Subscription::STATUS_PAST_DUE
            );

        $result = app(
            ScheduleSubscriptionPlanChange::class
        )->execute(
            $subscription,
            Subscription::BILLING_CYCLE_MONTHLY
        );

        $this->assertSame(
            Subscription::BILLING_CYCLE_YEARLY,
            $result->billing_cycle
        );

        $this->assertSame(
            600000,
            $result->billing_amount
        );

        $this->assertSame(
            Subscription::BILLING_CYCLE_MONTHLY,
            $result->pending_billing_cycle
        );

        $this->assertTrue(
            $result->isPastDue()
        );
    }

    private function subscription(
        string $status,
    ): Subscription {
        $tenant = Tenant::create([
            'name' =>
            'Consultorio Plan Change',

            'slug' =>
            'plan-change-' . uniqid(),

            'status' =>
            'active',

            'onboarding_completed_at' =>
            now(),
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        $startsAt =
            Carbon::parse(
                '2026-01-15 10:30:00'
            );

        $endsAt =
            Carbon::parse(
                '2027-01-15 10:30:00'
            );

        return Subscription::create([
            'billing_cycle' =>
            Subscription::BILLING_CYCLE_YEARLY,

            'status' =>
            $status,

            'starts_at' =>
            $startsAt,

            'current_period_starts_at' =>
            $startsAt,

            'current_period_ends_at' =>
            $endsAt,

            'next_billing_at' =>
            $endsAt,

            'billing_amount' =>
            600000,

            'billing_currency' =>
            'MXN',

            'past_due_since' =>
            $status === Subscription::STATUS_PAST_DUE
                ? $endsAt
                : null,

            'grace_ends_at' =>
            $status === Subscription::STATUS_PAST_DUE
                ? $endsAt->copy()->addDays(7)
                : null,

            'retry_count' =>
            0,

            'cancel_at_period_end' =>
            false,
        ]);
    }
}
