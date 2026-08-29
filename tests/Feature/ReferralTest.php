<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Referral;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class ReferralTest extends TestCase
{
    use RefreshDatabase;

    public function test_referral_generates_uuid_automatically(): void
    {
        $referral = $this->createReferral();

        $this->assertNotNull(
            $referral->uuid
        );
    }

    public function test_referral_uses_uuid_as_route_key(): void
    {
        $referral = $this->createReferral();

        $this->assertSame(
            'uuid',
            $referral->getRouteKeyName()
        );
    }

    public function test_referral_defaults_to_pending_status(): void
    {
        $referral = $this->createReferral([
            'status' => null,
        ]);

        $this->assertSame(
            Referral::STATUS_PENDING,
            $referral->status
        );

        $this->assertTrue(
            $referral->isPending()
        );

        $this->assertFalse(
            $referral->isQualified()
        );
    }

    public function test_referral_identifies_qualified_status(): void
    {
        $referral = $this->createReferral([
            'status' => Referral::STATUS_QUALIFIED,
            'qualified_at' => now(),
        ]);

        $this->assertTrue(
            $referral->isQualified()
        );

        $this->assertFalse(
            $referral->isPending()
        );
    }

    public function test_referral_belongs_to_referrer_tenant(): void
    {
        $referral = $this->createReferral();

        $this->assertInstanceOf(
            Tenant::class,
            $referral->referrerTenant
        );

        $this->assertSame(
            $referral->referrer_tenant_id,
            $referral->referrerTenant->id
        );
    }

    public function test_referral_belongs_to_referred_tenant(): void
    {
        $referral = $this->createReferral();

        $this->assertInstanceOf(
            Tenant::class,
            $referral->referredTenant
        );

        $this->assertSame(
            $referral->referred_tenant_id,
            $referral->referredTenant->id
        );
    }

    public function test_tenant_has_referrals_given(): void
    {
        $referral = $this->createReferral();

        $this->assertTrue(
            $referral->referrerTenant
                ->referralsGiven
                ->contains($referral)
        );
    }

    public function test_tenant_has_referral_received(): void
    {
        $referral = $this->createReferral();

        $this->assertNotNull(
            $referral->referredTenant
                ->referralReceived
        );

        $this->assertSame(
            $referral->id,
            $referral->referredTenant
                ->referralReceived
                ->id
        );
    }

    public function test_referred_tenant_can_only_be_attributed_once(): void
    {
        $referral = $this->createReferral();

        $anotherReferrer = $this->createTenant(
            'Otro referidor'
        );

        $this->expectException(
            QueryException::class
        );

        Referral::create([
            'referrer_tenant_id' =>
            $anotherReferrer->id,

            'referred_tenant_id' =>
            $referral->referred_tenant_id,

            'referral_code' =>
            'OTHER123',
        ]);
    }

    public function test_tenant_cannot_refer_itself(): void
    {
        $tenant = $this->createTenant(
            'Autorreferido'
        );

        $this->expectException(
            LogicException::class
        );

        $this->expectExceptionMessage(
            'Un tenant no puede referirse a sí mismo.'
        );

        Referral::create([
            'referrer_tenant_id' =>
            $tenant->id,

            'referred_tenant_id' =>
            $tenant->id,

            'referral_code' =>
            'SELF1234',
        ]);
    }

    public function test_referral_can_reference_qualifying_payment(): void
    {
        $referral = $this->createReferral();

        $payment = $this->createPayment(
            $referral->referredTenant
        );

        $referral->update([
            'status' =>
            Referral::STATUS_QUALIFIED,

            'qualifying_payment_id' =>
            $payment->id,

            'qualified_at' =>
            now(),
        ]);

        $referral->refresh();

        $this->assertInstanceOf(
            Payment::class,
            $referral->qualifyingPayment
        );

        $this->assertSame(
            $payment->id,
            $referral->qualifyingPayment->id
        );
    }

    public function test_qualifying_payment_can_only_qualify_one_referral(): void
    {
        $referralA = $this->createReferral();

        $payment = $this->createPayment(
            $referralA->referredTenant
        );

        $referralA->update([
            'status' =>
            Referral::STATUS_QUALIFIED,

            'qualifying_payment_id' =>
            $payment->id,

            'qualified_at' =>
            now(),
        ]);

        $referralB = $this->createReferral();

        $this->expectException(
            QueryException::class
        );

        $referralB->update([
            'status' =>
            Referral::STATUS_QUALIFIED,

            'qualifying_payment_id' =>
            $payment->id,

            'qualified_at' =>
            now(),
        ]);
    }

    private function createReferral(
        array $attributes = []
    ): Referral {
        $referrer = $this->createTenant(
            'Referidor'
        );

        $referred = $this->createTenant(
            'Referido'
        );

        return Referral::create(
            array_merge([
                'referrer_tenant_id' =>
                $referrer->id,

                'referred_tenant_id' =>
                $referred->id,

                'referral_code' =>
                'REF12345',

                'status' =>
                Referral::STATUS_PENDING,
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

    private function createPayment(
        Tenant $tenant
    ): Payment {
        app(TenantContext::class)->set(
            $tenant
        );

        $subscription = Subscription::create([
            'billing_cycle' =>
            Subscription::BILLING_CYCLE_MONTHLY,

            'status' =>
            Subscription::STATUS_ACTIVE,

            'starts_at' =>
            now()->subMonth(),

            'current_period_starts_at' =>
            now()->subMonth(),

            'current_period_ends_at' =>
            now(),

            'next_billing_at' =>
            now(),

            'retry_count' =>
            0,

            'cancel_at_period_end' =>
            false,

            'billing_amount' =>
            60000,

            'billing_currency' =>
            'MXN',
        ]);

        return Payment::create([
            'subscription_id' =>
            $subscription->id,

            'billing_cycle' =>
            Subscription::BILLING_CYCLE_MONTHLY,

            'amount' =>
            55000,

            'currency' =>
            'MXN',

            'status' =>
            Payment::STATUS_SUCCEEDED,

            'attempted_at' =>
            now(),

            'paid_at' =>
            now(),

            'idempotency_key' =>
            'referral-payment-' . uniqid(),
        ]);
    }

    public function test_referral_reward_tracking_is_nullable_while_pending(): void
    {
        $referral = $this->createReferral();

        $this->assertNull(
            $referral->reward_status
        );

        $this->assertNull(
            $referral->reward_month
        );
    }

    public function test_referral_casts_reward_month_as_date(): void
    {
        $referral = $this->createReferral();

        $referral->update([
            'reward_status' =>
            Referral::REWARD_GRANTED,

            'reward_month' =>
            '2026-08-01',
        ]);

        $referral->refresh();

        $this->assertSame(
            '2026-08-01',
            $referral->reward_month
                ->toDateString()
        );
    }
}
