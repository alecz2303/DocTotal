<?php

namespace Tests\Feature;

use App\Actions\Billing\CalculatePaymentAmount;
use App\Models\Referral;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentAmountCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_without_referral_has_no_discount(): void
    {
        $tenant = $this->createTenant(
            'Tenant Normal'
        );

        $result = app(
            CalculatePaymentAmount::class
        )->execute(
            $tenant,
            60000
        );

        $this->assertSame(
            60000,
            $result['gross_amount']
        );

        $this->assertSame(
            0,
            $result['referral_discount_amount']
        );

        $this->assertSame(
            0,
            $result['promotional_credit_amount']
        );

        $this->assertSame(
            60000,
            $result['amount']
        );
    }

    public function test_pending_referral_receives_fifty_peso_discount(): void
    {
        [$referrer, $referred] =
            $this->createReferral();

        $result = app(
            CalculatePaymentAmount::class
        )->execute(
            $referred,
            60000
        );

        $this->assertSame(
            60000,
            $result['gross_amount']
        );

        $this->assertSame(
            5000,
            $result['referral_discount_amount']
        );

        $this->assertSame(
            55000,
            $result['amount']
        );
    }

    public function test_annual_plan_receives_same_fifty_peso_discount(): void
    {
        [$referrer, $referred] =
            $this->createReferral();

        $result = app(
            CalculatePaymentAmount::class
        )->execute(
            $referred,
            600000
        );

        $this->assertSame(
            600000,
            $result['gross_amount']
        );

        $this->assertSame(
            5000,
            $result['referral_discount_amount']
        );

        $this->assertSame(
            595000,
            $result['amount']
        );
    }

    public function test_qualified_referral_does_not_receive_discount_again(): void
    {
        [$referrer, $referred, $referral] =
            $this->createReferral();

        $referral->update([
            'status' =>
            Referral::STATUS_QUALIFIED,

            'qualified_at' =>
            now(),
        ]);

        $result = app(
            CalculatePaymentAmount::class
        )->execute(
            $referred,
            60000
        );

        $this->assertSame(
            0,
            $result['referral_discount_amount']
        );

        $this->assertSame(
            60000,
            $result['amount']
        );
    }

    private function createReferral(): array
    {
        $referrer =
            $this->createTenant(
                'Tenant Referidor'
            );

        $referred =
            $this->createTenant(
                'Tenant Referido'
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

        return [
            $referrer,
            $referred,
            $referral,
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
            ),
        ]);
    }
}
