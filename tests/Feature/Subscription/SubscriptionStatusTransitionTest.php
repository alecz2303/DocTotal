<?php

namespace Tests\Feature\Subscription;

use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class SubscriptionStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_subscription_can_be_marked_past_due(): void
    {
        $subscription = $this->createSubscription(
            Subscription::STATUS_ACTIVE
        );

        $subscription->markPastDue();

        $subscription->refresh();

        $this->assertSame(
            Subscription::STATUS_PAST_DUE,
            $subscription->status
        );
    }

    public function test_past_due_subscription_can_be_reactivated(): void
    {
        $subscription = $this->createSubscription(
            Subscription::STATUS_PAST_DUE
        );

        $subscription->reactivate();

        $subscription->refresh();

        $this->assertSame(
            Subscription::STATUS_ACTIVE,
            $subscription->status
        );
    }

    public function test_past_due_subscription_cannot_be_marked_past_due_again(): void
    {
        $subscription = $this->createSubscription(
            Subscription::STATUS_PAST_DUE
        );

        $this->expectException(
            LogicException::class
        );

        $subscription->markPastDue();
    }

    public function test_cancelled_subscription_cannot_be_marked_past_due(): void
    {
        $subscription = $this->createSubscription(
            Subscription::STATUS_CANCELLED
        );

        $this->expectException(
            LogicException::class
        );

        $subscription->markPastDue();
    }

    public function test_active_subscription_cannot_be_reactivated(): void
    {
        $subscription = $this->createSubscription(
            Subscription::STATUS_ACTIVE
        );

        $this->expectException(
            LogicException::class
        );

        $subscription->reactivate();
    }

    public function test_cancelled_subscription_cannot_be_reactivated(): void
    {
        $subscription = $this->createSubscription(
            Subscription::STATUS_CANCELLED
        );

        $this->expectException(
            LogicException::class
        );

        $subscription->reactivate();
    }

    private function createSubscription(
        string $status
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
            false,

            'cancelled_at' =>
            $status ===
                Subscription::STATUS_CANCELLED
                ? now()
                : null,
        ]);
    }
}
