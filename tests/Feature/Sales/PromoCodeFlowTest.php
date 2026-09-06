<?php

namespace Tests\Feature\Sales;

use App\Actions\Billing\CalculatePaymentAmount;
use App\Actions\Registration\RegisterDoctor;
use App\Actions\Sales\GenerateSalesCommissionFromSuccessfulPayment;
use App\Models\Payment;
use App\Models\PromoCode;
use App\Models\SalesCommission;
use App\Models\SalesPartner;
use App\Models\Tenant;
use App\Models\TenantPromoAttribution;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromoCodeFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_persists_immutable_sales_attribution_snapshots(): void
    {
        [$partner, $promoCode] = $this->createPromoCode(
            discount: 15,
            commission: 20,
        );

        $user = app(RegisterDoctor::class)->handle([
            'practice_name' => 'Consultorio Promoción',
            'first_name' => 'Ana',
            'last_name' => 'Médica',
            'email' => 'ana.promo@example.test',
            'password' => 'Password123!',
            'promo_code' => 'vende20',
        ]);

        $attribution = TenantPromoAttribution::query()
            ->where('tenant_id', $user->tenant_id)
            ->firstOrFail();

        $this->assertSame($promoCode->id, $attribution->promo_code_id);
        $this->assertSame($partner->id, $attribution->sales_partner_id);
        $this->assertSame('VENDE20', $attribution->code_snapshot);
        $this->assertSame('15.00', $attribution->doctor_discount_percent_snapshot);
        $this->assertSame('20.00', $attribution->commission_percent_snapshot);

        $promoCode->update([
            'doctor_discount_percent' => 5,
            'commission_percent' => 8,
        ]);

        $attribution->refresh();

        $this->assertSame('15.00', $attribution->doctor_discount_percent_snapshot);
        $this->assertSame('20.00', $attribution->commission_percent_snapshot);
    }

    public function test_promo_discount_applies_before_first_successful_commission_only(): void
    {
        [, $promoCode] = $this->createPromoCode(
            discount: 10,
            commission: 20,
        );
        $tenant = $this->createAttributedTenant($promoCode);

        $first = app(CalculatePaymentAmount::class)->execute(
            $tenant,
            100000,
        );

        $this->assertSame(10000, $first['promo_code_discount_amount']);
        $this->assertSame(90000, $first['amount']);

        SalesCommission::query()->create([
            'sales_partner_id' => $promoCode->sales_partner_id,
            'promo_code_id' => $promoCode->id,
            'tenant_id' => $tenant->id,
            'payment_id' => $this->createPayment($tenant, Payment::STATUS_SUCCEEDED)->id,
            'status' => SalesCommission::STATUS_ACCRUED,
            'base_amount' => 90000,
            'commission_percent' => 20,
            'commission_amount' => 18000,
            'currency' => 'MXN',
            'accrued_at' => now(),
        ]);

        $next = app(CalculatePaymentAmount::class)->execute(
            $tenant,
            100000,
        );

        $this->assertSame(0, $next['promo_code_discount_amount']);
        $this->assertSame(100000, $next['amount']);
    }

    public function test_commission_is_generated_only_from_successful_payment_and_uses_snapshot(): void
    {
        [, $promoCode] = $this->createPromoCode(
            discount: 10,
            commission: 25,
        );
        $tenant = $this->createAttributedTenant($promoCode);
        $paidAt = Carbon::parse('2026-09-06 15:30:00');

        $failedPayment = $this->createPayment(
            $tenant,
            Payment::STATUS_FAILED,
            amount: 90000,
        );

        $this->assertNull(
            app(GenerateSalesCommissionFromSuccessfulPayment::class)
                ->execute($failedPayment, $paidAt)
        );

        $payment = $this->createPayment(
            $tenant,
            Payment::STATUS_SUCCEEDED,
            amount: 90000,
        );

        $promoCode->update(['commission_percent' => 5]);

        $commission = app(
            GenerateSalesCommissionFromSuccessfulPayment::class
        )->execute($payment, $paidAt);

        $this->assertNotNull($commission);
        $this->assertSame(90000, $commission->base_amount);
        $this->assertSame('25.00', $commission->commission_percent);
        $this->assertSame(22500, $commission->commission_amount);
        $this->assertSame('MXN', $commission->currency);
        $this->assertSame(SalesCommission::STATUS_ACCRUED, $commission->status);

        $secondPayment = $this->createPayment(
            $tenant,
            Payment::STATUS_SUCCEEDED,
            amount: 100000,
        );

        $this->assertNull(
            app(GenerateSalesCommissionFromSuccessfulPayment::class)
                ->execute($secondPayment, $paidAt->copy()->addMonth())
        );
    }

    private function createPromoCode(
        float $discount,
        float $commission,
    ): array {
        $partner = SalesPartner::query()->create([
            'name' => 'Vendedor Uno',
            'active' => true,
        ]);

        $promoCode = PromoCode::query()->create([
            'sales_partner_id' => $partner->id,
            'code' => 'VENDE20',
            'active' => true,
            'doctor_discount_percent' => $discount,
            'commission_percent' => $commission,
            'commission_scope' => PromoCode::SCOPE_FIRST_SUCCESSFUL_PAYMENT,
        ]);

        return [$partner, $promoCode];
    }

    private function createAttributedTenant(PromoCode $promoCode): Tenant
    {
        $tenant = Tenant::query()->create([
            'name' => 'Tenant Atribuido',
            'slug' => 'tenant-atribuido-'.str()->random(6),
            'status' => 'trial',
        ]);

        TenantPromoAttribution::query()->create([
            'tenant_id' => $tenant->id,
            'promo_code_id' => $promoCode->id,
            'sales_partner_id' => $promoCode->sales_partner_id,
            'code_snapshot' => $promoCode->code,
            'doctor_discount_percent_snapshot' => $promoCode->doctor_discount_percent,
            'commission_percent_snapshot' => $promoCode->commission_percent,
            'attributed_at' => now(),
        ]);

        return $tenant;
    }

    private function createPayment(
        Tenant $tenant,
        string $status,
        int $amount = 90000,
    ): Payment {
        return Payment::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'amount' => $amount,
            'gross_amount' => 100000,
            'currency' => 'MXN',
            'status' => $status,
            'billing_cycle' => 'monthly',
            'attempted_at' => now(),
            'paid_at' => $status === Payment::STATUS_SUCCEEDED ? now() : null,
            'failed_at' => $status === Payment::STATUS_FAILED ? now() : null,
            'provider' => 'test',
            'idempotency_key' => (string) str()->uuid(),
        ]);
    }
}
