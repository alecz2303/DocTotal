<?php

namespace App\Actions\Billing;

use App\Models\Payment;
use App\Models\PromotionalCredit;
use App\Models\Referral;
use App\Models\Tenant;
use App\Models\TenantPromoAttribution;

class CalculatePaymentAmount
{
    public function execute(
        Tenant $tenant,
        int $grossAmount,
    ): array {
        $referralDiscount = 0;
        $promoCodeDiscount = 0;

        if (
            $grossAmount >=
            PromotionalCredit::REFERRAL_REWARD_AMOUNT
        ) {
            $hasPendingReferral = Referral::query()
                ->where('referred_tenant_id', $tenant->id)
                ->where('status', Referral::STATUS_PENDING)
                ->exists();

            if ($hasPendingReferral) {
                $referralDiscount =
                    PromotionalCredit::REFERRAL_REWARD_AMOUNT;
            }
        }

        $attribution = TenantPromoAttribution::query()
            ->where('tenant_id', $tenant->id)
            ->first();

        $alreadyConverted = $attribution
            ? Payment::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('status', Payment::STATUS_SUCCEEDED)
                ->where('paid_at', '>=', $attribution->attributed_at)
                ->exists()
            : false;

        if ($attribution && ! $alreadyConverted) {
            $promoCodeDiscount = (int) round(
                $grossAmount
                * (float) $attribution
                    ->doctor_discount_percent_snapshot
                / 100
            );
        }

        $promoCodeDiscount = min(
            $promoCodeDiscount,
            max(0, $grossAmount - $referralDiscount)
        );

        return [
            'gross_amount' => $grossAmount,
            'referral_discount_amount' => $referralDiscount,
            'promo_code_discount_amount' => $promoCodeDiscount,
            'promotional_credit_amount' => 0,
            'amount' =>
                $grossAmount
                - $referralDiscount
                - $promoCodeDiscount,
        ];
    }
}
