<?php

namespace Tests\Feature;

use App\Models\PromotionalCredit;
use App\Models\Referral;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use App\Models\Payment;
use App\Models\Subscription;

class PromotionalCreditTest extends TestCase
{
    use RefreshDatabase;

    public function test_promotional_credit_generates_uuid_automatically(): void
    {
        $credit = $this->createCredit();

        $this->assertNotNull(
            $credit->uuid
        );
    }

    public function test_promotional_credit_uses_uuid_as_route_key(): void
    {
        $credit = $this->createCredit();

        $this->assertSame(
            'uuid',
            $credit->getRouteKeyName()
        );
    }

    public function test_promotional_credit_defaults_to_available_status(): void
    {
        $credit = $this->createCredit([
            'status' => null,
        ]);

        $this->assertSame(
            PromotionalCredit::STATUS_AVAILABLE,
            $credit->status
        );

        $this->assertTrue(
            $credit->isAvailable()
        );

        $this->assertFalse(
            $credit->isConsumed()
        );
    }

    public function test_promotional_credit_identifies_consumed_status(): void
    {
        $credit = $this->createCredit([
            'status' =>
            PromotionalCredit::STATUS_CONSUMED,

            'consumed_at' =>
            now(),
        ]);

        $this->assertTrue(
            $credit->isConsumed()
        );

        $this->assertFalse(
            $credit->isAvailable()
        );
    }

    public function test_referral_reward_is_fifty_mxn_in_minor_units(): void
    {
        $this->assertSame(
            5000,
            PromotionalCredit::REFERRAL_REWARD_AMOUNT
        );
    }

    public function test_promotional_credit_amount_is_cast_to_integer(): void
    {
        $credit = $this->createCredit([
            'amount' => 5000,
        ]);

        $this->assertIsInt(
            $credit->amount
        );

        $this->assertSame(
            5000,
            $credit->amount
        );
    }

    public function test_promotional_credit_dates_are_cast_to_datetime(): void
    {
        $credit = $this->createCredit([
            'available_at' =>
            '2026-08-28 12:00:00',

            'consumed_at' =>
            '2026-09-01 12:00:00',
        ]);

        $this->assertInstanceOf(
            Carbon::class,
            $credit->available_at
        );

        $this->assertInstanceOf(
            Carbon::class,
            $credit->consumed_at
        );
    }

    public function test_promotional_credit_belongs_to_tenant(): void
    {
        $credit = $this->createCredit();

        $this->assertInstanceOf(
            Tenant::class,
            $credit->tenant
        );

        $this->assertSame(
            $credit->tenant_id,
            $credit->tenant->id
        );
    }

    public function test_promotional_credit_belongs_to_referral(): void
    {
        $credit = $this->createCredit();

        $this->assertInstanceOf(
            Referral::class,
            $credit->referral
        );

        $this->assertSame(
            $credit->referral_id,
            $credit->referral->id
        );
    }

    public function test_tenant_has_promotional_credits(): void
    {
        $credit = $this->createCredit();

        $this->assertTrue(
            $credit->tenant
                ->promotionalCredits
                ->contains($credit)
        );
    }

    public function test_referral_has_promotional_credits(): void
    {
        $credit = $this->createCredit();

        $this->assertTrue(
            $credit->referral
                ->promotionalCredits
                ->contains($credit)
        );
    }

    public function test_promotional_credit_idempotency_key_must_be_unique(): void
    {
        $credit = $this->createCredit([
            'idempotency_key' =>
            'referral-reward-123',
        ]);

        $this->expectException(
            QueryException::class
        );

        PromotionalCredit::create([
            'tenant_id' =>
            $credit->tenant_id,

            'referral_id' =>
            $credit->referral_id,

            'kind' =>
            PromotionalCredit::KIND_REFERRER_REWARD,

            'amount' =>
            5000,

            'currency' =>
            'MXN',

            'available_at' =>
            now(),

            'idempotency_key' =>
            'referral-reward-123',
        ]);
    }

