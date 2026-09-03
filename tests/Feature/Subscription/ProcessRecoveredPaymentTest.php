<?php

namespace Tests\Feature\Subscription;

use App\Actions\Billing\ProcessRecoveredPayment;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use LogicException;
use Tests\TestCase;

class ProcessRecoveredPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_recovered_payment_succeeds(): void
    {
        [$subscription, $payment] =
            $this->createRecoveryPayment();

        $paidAt = Carbon::parse(
            '2026-09-29 18:42:15'
        );

        $payment = app(
            ProcessRecoveredPayment::class
        )->execute(
            $payment,
            $paidAt,
            'provider-success-100'
        );

        $this->assertSame(
            Payment::STATUS_SUCCEEDED,
            $payment->status
        );

        $this->assertTrue(
            $payment->paid_at->equalTo(
                $paidAt
            )
        );

        $this->assertSame(
            'provider-success-100',
            $payment->provider_payment_id
        );
    }

    public function test_recovered_payment_reactivates_subscription(): void
    {
        [$subscription, $payment] =
            $this->createRecoveryPayment();

        app(
            ProcessRecoveredPayment::class
        )->execute(
            $payment,
            Carbon::parse(
                '2026-09-29 18:42:15'
            )
        );

        $subscription->refresh();

        $this->assertSame(
            Subscription::STATUS_ACTIVE,
            $subscription->status
        );
    }

    public function test_recovery_clears_recovery_state(): void
    {
        [$subscription, $payment] =
            $this->createRecoveryPayment(
                retryCount: 2
            );

        app(
            ProcessRecoveredPayment::class
        )->execute(
            $payment,
            Carbon::parse(
                '2026-10-02 16:37:22'
            )
        );

        $subscription->refresh();

        $this->assertNull(
            $subscription->past_due_since
        );

        $this->assertNull(
            $subscription->grace_ends_at
        );

        $this->assertNull(
            $subscription->next_retry_at
        );

        $this->assertSame(
            0,
            $subscription->retry_count
        );
    }

    public function test_recovery_preserves_original_billing_anchor(): void
    {
        [$subscription, $payment] =
            $this->createRecoveryPayment();

        app(
            ProcessRecoveredPayment::class
        )->execute(
            $payment,
            Carbon::parse(
                '2026-09-29 18:42:15'
            )
        );

        $subscription->refresh();

        $this->assertTrue(
            $subscription
                ->current_period_starts_at
                ->equalTo(
                    '2026-09-26 16:37:22'
                )
        );

        $this->assertTrue(
            $subscription
                ->current_period_ends_at
                ->equalTo(
                    '2026-10-26 16:37:22'
                )
        );

        $this->assertTrue(
            $subscription
                ->next_billing_at
                ->equalTo(
                    '2026-10-26 16:37:22'
                )
        );
    }

    public function test_recovery_applies_the_plan_that_was_actually_paid(): void
    {
        [$subscription, $payment] =
            $this->createRecoveryPayment();

        $subscription->update([
            'billing_cycle' =>
            Subscription::BILLING_CYCLE_YEARLY,

            'billing_amount' =>
            600000,

            'billing_currency' =>
            'MXN',

            'pending_billing_cycle' =>
            Subscription::BILLING_CYCLE_MONTHLY,
        ]);

        $payment->update([
            'billing_cycle' =>
            Subscription::BILLING_CYCLE_MONTHLY,

            'gross_amount' =>
            60000,

            'amount' =>
            60000,

            'currency' =>
            'MXN',
        ]);

        app(
            ProcessRecoveredPayment::class
        )->execute(
            $payment,
            Carbon::parse(
                '2026-09-29 18:42:15'
            )
        );

        $subscription->refresh();

        $this->assertTrue(
            $subscription->isActive()
        );

        $this->assertSame(
            Subscription::BILLING_CYCLE_MONTHLY,
            $subscription->billing_cycle
        );

        $this->assertSame(
            60000,
            $subscription->billing_amount
        );

        $this->assertSame(
            'MXN',
            $subscription->billing_currency
        );

        $this->assertNull(
            $subscription->pending_billing_cycle
        );

        $this->assertTrue(
            $subscription
                ->current_period_starts_at
                ->equalTo(
                    '2026-09-26 16:37:22'
                )
        );

        $this->assertTrue(
            $subscription
                ->current_period_ends_at
                ->equalTo(
                    '2026-10-26 16:37:22'
                )
        );
    }

    public function test_recovery_reactivates_suspended_tenant(): void
    {
        [$subscription, $payment] =
            $this->createRecoveryPayment(
                tenantStatus: 'suspended'
            );

        $tenant =
            $subscription->tenant;

        $tenant->update([
            'suspended_at' =>
            Carbon::parse(
                '2026-10-03 16:37:22'
            ),
        ]);

        app(
            ProcessRecoveredPayment::class
        )->execute(
            $payment,
            Carbon::parse(
                '2026-10-04 09:15:00'
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

    public function test_recovered_payment_restores_service_access(): void
    {
        Carbon::setTestNow(
            '2026-10-04 09:15:00'
        );

        [$subscription, $payment] =
            $this->createRecoveryPayment(
                tenantStatus: 'suspended'
            );

        $tenant =
            $subscription->tenant;

        $tenant->update([
            'suspended_at' =>
            Carbon::parse(
                '2026-10-03 16:37:22'
            ),
        ]);

        $this->assertFalse(
            $tenant->hasAccessToService()
        );

        app(
            ProcessRecoveredPayment::class
        )->execute(
            $payment,
            Carbon::parse(
                '2026-10-04 09:15:00'
            )
        );

        $tenant->refresh();

        $this->assertTrue(
            $tenant->hasAccessToService()
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_succeeded_payment_cannot_be_recovered_again(): void
    {
        [$subscription, $payment] =
            $this->createRecoveryPayment();

        $payment->update([
            'status' =>
            Payment::STATUS_SUCCEEDED,

            'paid_at' =>
            now(),
        ]);

        $this->expectException(
            LogicException::class
        );

        app(
            ProcessRecoveredPayment::class
        )->execute(
            $payment,
            now()
        );
    }

    public function test_recovery_requires_past_due_subscription(): void
    {
        [$subscription, $payment] =
            $this->createRecoveryPayment();

        $subscription->update([
            'status' =>
            Subscription::STATUS_ACTIVE,
        ]);

        $this->expectException(
            LogicException::class
        );

        app(
            ProcessRecoveredPayment::class
        )->execute(
            $payment,
            now()
        );
    }

    private function createRecoveryPayment(
        int $retryCount = 1,
        string $tenantStatus = 'active',
    ): array {
        $tenant = Tenant::create([
            'name' =>
            'Consultorio Recovery',

            'slug' =>
            'consultorio-recovery-' .
                uniqid(),

            'status' =>
            $tenantStatus,

            'onboarding_completed_at' =>
            now(),
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        $subscription =
            Subscription::create([
                'billing_cycle' =>
                Subscription::BILLING_CYCLE_MONTHLY,

                'status' =>
                Subscription::STATUS_PAST_DUE,

                'starts_at' =>
                Carbon::parse(
                    '2026-08-26 16:37:22'
                ),

                'current_period_starts_at' =>
                Carbon::parse(
                    '2026-08-26 16:37:22'
                ),

                'current_period_ends_at' =>
                Carbon::parse(
                    '2026-09-26 16:37:22'
                ),

                'next_billing_at' =>
                Carbon::parse(
                    '2026-09-26 16:37:22'
                ),

                'past_due_since' =>
                Carbon::parse(
                    '2026-09-26 16:37:22'
                ),

                'grace_ends_at' =>
                Carbon::parse(
                    '2026-10-03 16:37:22'
                ),

                'next_retry_at' =>
                Carbon::parse(
                    '2026-09-29 16:37:22'
                ),

                'retry_count' =>
                $retryCount,

                'cancel_at_period_end' =>
                false,
            ]);

        $payment = Payment::create([
            'subscription_id' =>
            $subscription->id,

            'amount' =>
            129900,

            'currency' =>
            'MXN',

            'status' =>
            Payment::STATUS_PENDING,

            'attempted_at' =>
            Carbon::parse(
                '2026-09-29 16:37:22'
            ),

            'provider' =>
            'test',

            'idempotency_key' =>
            'recovery-' . uniqid(),
        ]);

        return [
            $subscription,
            $payment,
        ];
    }
}
