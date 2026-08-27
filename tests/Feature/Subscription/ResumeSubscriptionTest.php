<?php

namespace Tests\Feature\Subscription;

use App\Actions\Subscription\ResumeSubscription;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use LogicException;
use Tests\TestCase;

class ResumeSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduled_cancellation_can_be_resumed(): void
    {
        $subscription = $this->createSubscription(
            cancelAtPeriodEnd: true
        );

        $result = app(
            ResumeSubscription::class
        )->execute($subscription);

        $this->assertSame(
            Subscription::STATUS_ACTIVE,
            $result->status
        );

        $this->assertFalse(
            $result->cancel_at_period_end
        );

        $this->assertNull(
            $result->cancelled_at
        );

        $this->assertNotNull(
            $result->next_billing_at
        );
    }

    public function test_resuming_active_subscription_without_scheduled_cancellation_is_idempotent(): void
    {
        $subscription = $this->createSubscription(
            cancelAtPeriodEnd: false
        );

        $nextBillingAt =
            $subscription->next_billing_at->copy();

        $result = app(
            ResumeSubscription::class
        )->execute($subscription);

        $this->assertFalse(
            $result->cancel_at_period_end
        );

        $this->assertTrue(
            $result->next_billing_at->equalTo(
                $nextBillingAt
            )
        );
    }

    public function test_resuming_does_not_restore_discarded_pending_plan_change(): void
    {
        $subscription = $this->createSubscription(
            cancelAtPeriodEnd: true
        );

        $this->assertNull(
            $subscription->pending_billing_cycle
        );

        $result = app(
            ResumeSubscription::class
        )->execute($subscription);

        $this->assertFalse(
            $result->cancel_at_period_end
        );

        $this->assertNull(
            $result->pending_billing_cycle
        );
    }

    public function test_past_due_subscription_cannot_be_resumed(): void
    {
        $subscription = $this->createSubscription(
            status: Subscription::STATUS_PAST_DUE,
            cancelAtPeriodEnd: true,
        );

        $this->expectException(
            LogicException::class
        );

        app(
            ResumeSubscription::class
        )->execute($subscription);
    }

    public function test_cancelled_subscription_cannot_be_resumed(): void
    {
        $subscription = $this->createSubscription(
            status: Subscription::STATUS_CANCELLED,
            cancelAtPeriodEnd: false,
        );

        $this->expectException(
            LogicException::class
        );

        app(
            ResumeSubscription::class
        )->execute($subscription);
    }

    private function createSubscription(
        string $status = Subscription::STATUS_ACTIVE,
        bool $cancelAtPeriodEnd = false,
    ): Subscription {
        $tenant = Tenant::create([
            'name' => 'Consultorio Reanudación',
            'slug' => 'reanudacion-' . uniqid(),
            'status' => 'active',
            'onboarding_completed_at' => now(),
        ]);

        app(TenantContext::class)->set($tenant);

        $startsAt = Carbon::parse(
            '2026-08-26 22:20:32'
        );

        $endsAt = Carbon::parse(
            '2026-09-26 22:20:32'
        );

        return Subscription::create([
            'billing_cycle' =>
                Subscription::BILLING_CYCLE_MONTHLY,

            'status' => $status,

            'starts_at' => $startsAt,

            'current_period_starts_at' =>
                $startsAt,

            'current_period_ends_at' =>
                $endsAt,

            'next_billing_at' =>
                $status === Subscription::STATUS_CANCELLED
                    ? null
                    : $endsAt,

            'cancel_at_period_end' =>
                $cancelAtPeriodEnd,

            'cancelled_at' =>
                $status === Subscription::STATUS_CANCELLED
                    ? now()
                    : null,
        ]);
    }
}
