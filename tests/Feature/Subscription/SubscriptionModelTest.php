<?php

namespace Tests\Feature\Subscription;

use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use LogicException;
use Tests\TestCase;

class SubscriptionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_generates_uuid_automatically(): void
    {
        $tenant = $this->createTenant();

        app(TenantContext::class)->set($tenant);

        $subscription = $this->createSubscription();

        $this->assertNotNull(
            $subscription->uuid
        );

        $this->assertSame(
            'uuid',
            $subscription->getRouteKeyName()
        );

        $this->assertSame(
            $subscription->uuid,
            $subscription->getRouteKey()
        );
    }

    public function test_subscription_belongs_to_tenant(): void
    {
        $tenant = $this->createTenant();

        app(TenantContext::class)->set($tenant);

        $subscription = $this->createSubscription();

        $this->assertSame(
            $tenant->id,
            $subscription->tenant_id
        );

        $this->assertTrue(
            $subscription
                ->tenant
                ->is($tenant)
        );

        $this->assertTrue(
            $tenant
                ->subscriptions
                ->contains($subscription)
        );
    }

    public function test_subscription_dates_are_cast_to_datetime(): void
    {
        $tenant = $this->createTenant();

        app(TenantContext::class)->set($tenant);

        $subscription = $this->createSubscription();

        $this->assertInstanceOf(
            Carbon::class,
            $subscription->starts_at
        );

        $this->assertInstanceOf(
            Carbon::class,
            $subscription->current_period_starts_at
        );

        $this->assertInstanceOf(
            Carbon::class,
            $subscription->current_period_ends_at
        );

        $this->assertInstanceOf(
            Carbon::class,
            $subscription->next_billing_at
        );
    }

    public function test_monthly_subscription_is_identified_correctly(): void
    {
        $tenant = $this->createTenant();

        app(TenantContext::class)->set($tenant);

        $subscription = $this->createSubscription(
            billingCycle: Subscription::BILLING_CYCLE_MONTHLY
        );

        $this->assertTrue(
            $subscription->isMonthly()
        );

        $this->assertFalse(
            $subscription->isYearly()
        );
    }

    public function test_yearly_subscription_is_identified_correctly(): void
    {
        $tenant = $this->createTenant();

        app(TenantContext::class)->set($tenant);

        $subscription = $this->createSubscription(
            billingCycle: Subscription::BILLING_CYCLE_YEARLY
        );

        $this->assertTrue(
            $subscription->isYearly()
        );

        $this->assertFalse(
            $subscription->isMonthly()
        );
    }

    public function test_active_subscription_inside_period_is_current(): void
    {
        Carbon::setTestNow(
            '2026-08-26 10:30:00'
        );

        $tenant = $this->createTenant();

        app(TenantContext::class)->set($tenant);

        $subscription = $this->createSubscription(
            startsAt: now()->subDays(5),
            periodStartsAt: now()->subDays(5),
            periodEndsAt: now()->addDays(25),
            nextBillingAt: now()->addDays(25),
        );

        $this->assertTrue(
            $subscription->isCurrent()
        );

        Carbon::setTestNow();
    }

    public function test_active_subscription_after_period_end_is_not_current(): void
    {
        Carbon::setTestNow(
            '2026-08-26 10:30:00'
        );

        $tenant = $this->createTenant();

        app(TenantContext::class)->set($tenant);

        $subscription = $this->createSubscription(
            startsAt: now()->subMonth(),
            periodStartsAt: now()->subMonth(),
            periodEndsAt: now()->subSecond(),
            nextBillingAt: now()->subSecond(),
        );

        $this->assertFalse(
            $subscription->isCurrent()
        );

        Carbon::setTestNow();
    }

    public function test_cancelled_subscription_is_not_current(): void
    {
        $tenant = $this->createTenant();

        app(TenantContext::class)->set($tenant);

        $subscription = $this->createSubscription(
            status: Subscription::STATUS_CANCELLED
        );

        $this->assertFalse(
            $subscription->isCurrent()
        );
    }

    public function test_active_subscription_can_schedule_cancellation(): void
    {
        $tenant = $this->createTenant();

        app(TenantContext::class)->set($tenant);

        $subscription = $this->createSubscription();

        $subscription->scheduleCancellation();

        $subscription->refresh();

        $this->assertTrue(
            $subscription->cancel_at_period_end
        );

        $this->assertSame(
            Subscription::STATUS_ACTIVE,
            $subscription->status
        );
    }

    public function test_scheduling_cancellation_clears_pending_plan_change(): void
    {
        $tenant = $this->createTenant();

        app(TenantContext::class)->set($tenant);

        $subscription = $this->createSubscription();

        $subscription->update([
            'pending_billing_cycle' =>
            Subscription::BILLING_CYCLE_YEARLY,
        ]);

        $subscription->scheduleCancellation();

        $subscription->refresh();

        $this->assertTrue(
            $subscription->cancel_at_period_end
        );

        $this->assertNull(
            $subscription->pending_billing_cycle
        );
    }

    public function test_non_active_subscription_cannot_schedule_cancellation(): void
    {
        $tenant = $this->createTenant();

        app(TenantContext::class)->set($tenant);

        $subscription = $this->createSubscription(
            status: Subscription::STATUS_PAST_DUE
        );

        $this->expectException(
            LogicException::class
        );

        $subscription
            ->scheduleCancellation();
    }

    public function test_subscription_can_be_cancelled(): void
    {
        $tenant = $this->createTenant();

        app(TenantContext::class)->set($tenant);

        $subscription = $this->createSubscription();

        $subscription->cancel();

        $subscription->refresh();

        $this->assertSame(
            Subscription::STATUS_CANCELLED,
            $subscription->status
        );

        $this->assertNotNull(
            $subscription->cancelled_at
        );

        $this->assertFalse(
            $subscription->cancel_at_period_end
        );

        $this->assertNull(
            $subscription->next_billing_at
        );
    }

    public function test_cancelling_subscription_clears_pending_plan_change(): void
    {
        $tenant = $this->createTenant();

        app(TenantContext::class)->set($tenant);

        $subscription = $this->createSubscription();

        $subscription->update([
            'pending_billing_cycle' =>
            Subscription::BILLING_CYCLE_YEARLY,
        ]);

        $subscription->cancel();

        $subscription->refresh();

        $this->assertSame(
            Subscription::STATUS_CANCELLED,
            $subscription->status
        );

        $this->assertNull(
            $subscription->pending_billing_cycle
        );
    }

    public function test_cancelling_already_cancelled_subscription_is_idempotent(): void
    {
        $tenant = $this->createTenant();

        app(TenantContext::class)->set($tenant);

        $subscription = $this->createSubscription(
            status: Subscription::STATUS_CANCELLED,
            cancelledAt: now()->subDay(),
            nextBillingAt: null,
        );

        $originalCancelledAt =
            $subscription->cancelled_at;

        $subscription->cancel();

        $subscription->refresh();

        $this->assertSame(
            Subscription::STATUS_CANCELLED,
            $subscription->status
        );

        $this->assertTrue(
            $subscription
                ->cancelled_at
                ->equalTo(
                    $originalCancelledAt
                )
        );
    }

    public function test_subscriptions_are_isolated_by_tenant(): void
    {
        $tenantA = $this->createTenant(
            name: 'Tenant A',
            slug: 'tenant-a'
        );

        $tenantB = $this->createTenant(
            name: 'Tenant B',
            slug: 'tenant-b'
        );

        app(TenantContext::class)->set($tenantA);

        $subscriptionA =
            $this->createSubscription();

        app(TenantContext::class)->set($tenantB);

        $subscriptionB =
            $this->createSubscription();

        $subscriptions =
            Subscription::query()->get();

        $this->assertCount(
            1,
            $subscriptions
        );

        $this->assertTrue(
            $subscriptions
                ->first()
                ->is($subscriptionB)
        );

        $this->assertFalse(
            $subscriptions
                ->contains($subscriptionA)
        );
    }

    private function createTenant(
        string $name = 'Consultorio Test',
        string $slug = 'consultorio-test',
    ): Tenant {
        return Tenant::create([
            'name' => $name,
            'slug' => $slug,
            'onboarding_completed_at' => now(),
        ]);
    }

    private function createSubscription(
        string $billingCycle =
        Subscription::BILLING_CYCLE_MONTHLY,
        string $status =
        Subscription::STATUS_ACTIVE,
        mixed $startsAt = null,
        mixed $periodStartsAt = null,
        mixed $periodEndsAt = null,
        mixed $nextBillingAt = null,
        bool $cancelAtPeriodEnd = false,
        mixed $cancelledAt = null,
        ?int $billingAmount = null,
        string $billingCurrency = 'MXN',
    ): Subscription {
        $startsAt ??= now();

        $periodStartsAt ??=
            $startsAt;

        $periodEndsAt ??=
            now()->addMonth();

        if (
            $nextBillingAt === null
            && $status !==
            Subscription::STATUS_CANCELLED
        ) {
            $nextBillingAt =
                $periodEndsAt;
        }

        return Subscription::create([
            'billing_cycle' =>
            $billingCycle,

            'billing_amount' =>
            $billingAmount,

            'billing_currency' =>
            $billingCurrency,

            'status' =>
            $status,

            'starts_at' =>
            $startsAt,

            'current_period_starts_at' =>
            $periodStartsAt,

            'current_period_ends_at' =>
            $periodEndsAt,

            'next_billing_at' =>
            $nextBillingAt,

            'cancel_at_period_end' =>
            $cancelAtPeriodEnd,

            'cancelled_at' =>
            $cancelledAt,
        ]);
    }

    public function test_active_subscription_before_period_start_is_not_current(): void
    {
        Carbon::setTestNow(
            '2026-08-26 10:30:00'
        );

        $tenant = $this->createTenant();

        app(TenantContext::class)->set($tenant);

        $subscription = $this->createSubscription(
            startsAt: now()->addDay(),
            periodStartsAt: now()->addDay(),
            periodEndsAt: now()->addMonth(),
            nextBillingAt: now()->addMonth(),
        );

        $this->assertFalse(
            $subscription->isCurrent()
        );

        Carbon::setTestNow();
    }

    public function test_subscription_can_store_billing_amount_in_minor_units(): void
    {
        $tenant = $this->createTenant();

        app(TenantContext::class)->set(
            $tenant
        );

        $subscription =
            $this->createSubscription(
                billingAmount: 129900,
                billingCurrency: 'MXN',
            );

        $this->assertIsInt(
            $subscription->billing_amount
        );

        $this->assertSame(
            129900,
            $subscription->billing_amount
        );

        $this->assertSame(
            'MXN',
            $subscription->billing_currency
        );
    }
}
