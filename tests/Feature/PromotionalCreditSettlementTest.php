<?php

namespace Tests\Feature;

use App\Actions\Billing\ConsumeReservedPromotionalCredits;
use App\Actions\Billing\ReleaseReservedPromotionalCredits;
use App\Actions\Billing\ReservePromotionalCredits;
use App\Models\Payment;
use App\Models\PromotionalCredit;
use App\Models\Referral;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use LogicException;
use Tests\TestCase;

class PromotionalCreditSettlementTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_payment_consumes_reserved_credits(): void
    {
        [$tenant, $payment] =
            $this->createScenario();

        $credit = $this->createCredit(
            $tenant,
            5000
        );

        $payment = app(
            ReservePromotionalCredits::class
        )->execute(
            $payment
        );

        $paidAt = Carbon::parse(
            '2026-09-27 12:00:00'
        );

        $payment->succeed(
            $paidAt,
            'provider-success'
        );

        app(
            ConsumeReservedPromotionalCredits::class
        )->execute(
            $payment,
            $paidAt
        );

        $credit->refresh();

        $this->assertTrue(
            $credit->isConsumed()
        );

        $this->assertSame(
            $payment->id,
            $credit->payment_id
        );

        $this->assertTrue(
            $credit->consumed_at->equalTo(
                $paidAt
            )
        );

        $this->assertSame(
            5000,
            $payment->refresh()
                ->promotional_credit_amount
        );
    }

    public function test_failed_payment_releases_reserved_credits_but_preserves_payment_history(): void
    {
        [$tenant, $payment] =
            $this->createScenario();

        $credit = $this->createCredit(
            $tenant,
            5000
        );

        $payment = app(
            ReservePromotionalCredits::class
        )->execute(
            $payment
        );

        $this->assertSame(
            55000,
            $payment->amount
        );

        $releasedAt = Carbon::parse(
            '2026-09-27 12:00:00'
        );

        $payment->fail(
            $releasedAt,
            'card_declined',
            'Tarjeta rechazada.'
        );

        app(
            ReleaseReservedPromotionalCredits::class
        )->execute(
            $payment,
            $releasedAt
        );

        /*
        * La acción vuelve a cargar y actualiza
        * su propia instancia de Payment.
        */
        $credit->refresh();
        $payment->refresh();

        $this->assertTrue(
            $credit->isAvailable()
        );

        $this->assertNull(
            $credit->payment_id
        );

        $this->assertNull(
            $credit->consumed_at
        );

        /*
        * El Payment fallido conserva el descuento
        * que realmente se intentó utilizar.
        */
        $this->assertSame(
            5000,
            $payment
                ->promotional_credit_amount
        );

        $this->assertSame(
            55000,
            $payment->amount
        );

        /*
        * Y queda auditado cuándo se liberó
        * la reserva.
        */
        $this->assertNotNull(
            $payment
                ->promotional_credits_released_at
        );

        $this->assertTrue(
            $payment
                ->promotional_credits_released_at
                ->equalTo(
                    $releasedAt
                )
        );
    }

    public function test_pending_payment_cannot_consume_reserved_credits(): void
    {
        [$tenant, $payment] =
            $this->createScenario();

        $this->createCredit(
            $tenant,
            5000
        );

        $payment = app(
            ReservePromotionalCredits::class
        )->execute(
            $payment
        );

        $this->expectException(
            LogicException::class
        );

        app(
            ConsumeReservedPromotionalCredits::class
        )->execute(
            $payment,
            Carbon::parse(
                '2026-09-27 12:00:00'
            )
        );
    }

    public function test_non_failed_payment_cannot_release_reserved_credits(): void
    {
        [$tenant, $payment] =
            $this->createScenario();

        $this->createCredit(
            $tenant,
            5000
        );

        $payment = app(
            ReservePromotionalCredits::class
        )->execute(
            $payment
        );

        $releasedAt = Carbon::parse(
            '2026-09-27 12:00:00'
        );

        $this->expectException(
            LogicException::class
        );

        app(
            ReleaseReservedPromotionalCredits::class
        )->execute(
            $payment,
            $releasedAt
        );
    }


    public function test_consuming_same_payment_twice_is_idempotent(): void
    {
        [$tenant, $payment] =
            $this->createScenario();

        $this->createCredit(
            $tenant,
            5000
        );

        $payment = app(
            ReservePromotionalCredits::class
        )->execute(
            $payment
        );

        $paidAt = Carbon::parse(
            '2026-09-27 12:00:00'
        );

        $payment->succeed(
            $paidAt,
            'provider-success'
        );

        $action = app(
            ConsumeReservedPromotionalCredits::class
        );

        $action->execute(
            $payment,
            $paidAt
        );

        $action->execute(
            $payment,
            $paidAt
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
                    PromotionalCredit::STATUS_CONSUMED
                )
                ->count()
        );

        $this->assertSame(
            5000,
            PromotionalCredit::withoutGlobalScopes()
                ->where(
                    'payment_id',
                    $payment->id
                )
                ->where(
                    'status',
                    PromotionalCredit::STATUS_CONSUMED
                )
                ->sum('amount')
        );
    }

    public function test_releasing_same_payment_twice_is_idempotent(): void
    {
        [$tenant, $payment] =
            $this->createScenario();

        $credit = $this->createCredit(
            $tenant,
            5000
        );

        $payment = app(
            ReservePromotionalCredits::class
        )->execute(
            $payment
        );

        $releasedAt = Carbon::parse(
            '2026-09-27 12:00:00'
        );

        $payment->fail(
            $releasedAt,
            'card_declined',
            'Tarjeta rechazada.'
        );

        $action = app(
            ReleaseReservedPromotionalCredits::class
        );

        $action->execute(
            $payment,
            $releasedAt
        );

        $action->execute(
            $payment,
            $releasedAt->copy()->addMinute()
        );

        $credit->refresh();
        $payment->refresh();

        $this->assertTrue(
            $credit->isAvailable()
        );

        $this->assertNull(
            $credit->payment_id
        );

        /*
        * La segunda ejecución no modifica
        * el instante original del settlement.
        */
        $this->assertTrue(
            $payment
                ->promotional_credits_released_at
                ->equalTo(
                    $releasedAt
                )
        );

        $this->assertSame(
            5000,
            $payment
                ->promotional_credit_amount
        );

        $this->assertSame(
            55000,
            $payment->amount
        );
    }

    public function test_failed_payment_with_missing_reservation_cannot_be_marked_as_released(): void
    {
        [$tenant, $payment] =
            $this->createScenario();

        $payment->update([
            'promotional_credit_amount' =>
            5000,

            'amount' =>
            55000,
        ]);

        $failedAt = Carbon::parse(
            '2026-09-27 12:00:00'
        );

        $payment->fail(
            $failedAt,
            'card_declined',
            'Tarjeta rechazada.'
        );

        $this->expectException(
            LogicException::class
        );

        app(
            ReleaseReservedPromotionalCredits::class
        )->execute(
            $payment,
            $failedAt
        );
    }

    private function createScenario(): array
    {
        $tenant = $this->createTenant(
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
                60000,

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

        $payment = Payment::create([
            'tenant_id' =>
            $tenant->id,

            'subscription_id' =>
            $subscription->id,

            'gross_amount' =>
            60000,

            'referral_discount_amount' =>
            0,

            'promotional_credit_amount' =>
            0,

            'amount' =>
            60000,

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
            'settlement-payment-'
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
        $referred = $this->createTenant(
            'Referido'
        );

        $referral = Referral::create([
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
            'settlement-credit-'
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
