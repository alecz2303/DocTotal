<?php

namespace Tests\Feature\Subscription;

use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ProcessSubscriptionCancellationsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_command_cancels_subscription_at_exact_period_end(): void
    {
        Carbon::setTestNow(
            '2026-10-14 16:37:22'
        );

        $subscription =
            $this->createSubscription(
                periodEndsAt: '2026-10-14 16:37:22',
                cancelAtPeriodEnd: true,
            );

        $this->artisan(
            'billing:process-cancellations'
        )
            ->expectsOutput(
                'Cancelaciones procesadas: 1'
            )
            ->assertSuccessful();

        $subscription->refresh();

        $this->assertSame(
            Subscription::STATUS_CANCELLED,
            $subscription->status
        );

        $this->assertFalse(
            $subscription->cancel_at_period_end
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

    public function test_command_does_not_cancel_before_period_end(): void
    {
        Carbon::setTestNow(
            '2026-10-14 16:37:21'
        );

        $subscription =
            $this->createSubscription(
                periodEndsAt: '2026-10-14 16:37:22',
                cancelAtPeriodEnd: true,
            );

        $this->artisan(
            'billing:process-cancellations'
        )
            ->expectsOutput(
                'Cancelaciones procesadas: 0'
            )
            ->assertSuccessful();

        $subscription->refresh();

        $this->assertSame(
            Subscription::STATUS_ACTIVE,
            $subscription->status
        );
    }

    public function test_command_ignores_subscription_without_scheduled_cancellation(): void
    {
        Carbon::setTestNow(
            '2026-10-15 09:00:00'
        );

        $subscription =
            $this->createSubscription(
                periodEndsAt: '2026-10-14 16:37:22',
                cancelAtPeriodEnd: false,
            );

        $this->artisan(
            'billing:process-cancellations'
        )
            ->expectsOutput(
                'Cancelaciones procesadas: 0'
            )
            ->assertSuccessful();

        $subscription->refresh();

        $this->assertSame(
            Subscription::STATUS_ACTIVE,
            $subscription->status
        );
    }

    public function test_command_ignores_already_cancelled_subscription(): void
    {
        Carbon::setTestNow(
            '2026-10-15 09:00:00'
        );

        $subscription =
            $this->createSubscription(
                status: Subscription::STATUS_CANCELLED,
                periodEndsAt: '2026-10-14 16:37:22',
                cancelAtPeriodEnd: false,
                cancelledAt: '2026-10-14 16:37:22',
            );

        $this->artisan(
            'billing:process-cancellations'
        )
            ->expectsOutput(
                'Cancelaciones procesadas: 0'
            )
            ->assertSuccessful();

        $subscription->refresh();

        $this->assertSame(
            Subscription::STATUS_CANCELLED,
            $subscription->status
        );
    }

    public function test_command_processes_multiple_tenants_without_tenant_context(): void
    {
        Carbon::setTestNow(
            '2026-10-15 09:00:00'
        );

        $subscriptionA =
            $this->createSubscription(
                periodEndsAt: '2026-10-14 16:37:22',
                cancelAtPeriodEnd: true,
            );

        app(TenantContext::class)->clear();

        $subscriptionB =
            $this->createSubscription(
                periodEndsAt: '2026-10-14 18:00:00',
                cancelAtPeriodEnd: true,
            );

        app(TenantContext::class)->clear();

        $this->artisan(
            'billing:process-cancellations'
        )
            ->expectsOutput(
                'Cancelaciones procesadas: 2'
            )
            ->assertSuccessful();

        $this->assertSame(
            Subscription::STATUS_CANCELLED,
            $subscriptionA->refresh()->status
        );

        $this->assertSame(
            Subscription::STATUS_CANCELLED,
            $subscriptionB->refresh()->status
        );
    }

    private function createSubscription(
        string $status =
        Subscription::STATUS_ACTIVE,
        string $periodEndsAt =
        '2026-10-14 16:37:22',
        bool $cancelAtPeriodEnd = false,
        ?string $cancelledAt = null,
    ): Subscription {
        $tenant = Tenant::create([
            'name' =>
            'Consultorio Cancellation ' .
                uniqid(),

            'slug' =>
            'consultorio-cancellation-' .
                uniqid(),

            'status' =>
            'active',

            'onboarding_completed_at' =>
            now(),
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        return Subscription::create([
            'billing_cycle' =>
            Subscription::BILLING_CYCLE_MONTHLY,

            'billing_amount' =>
            129900,

            'billing_currency' =>
            'MXN',

            'status' =>
            $status,

            'starts_at' =>
            Carbon::parse(
                '2026-09-14 16:37:22'
            ),

            'current_period_starts_at' =>
            Carbon::parse(
                '2026-09-14 16:37:22'
            ),

            'current_period_ends_at' =>
            Carbon::parse(
                $periodEndsAt
            ),

            'next_billing_at' =>
            $status ===
                Subscription::STATUS_CANCELLED
                ? null
                : Carbon::parse(
                    $periodEndsAt
                ),

            'retry_count' =>
            0,

            'cancel_at_period_end' =>
            $cancelAtPeriodEnd,

            'cancelled_at' =>
            $cancelledAt
                ? Carbon::parse(
                    $cancelledAt
                )
                : null,
        ]);
    }
}
