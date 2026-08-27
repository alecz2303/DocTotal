<?php

namespace Tests\Feature\Subscription;

use App\Actions\Subscription\ScheduleSubscriptionCancellation;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use LogicException;
use Tests\TestCase;

class ScheduleSubscriptionCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_subscription_can_schedule_cancellation(): void
    {
        $subscription = $this->createSubscription();

        $result = app(
            ScheduleSubscriptionCancellation::class
        )->execute($subscription);

        $this->assertTrue(
            $result->cancel_at_period_end
        );

        $this->assertSame(
            Subscription::STATUS_ACTIVE,
            $result->status
        );

        $this->assertNull(
            $result->cancelled_at
        );

        $this->assertNotNull(
            $result->next_billing_at
        );
    }

    public function test_scheduling_cancellation_clears_pending_plan_change(): void
    {
        $subscription = $this->createSubscription();

        $subscription->update([
            'pending_billing_cycle' =>
                Subscription::BILLING_CYCLE_YEARLY,
        ]);

        $result = app(
            ScheduleSubscriptionCancellation::class
        )->execute($subscription);

        $this->assertTrue(
            $result->cancel_at_period_end
        );

        $this->assertNull(
            $result->pending_billing_cycle
        );
    }

    public function test_past_due_subscription_cannot_schedule_cancellation(): void
    {
        $subscription = $this->createSubscription(
            Subscription::STATUS_PAST_DUE
        );

        $this->expectException(
            LogicException::class
        );

        app(
            ScheduleSubscriptionCancellation::class
        )->execute($subscription);
    }

    public function test_cancelled_subscription_cannot_schedule_cancellation(): void
    {
        $subscription = $this->createSubscription(
            Subscription::STATUS_CANCELLED
        );

        $this->expectException(
            LogicException::class
        );

        app(
            ScheduleSubscriptionCancellation::class
        )->execute($subscription);
    }

    private function createSubscription(
        string $status = Subscription::STATUS_ACTIVE,
    ): Subscription {
        $tenant = Tenant::create([
            'name' => 'Consultorio Cancelación',
            'slug' => 'cancelacion-' . uniqid(),
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

            'cancel_at_period_end' => false,

            'cancelled_at' =>
                $status === Subscription::STATUS_CANCELLED
                    ? now()
                    : null,
        ]);
    }
}
