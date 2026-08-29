<?php

namespace Tests\Feature;

use App\Actions\Billing\ReservePromotionalCredits;
use App\Models\Payment;
use App\Models\PromotionalCredit;
use App\Models\Referral;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class ReservePromotionalCreditsTest extends TestCase
{
    use RefreshDatabase;

    public function test_available_credits_are_reserved_and_reduce_payment_amount(): void
    {
        [$tenant, $payment] =
            $this->createScenario();

        $this->createCredit(
            $tenant,
            5000
        );

        $this->createCredit(
            $tenant,
            5000
        );

        $this->createCredit(
            $tenant,
            5000
        );

        $payment = app(
            ReservePromotionalCredits::class
        )->execute(
            $payment
        );

        $this->assertSame(
            15000,
            $payment
                ->promotional_credit_amount
        );

        $this->assertSame(
            45000,
            $payment->amount
        );

        $this->assertSame(
            3,
            PromotionalCredit::withoutGlobalScopes()
                ->where(
                    'payment_id',
                    $payment->id
                )
                ->where(
                    'status',
                    PromotionalCredit::STATUS_RESERVED
                )
                ->count()
        );
    }

    public function test_reservation_preserves_gross_and_referral_discount(): void
    {
        [$tenant, $payment] =
            $this->createScenario(
                referralDiscount: 5000
            );

        $this->createCredit(
            $tenant,
            5000
        );

        $payment = app(
            ReservePromotionalCredits::class
        )->execute(
            $payment
        );

        $this->assertSame(
            60000,
            $payment->gross_amount
        );

        $this->assertSame(
            5000,
            $payment
                ->referral_discount_amount
        );

        $this->assertSame(
            5000,
            $payment
                ->promotional_credit_amount
        );

        $this->assertSame(
            50000,
            $payment->amount
        );
    }

    public function test_credit_that_does_not_fit_remains_available(): void
    {
        [$tenant, $payment] =
            $this->createScenario(
                grossAmount: 3000
            );

        $credit =
            $this->createCredit(
                $tenant,
                5000
            );

        $payment = app(
            ReservePromotionalCredits::class
        )->execute(
            $payment
        );

        $this->assertSame(
            0,
            $payment
                ->promotional_credit_amount
        );

        $this->assertSame(
            3000,
            $payment->amount
        );

        $credit->refresh();

        $this->assertTrue(
            $credit->isAvailable()
        );

        $this->assertNull(
            $credit->payment_id
        );
    }

    public function test_credits_from_another_tenant_are_not_reserved(): void
    {
        [$tenant, $payment] =
            $this->createScenario();

        $otherTenant =
            $this->createTenant(
                'Otro tenant'
            );

        $this->createCredit(
            $otherTenant,
            5000
        );

        app(TenantContext::class)->set(
            $tenant
        );

        $payment = app(
            ReservePromotionalCredits::class
        )->execute(
            $payment
        );

        $this->assertSame(
            0,
            $payment
                ->promotional_credit_amount
        );

        $this->assertSame(
            60000,
            $payment->amount
        );
    }

    public function test_same_payment_reservation_is_idempotent(): void
    {
        [$tenant, $payment] =
            $this->createScenario();

        $this->createCredit(
            $tenant,
            5000
        );

        $first = app(
            ReservePromotionalCredits::class
        )->execute(
            $payment
        );

        $second = app(
            ReservePromotionalCredits::class
        )->execute(
            $first
        );

        $this->assertSame(
            5000,
            $second
                ->promotional_credit_amount
        );

        $this->assertSame(
            55000,
            $second->amount
        );

        $this->assertSame(
            1,
            PromotionalCredit::withoutGlobalScopes()
                ->where(
                    'payment_id',
                    $payment->id
                )
                ->where(
                    'status',
                    PromotionalCredit::STATUS_RESERVED
                )
                ->count()
        );
    }

    public function test_succeeded_payment_cannot_reserve_credits(): void
    {
        [$tenant, $payment] =
            $this->createScenario();

        $payment->update([
            'status' =>
            Payment::STATUS_SUCCEEDED,

            'paid_at' =>
            now(),
        ]);

        $this->createCredit(
            $tenant,
            5000
        );

        $this->expectException(
            LogicException::class
        );

        app(
            ReservePromotionalCredits::class
        )->execute(
            $payment
        );
    }

    public function test_only_credits_that_fit_are_reserved(): void
    {
        [$tenant, $payment] =
            $this->createScenario(
                grossAmount: 12000
            );

        $this->createCredit(
            $tenant,
            5000
        );

        $this->createCredit(
            $tenant,
            5000
        );

        $third =
            $this->createCredit(
                $tenant,
                5000
            );

        $payment = app(
            ReservePromotionalCredits::class
        )->execute(
            $payment
        );

        $this->assertSame(
            10000,
            $payment
                ->promotional_credit_amount
        );

        $this->assertSame(
            2000,
            $payment->amount
        );

        $third->refresh();

        $this->assertTrue(
            $third->isAvailable()
        );
    }

    public function test_credits_cannot_reduce_payment_to_zero(): void
    {
        [$tenant, $payment] =
            $this->createScenario(
                grossAmount: 10000
            );

        $this->createCredit(
            $tenant,
            5000
        );

        $secondCredit =
            $this->createCredit(
                $tenant,
                5000
            );

        $payment = app(
            ReservePromotionalCredits::class
        )->execute(
            $payment
        );

        $this->assertSame(
            5000,
            $payment
                ->promotional_credit_amount
        );

        $this->assertSame(
            5000,
            $payment->amount
        );

        $this->assertSame(
            1,
            PromotionalCredit::withoutGlobalScopes()
                ->where(
                    'payment_id',
                    $payment->id
                )
                ->where(
                    'status',
                    PromotionalCredit::STATUS_RESERVED
                )
                ->count()
        );

        $secondCredit->refresh();

        $this->assertTrue(
            $secondCredit->isAvailable()
        );

        $this->assertNull(
            $secondCredit->payment_id
        );
    }

    private function createScenario(
        int $grossAmount = 60000,
        int $referralDiscount = 0,
    ): array {
        $tenant =
            $this->createTenant(
                'Tenant Billing'
            );

        app(TenantContext::class)->set(
            $tenant
        );

        $subscription =
            Subscription::create([
                'billing_cycle' =>
                Subscription::BILLING_CYCLE_MONTHLY,

                'billing_amount' =>
                $grossAmount,

                'billing_currency' =>
                'MXN',

                'status' =>
                Subscription::STATUS_ACTIVE,

                'starts_at' =>
                now(),

                'current_period_starts_at' =>
                now(),

                'current_period_ends_at' =>
                now()->addMonth(),

                'next_billing_at' =>
                now()->addMonth(),

                'retry_count' =>
                0,

                'cancel_at_period_end' =>
                false,
            ]);

        $payment =
            Payment::create([
                'tenant_id' =>
                $tenant->id,

                'subscription_id' =>
                $subscription->id,

                'gross_amount' =>
                $grossAmount,

                'referral_discount_amount' =>
                $referralDiscount,

                'promotional_credit_amount' =>
                0,

                'amount' =>
                $grossAmount
                    - $referralDiscount,

                'currency' =>
                'MXN',

                'status' =>
                Payment::STATUS_PENDING,

                'attempted_at' =>
                now(),

                'provider' =>
                'stripe',

                'billing_cycle' =>
                Subscription::BILLING_CYCLE_MONTHLY,

                'idempotency_key' =>
                'reserve-payment-'
                    . uniqid(),
            ]);

        return [
            $tenant,
            $payment,
        ];
    }

    private function createCredit(
        Tenant $tenant,
        int $amount
    ): PromotionalCredit {
        $referred =
            $this->createTenant(
                'Referido'
            );

        $referral =
            Referral::create([
                'referrer_tenant_id' =>
                $tenant->id,

                'referred_tenant_id' =>
                $referred->id,

                'referral_code' =>
                $tenant->referral_code,
            ]);

        app(TenantContext::class)->set(
            $tenant
        );

        return PromotionalCredit::create([
            'tenant_id' =>
            $tenant->id,

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
            'reserve-credit-'
                . uniqid(),
        ]);
    }

    private function createTenant(
        string $name
    ): Tenant {
        return Tenant::create([
            'name' =>
            $name,

            'slug' =>
            str($name)->slug()
                . '-'
                . uniqid(),

            'status' =>
            'active',

            'currency' =>
            'MXN',

            'onboarding_completed_at' =>
            now(),
        ]);
    }
}
