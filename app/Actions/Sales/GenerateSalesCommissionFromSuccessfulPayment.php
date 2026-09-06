<?php

namespace App\Actions\Sales;

use App\Models\Payment;
use App\Models\PromoCode;
use App\Models\SalesCommission;
use App\Models\TenantPromoAttribution;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class GenerateSalesCommissionFromSuccessfulPayment
{
    public function execute(
        Payment $payment,
        CarbonInterface $paidAt,
    ): ?SalesCommission {
        if (! $payment->isSucceeded()) {
            return null;
        }

        return DB::transaction(function () use ($payment, $paidAt) {
            $existing = SalesCommission::query()
                ->where('payment_id', $payment->id)
                ->first();

            if ($existing) {
                return $existing;
            }

            $attribution = TenantPromoAttribution::query()
                ->where('tenant_id', $payment->tenant_id)
                ->first();

            if (! $attribution) {
                return null;
            }

            $promoCode = $attribution->promoCode()->first();

            if (
                ! $promoCode
                || $promoCode->commission_scope
                    !== PromoCode::SCOPE_FIRST_SUCCESSFUL_PAYMENT
            ) {
                return null;
            }

            if (
                SalesCommission::query()
                    ->where('tenant_id', $payment->tenant_id)
                    ->exists()
            ) {
                return null;
            }

            $percent = (float) $attribution
                ->commission_percent_snapshot;

            if ($percent <= 0) {
                return null;
            }

            $baseAmount = max(0, (int) $payment->amount);
            $commissionAmount = (int) round(
                $baseAmount * $percent / 100
            );

            if ($commissionAmount <= 0) {
                return null;
            }

            return SalesCommission::query()->create([
                'sales_partner_id' => $attribution->sales_partner_id,
                'promo_code_id' => $attribution->promo_code_id,
                'tenant_id' => $payment->tenant_id,
                'payment_id' => $payment->id,
                'status' => SalesCommission::STATUS_ACCRUED,
                'base_amount' => $baseAmount,
                'commission_percent' => $attribution
                    ->commission_percent_snapshot,
                'commission_amount' => $commissionAmount,
                'currency' => strtoupper($payment->currency),
                'accrued_at' => $paidAt,
            ]);
        });
    }
}