    public function test_promotional_credits_are_isolated_by_tenant(): void
    {
        $credit = $this->createCredit();

        $tenantB = $this->createTenant(
            'Tenant B'
        );

        app(TenantContext::class)->set(
            $tenantB
        );

        $this->assertNull(
            PromotionalCredit::find(
                $credit->id
            )
        );
    }

    private function createCredit(
        array $attributes = []
    ): PromotionalCredit {
        $referrer = $this->createTenant(
            'Referidor'
        );

        $referred = $this->createTenant(
            'Referido'
        );

        $referral = Referral::create([
            'referrer_tenant_id' =>
            $referrer->id,

            'referred_tenant_id' =>
            $referred->id,

            'referral_code' =>
            'REF12345',
        ]);

        app(TenantContext::class)->set(
            $referrer
        );

        return PromotionalCredit::create(
            array_merge([
                'tenant_id' =>
                $referrer->id,

                'referral_id' =>
                $referral->id,

                'kind' =>
                PromotionalCredit::KIND_REFERRER_REWARD,

                'amount' =>
                PromotionalCredit::REFERRAL_REWARD_AMOUNT,

                'currency' =>
                'MXN',

                'status' =>
                PromotionalCredit::STATUS_AVAILABLE,

                'available_at' =>
                now(),

                'idempotency_key' =>
                'referral-credit-' . uniqid(),
            ], $attributes)
        );
    }

    private function createTenant(
        string $name
    ): Tenant {
        return Tenant::create([
            'name' => $name,
            'slug' =>
            str($name)->slug()
                . '-'
                . uniqid(),
            'status' => 'active',
            'onboarding_completed_at' => now(),
        ]);
    }

    public function test_available_credit_can_be_reserved_for_payment(): void
    {
        [$credit, $payment] =
            $this->createCreditAndPayment();

        $credit->reserve($payment);

        $credit->refresh();

        $this->assertSame(
            PromotionalCredit::STATUS_RESERVED,
            $credit->status
        );

        $this->assertSame(
            $payment->id,
            $credit->payment_id
        );

        $this->assertTrue(
            $credit->isReserved()
        );
    }

    public function test_reserved_credit_can_be_consumed(): void
    {
        [$credit, $payment] =
            $this->createCreditAndPayment();

        $credit->reserve($payment);

        $consumedAt = Carbon::parse(
            '2026-09-27 12:00:00'
        );

        $credit->consume(
            $consumedAt
        );

        $credit->refresh();

        $this->assertSame(
            PromotionalCredit::STATUS_CONSUMED,
            $credit->status
        );

        $this->assertSame(
            $payment->id,
            $credit->payment_id
        );

        $this->assertTrue(
            $credit->consumed_at->equalTo(
                $consumedAt
            )
        );
    }

    public function test_reserved_credit_can_be_released(): void
    {
        [$credit, $payment] =
            $this->createCreditAndPayment();

        $credit->reserve($payment);
        $credit->release();

        $credit->refresh();

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
    }

    public function test_available_credit_cannot_be_consumed_directly(): void
    {
        [$credit] =
            $this->createCreditAndPayment();

        $this->expectException(
            \LogicException::class
        );

        $credit->consume(
            now()
        );
    }

    public function test_consumed_credit_cannot_be_released(): void
    {
        [$credit, $payment] =
            $this->createCreditAndPayment();

        $credit->reserve($payment);
        $credit->consume(now());

        $this->expectException(
            \LogicException::class
        );

        $credit->release();
    }

    private function createCreditAndPayment(): array
    {
        $credit = $this->createCredit();

        $tenant = Tenant::withoutGlobalScopes()
            ->findOrFail(
                $credit->tenant_id
            );

        app(TenantContext::class)->set(
            $tenant
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

            'idempotency_key' =>
            'promo-reservation-' . uniqid(),

            'billing_cycle' =>
            Subscription::BILLING_CYCLE_MONTHLY,
        ]);

        return [
            $credit,
            $payment,
        ];
    }
}
