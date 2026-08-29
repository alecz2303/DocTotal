<?php

namespace Tests\Feature;

use App\Actions\Billing\AttemptSubscriptionPayment;
use App\Actions\Billing\CalculatePaymentAmount;
use App\Contracts\PaymentGateway;
use App\Models\Payment;
use App\Models\PromotionalCredit;
use App\Models\Referral;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\Billing\FakePaymentGateway;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReferralPaymentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_first_payment_keeps_referral_pending(): void
    {
        [
            $referrer,
            $referred,
            $referral,
            $subscription,
        ] = $this->createReferralScenario();

        $gateway = $this->fakeGateway();

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
            'referral-first-failure'
        );

        $this->assertTrue(
            $payment->isFailed()
        );

        $this->assertSame(
            60000,
            $payment->gross_amount
        );

        $this->assertSame(
            5000,
            $payment->referral_discount_amount
        );

        $this->assertSame(
            55000,
            $payment->amount
        );

        $referral->refresh();

        $this->assertSame(
            Referral::STATUS_PENDING,
            $referral->status
        );

        $this->assertNull(
            $referral->qualifying_payment_id
        );

        $this->assertSame(
            0,
            PromotionalCredit::withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $referrer->id
                )
                ->count()
        );
    }

    public function test_successful_retry_preserves_discount_and_qualifies_referral(): void
    {
        [
            $referrer,
            $referred,
            $referral,
            $subscription,
        ] = $this->createReferralScenario();

        $gateway = $this->fakeGateway();

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
            'referral-recovery-failure'
        );

        $this->assertTrue(
            $failedPayment->isFailed()
        );

        $subscription->refresh();
        $referral->refresh();

        $this->assertSame(
            Subscription::STATUS_PAST_DUE,
            $subscription->status
        );

        $this->assertSame(
            Referral::STATUS_PENDING,
            $referral->status
        );

        $gateway->succeedNextCharge(
            'provider-referral-recovery'
        );

        $successfulPayment = app(
            AttemptSubscriptionPayment::class
        )->execute(
            $subscription,
            Carbon::parse(
                '2026-09-27 16:37:22'
            ),
            'referral-recovery-success',
            isRetry: true,
        );

        $this->assertTrue(
            $successfulPayment->isSucceeded()
        );

        $this->assertSame(
            60000,
            $successfulPayment->gross_amount
        );

        $this->assertSame(
            5000,
            $successfulPayment
                ->referral_discount_amount
        );

        $this->assertSame(
            55000,
            $successfulPayment->amount
        );

        $referral->refresh();

        $this->assertSame(
            Referral::STATUS_QUALIFIED,
            $referral->status
        );

        $this->assertSame(
            $successfulPayment->id,
            $referral->qualifying_payment_id
        );

        $this->assertSame(
            Referral::REWARD_GRANTED,
            $referral->reward_status
        );

        $credits =
            PromotionalCredit::withoutGlobalScopes()
            ->where(
                'tenant_id',
                $referrer->id
            )
            ->get();

        $this->assertCount(
            1,
            $credits
        );

        $this->assertSame(
            5000,
            $credits->first()->amount
        );
    }

    public function test_successful_first_payment_qualifies_referral_immediately(): void
    {
        [
            $referrer,
            $referred,
            $referral,
            $subscription,
        ] = $this->createReferralScenario();

        $gateway = $this->fakeGateway();

        $gateway->succeedNextCharge(
            'provider-referral-success'
        );

        $payment = app(
            AttemptSubscriptionPayment::class
        )->execute(
            $subscription,
            Carbon::parse(
                '2026-09-26 16:37:22'
            ),
            'referral-first-success'
        );

        $this->assertTrue(
            $payment->isSucceeded()
        );

        $this->assertSame(
            60000,
            $payment->gross_amount
        );

        $this->assertSame(
            5000,
            $payment->referral_discount_amount
        );

        $this->assertSame(
            55000,
            $payment->amount
        );

        $referral->refresh();

        $this->assertSame(
            Referral::STATUS_QUALIFIED,
            $referral->status
        );

        $this->assertSame(
            $payment->id,
            $referral->qualifying_payment_id
        );

        $this->assertSame(
            1,
            PromotionalCredit::withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $referrer->id
                )
                ->where(
                    'referral_id',
                    $referral->id
                )
                ->count()
        );
    }

    public function test_qualified_referral_does_not_receive_discount_or_reward_again(): void
    {
        [
            $referrer,
            $referred,
            $referral,
            $subscription,
        ] = $this->createReferralScenario();

        $gateway = $this->fakeGateway();

        $gateway->succeedNextCharge(
            'provider-referral-once'
        );

        $firstPayment = app(
            AttemptSubscriptionPayment::class
        )->execute(
            $subscription,
            Carbon::parse(
                '2026-09-26 16:37:22'
            ),
            'referral-once'
        );

        $referral->refresh();

        $this->assertSame(
            Referral::STATUS_QUALIFIED,
            $referral->status
        );

        $this->assertSame(
            $firstPayment->id,
            $referral->qualifying_payment_id
        );

        $breakdown = app(
            CalculatePaymentAmount::class
        )->execute(
            $referred,
            60000
        );

        $this->assertSame(
            60000,
            $breakdown['gross_amount']
        );

        $this->assertSame(
            0,
            $breakdown['referral_discount_amount']
        );

        $this->assertSame(
            60000,
            $breakdown['amount']
        );

        /*
         * Ejecutar el settlement nuevamente tampoco
         * puede crear otro reward.
         */
        app(
            \App\Actions\Billing\ProcessSuccessfulPaymentPromotions::class
        )->execute(
            $firstPayment,
            $firstPayment->paid_at
        );

        $this->assertSame(
            1,
            PromotionalCredit::withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $referrer->id
                )
                ->where(
                    'referral_id',
                    $referral->id
                )
                ->count()
        );
    }

    private function createReferralScenario(): array
    {
        $referrer = $this->createTenant(
            'Consultorio Referidor'
        );

        $referred = $this->createTenant(
            'Consultorio Referido'
        );

        $referral = Referral::create([
            'referrer_tenant_id' =>
            $referrer->id,

            'referred_tenant_id' =>
            $referred->id,

            'referral_code' =>
            $referrer->referral_code,

            'status' =>
            Referral::STATUS_PENDING,
        ]);

        app(TenantContext::class)->set(
            $referred
        );

        $subscription = Subscription::create([
            'billing_cycle' =>
            Subscription::BILLING_CYCLE_MONTHLY,

            'billing_amount' =>
            60000,

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

        return [
            $referrer,
            $referred,
            $referral,
            $subscription,
        ];
    }

    private function createTenant(
        string $name
    ): Tenant {
        return Tenant::create([
            'name' =>
            $name,

            'slug' =>
            strtolower(
                str_replace(
                    ' ',
                    '-',
                    $name
                )
            )
                . '-'
                . uniqid(),

            'status' =>
            'active',

            'timezone' =>
            'America/Mexico_City',

            'currency' =>
            'MXN',

            'onboarding_completed_at' =>
            now(),
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
}
