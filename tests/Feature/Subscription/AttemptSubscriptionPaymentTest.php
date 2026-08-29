<?php

namespace Tests\Feature\Subscription;

use App\Actions\Billing\AttemptSubscriptionPayment;
use App\Contracts\PaymentGateway;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\PromotionalCredit;
use App\Models\Tenant;
use App\Services\Billing\FakePaymentGateway;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use LogicException;
use Tests\TestCase;
use App\Models\Referral;

class AttemptSubscriptionPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_attempt_creates_pending_payment_with_subscription_amount(): void
    {
        $subscription =
            $this->createSubscription();

        $gateway =
            $this->fakeGateway();

        $gateway->succeedNextCharge(
            'provider-100'
        );

        $payment = app(
            AttemptSubscriptionPayment::class
        )->execute(
            $subscription,
            Carbon::parse(
                '2026-09-26 16:37:22'
            ),
            'charge-subscription-1'
        );

        $this->assertSame(
            129900,
            $payment->amount
        );

        $this->assertSame(
            'MXN',
            $payment->currency
        );

        $this->assertSame(
            $subscription->id,
            $payment->subscription_id
        );

        $this->assertSame(
            $subscription->tenant_id,
            $payment->tenant_id
        );
    }

    public function test_successful_initial_attempt_marks_payment_succeeded(): void
    {
        $subscription =
            $this->createSubscription();

        $gateway =
            $this->fakeGateway();

        $gateway->succeedNextCharge(
            'provider-success-1'
        );

        $payment = app(
            AttemptSubscriptionPayment::class
        )->execute(
            $subscription,
            now(),
            'initial-success-1'
        );

        $this->assertSame(
            Payment::STATUS_SUCCEEDED,
            $payment->status
        );

        $this->assertSame(
            'provider-success-1',
            $payment->provider_payment_id
        );
    }

    public function test_failed_initial_attempt_starts_recovery(): void
    {
        $subscription =
            $this->createSubscription();

        $gateway =
            $this->fakeGateway();

        $gateway->failNextCharge(
            'card_declined',
            'Tarjeta rechazada.'
        );

        $payment = app(
            AttemptSubscriptionPayment::class
        )->execute(
            $subscription,
            Carbon::parse(
                '2026-09-26 16:37:22'
            ),
            'initial-failure-1'
        );

        $this->assertSame(
            Payment::STATUS_FAILED,
            $payment->status
        );

        $subscription->refresh();

        $this->assertSame(
            Subscription::STATUS_PAST_DUE,
            $subscription->status
        );

        $this->assertNotNull(
            $subscription->past_due_since
        );
    }

    public function test_failed_retry_updates_existing_recovery(): void
    {
        $subscription =
            $this->createPastDueSubscription();

        $gateway =
            $this->fakeGateway();

        $gateway->failNextCharge(
            'insufficient_funds',
            'Fondos insuficientes.'
        );

        app(
            AttemptSubscriptionPayment::class
        )->execute(
            $subscription,
            Carbon::parse(
                '2026-09-27 16:37:22'
            ),
            'retry-failure-1',
            isRetry: true,
        );

        $subscription->refresh();

        $this->assertSame(
            1,
            $subscription->retry_count
        );
    }

    public function test_successful_retry_recovers_subscription(): void
    {
        $subscription =
            $this->createPastDueSubscription();

        $gateway =
            $this->fakeGateway();

        $gateway->succeedNextCharge(
            'provider-recovery-1'
        );

        $payment = app(
            AttemptSubscriptionPayment::class
        )->execute(
            $subscription,
            Carbon::parse(
                '2026-09-27 16:37:22'
            ),
            'retry-success-1',
            isRetry: true,
        );

        $subscription->refresh();

        $this->assertSame(
            Payment::STATUS_SUCCEEDED,
            $payment->status
        );

        $this->assertSame(
            Subscription::STATUS_ACTIVE,
            $subscription->status
        );

        $this->assertNull(
            $subscription->past_due_since
        );
    }

    public function test_same_idempotency_key_returns_existing_payment(): void
    {
        $subscription =
            $this->createSubscription();

        $gateway =
            $this->fakeGateway();

        $gateway->succeedNextCharge(
            'provider-first'
        );

        $first = app(
            AttemptSubscriptionPayment::class
        )->execute(
            $subscription,
            now(),
            'same-key'
        );

        $second = app(
            AttemptSubscriptionPayment::class
        )->execute(
            $subscription,
            now(),
            'same-key'
        );

        $this->assertTrue(
            $first->is($second)
        );

        $this->assertSame(
            1,
            Payment::withoutGlobalScopes()
                ->where(
                    'idempotency_key',
                    'same-key'
                )
                ->count()
        );
    }

    public function test_subscription_without_billing_amount_cannot_be_charged(): void
    {
        $subscription =
            $this->createSubscription(
                billingAmount: null
            );

        $this->expectException(
            LogicException::class
        );

        app(
            AttemptSubscriptionPayment::class
        )->execute(
            $subscription,
            now(),
            'missing-amount'
        );
    }

    public function test_retry_requires_past_due_subscription(): void
    {
        $subscription =
            $this->createSubscription();

        $this->expectException(
            LogicException::class
        );

        app(
            AttemptSubscriptionPayment::class
        )->execute(
            $subscription,
            now(),
            'invalid-retry',
            isRetry: true,
        );
    }

    public function test_successful_payment_consumes_reserved_promotional_credit(): void
    {
        $subscription =
            $this->createSubscription(
                billingAmount: 60000
            );

        $credit =
            $this->createPromotionalCredit(
                $subscription->tenant_id
            );

        $gateway =
            $this->fakeGateway();

        $gateway->succeedNextCharge(
            'provider-credit-success'
        );

        $payment = app(
            AttemptSubscriptionPayment::class
        )->execute(
            $subscription,
            Carbon::parse(
                '2026-09-26 16:37:22'
            ),
            'credit-success-1'
        );

        $credit->refresh();
        $payment->refresh();

        $this->assertSame(
            Payment::STATUS_SUCCEEDED,
            $payment->status
        );

        $this->assertSame(
            60000,
            $payment->gross_amount
        );

        $this->assertSame(
            5000,
            $payment->promotional_credit_amount
        );

        $this->assertSame(
            55000,
            $payment->amount
        );

        $this->assertSame(
            PromotionalCredit::STATUS_CONSUMED,
            $credit->status
        );

        $this->assertSame(
            $payment->id,
            $credit->payment_id
        );

        $this->assertNotNull(
            $credit->consumed_at
        );
    }

    public function test_failed_payment_releases_reserved_promotional_credit(): void
    {
        $subscription =
            $this->createSubscription(
                billingAmount: 60000
            );

        $credit =
            $this->createPromotionalCredit(
                $subscription->tenant_id
            );

        $gateway =
            $this->fakeGateway();

        $gateway->failNextCharge(
            'card_declined',
            'Tarjeta rechazada.'
        );

        $failedAt =
            Carbon::parse(
                '2026-09-26 16:37:22'
            );

        $payment = app(
            AttemptSubscriptionPayment::class
        )->execute(
            $subscription,
            $failedAt,
            'credit-failure-1'
        );

        $credit->refresh();
        $payment->refresh();

        $this->assertSame(
            Payment::STATUS_FAILED,
            $payment->status
        );

        /*
        * Payment conserva el historial del intento:
        * bruto 600, crédito 50, cobro neto 550.
        */
        $this->assertSame(
            60000,
            $payment->gross_amount
        );

        $this->assertSame(
            5000,
            $payment->promotional_credit_amount
        );

        $this->assertSame(
            55000,
            $payment->amount
        );

        /*
        * Pero el crédito vuelve a estar disponible.
        */
        $this->assertSame(
            PromotionalCredit::STATUS_AVAILABLE,
            $credit->status
        );

        $this->assertNull(
            $credit->payment_id
        );

        $this->assertNull(
            $credit->consumed_at
        );

        $this->assertNotNull(
            $payment->promotional_credits_released_at
        );
    }

    public function test_failed_payment_credit_is_reused_and_consumed_by_successful_retry(): void
    {
        $subscription =
            $this->createSubscription(
                billingAmount: 60000
            );

        $credit =
            $this->createPromotionalCredit(
                $subscription->tenant_id
            );

        $gateway =
            $this->fakeGateway();

        /*
        * Primer intento: falla.
        */
        $gateway->failNextCharge(
            'card_declined',
            'Tarjeta rechazada.'
        );

        $failedPayment = app(
            AttemptSubscriptionPayment::class
        )->execute(
            $subscription,
            Carbon::parse(
                '2026-09-26 16:37:22'
            ),
            'credit-retry-failure-1'
        );

        $subscription->refresh();
        $credit->refresh();
        $failedPayment->refresh();

        $this->assertSame(
            Payment::STATUS_FAILED,
            $failedPayment->status
        );

        $this->assertSame(
            5000,
            $failedPayment->promotional_credit_amount
        );

        $this->assertSame(
            55000,
            $failedPayment->amount
        );

        $this->assertSame(
            PromotionalCredit::STATUS_AVAILABLE,
            $credit->status
        );

        $this->assertNull(
            $credit->payment_id
        );

        /*
        * Retry: el mismo crédito disponible debe
        * reservarse de nuevo y consumirse al tener éxito.
        */
        $gateway->succeedNextCharge(
            'provider-credit-retry-success'
        );

        $retryPayment = app(
            AttemptSubscriptionPayment::class
        )->execute(
            $subscription,
            Carbon::parse(
                '2026-09-27 16:37:22'
            ),
            'credit-retry-success-1',
            isRetry: true,
        );

        $subscription->refresh();
        $credit->refresh();
        $retryPayment->refresh();

        $this->assertSame(
            Payment::STATUS_SUCCEEDED,
            $retryPayment->status
        );

        $this->assertSame(
            60000,
            $retryPayment->gross_amount
        );

        $this->assertSame(
            5000,
            $retryPayment->promotional_credit_amount
        );

        $this->assertSame(
            55000,
            $retryPayment->amount
        );

        $this->assertSame(
            PromotionalCredit::STATUS_CONSUMED,
            $credit->status
        );

        $this->assertSame(
            $retryPayment->id,
            $credit->payment_id
        );

        $this->assertNotNull(
            $credit->consumed_at
        );

        $this->assertSame(
            Subscription::STATUS_ACTIVE,
            $subscription->status
        );
    }

    private function createPromotionalCredit(
        int $tenantId,
        int $amount = 5000,
    ): PromotionalCredit {
        $referrer =
            Tenant::query()
            ->findOrFail(
                $tenantId
            );

        $referred =
            Tenant::create([
                'name' =>
                'Consultorio Referido',

                'slug' =>
                'consultorio-referido-' .
                    uniqid(),

                'status' =>
                'active',

                'onboarding_completed_at' =>
                now(),
            ]);

        $referral =
            Referral::create([
                'referrer_tenant_id' =>
                $referrer->id,

                'referred_tenant_id' =>
                $referred->id,

                'referral_code' =>
                $referrer->referral_code,

                'status' =>
                Referral::STATUS_QUALIFIED,

                'qualified_at' =>
                now(),

                'reward_status' =>
                Referral::REWARD_GRANTED,

                'reward_month' =>
                now()->startOfMonth(),
            ]);

        return PromotionalCredit::withoutGlobalScopes()
            ->create([
                'tenant_id' =>
                $referrer->id,

                'referral_id' =>
                $referral->id,

                'kind' =>
                PromotionalCredit::KIND_REFERRER_REWARD,

                'amount' =>
                $amount,

                'currency' =>
                'MXN',

                'status' =>
                PromotionalCredit::STATUS_AVAILABLE,

                'available_at' =>
                now(),

                'idempotency_key' =>
                'test-credit-' . uniqid(),
            ]);
    }

    private function fakeGateway(): FakePaymentGateway
    {
        $gateway = app(
            PaymentGateway::class
        );

        $this->assertInstanceOf(
            FakePaymentGateway::class,
            $gateway
        );

        return $gateway;
    }

    private function createSubscription(
        ?int $billingAmount = 129900
    ): Subscription {
        $tenant = Tenant::create([
            'name' =>
            'Consultorio Billing',

            'slug' =>
            'consultorio-billing-' .
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
            $billingAmount,

            'billing_currency' =>
            'MXN',

            'status' =>
            Subscription::STATUS_ACTIVE,

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

            'retry_count' =>
            0,

            'cancel_at_period_end' =>
            false,
        ]);
    }

    private function createPastDueSubscription(): Subscription
    {
        $subscription =
            $this->createSubscription();

        $subscription->update([
            'status' =>
            Subscription::STATUS_PAST_DUE,

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
                '2026-09-27 16:37:22'
            ),

            'retry_count' =>
            0,
        ]);

        return $subscription->refresh();
    }

    public function test_successful_initial_payment_renews_subscription_period(): void
    {
        Carbon::setTestNow(
            '2026-09-26 16:37:22'
        );

        $subscription =
            $this->createSubscription();

        $gateway =
            $this->fakeGateway();

        $gateway->succeedNextCharge(
            'provider-renewal-1'
        );

        app(
            AttemptSubscriptionPayment::class
        )->execute(
            $subscription,
            now(),
            'renewal-success-1'
        );

        $subscription->refresh();

        $this->assertTrue(
            $subscription
                ->current_period_starts_at
                ->equalTo(
                    Carbon::parse(
                        '2026-09-26 16:37:22'
                    )
                )
        );

        $this->assertTrue(
            $subscription
                ->current_period_ends_at
                ->equalTo(
                    Carbon::parse(
                        '2026-10-26 16:37:22'
                    )
                )
        );

        $this->assertTrue(
            $subscription
                ->next_billing_at
                ->equalTo(
                    Carbon::parse(
                        '2026-10-26 16:37:22'
                    )
                )
        );
    }

    public function test_failed_initial_payment_does_not_renew_subscription_period(): void
    {
        Carbon::setTestNow(
            '2026-09-26 16:37:22'
        );

        $subscription =
            $this->createSubscription();

        $originalPeriodStart =
            $subscription
            ->current_period_starts_at
            ->copy();

        $originalPeriodEnd =
            $subscription
            ->current_period_ends_at
            ->copy();

        $gateway =
            $this->fakeGateway();

        $gateway->failNextCharge(
            'card_declined',
            'Tarjeta rechazada.'
        );

        app(
            AttemptSubscriptionPayment::class
        )->execute(
            $subscription,
            now(),
            'renewal-failure-1'
        );

        $subscription->refresh();

        $this->assertTrue(
            $subscription
                ->current_period_starts_at
                ->equalTo(
                    $originalPeriodStart
                )
        );

        $this->assertTrue(
            $subscription
                ->current_period_ends_at
                ->equalTo(
                    $originalPeriodEnd
                )
        );

        $this->assertSame(
            Subscription::STATUS_PAST_DUE,
            $subscription->status
        );
    }

    public function test_successful_retry_advances_subscription_only_once(): void
    {
        Carbon::setTestNow(
            '2026-09-27 16:37:22'
        );

        $subscription =
            $this->createPastDueSubscription();

        $gateway =
            $this->fakeGateway();

        $gateway->succeedNextCharge(
            'provider-recovery-once'
        );

        app(
            AttemptSubscriptionPayment::class
        )->execute(
            $subscription,
            now(),
            'recovery-once',
            isRetry: true,
        );

        $subscription->refresh();

        $this->assertSame(
            Subscription::STATUS_ACTIVE,
            $subscription->status
        );

        $this->assertTrue(
            $subscription
                ->current_period_starts_at
                ->equalTo(
                    Carbon::parse(
                        '2026-09-26 16:37:22'
                    )
                )
        );

        $this->assertTrue(
            $subscription
                ->current_period_ends_at
                ->equalTo(
                    Carbon::parse(
                        '2026-10-26 16:37:22'
                    )
                )
        );
    }
}
