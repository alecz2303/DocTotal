<?php

namespace App\Actions\Billing;

use App\Models\PromotionalCredit;
use App\Models\Referral;
use App\Models\Tenant;

class CalculatePaymentAmount
{
    public function execute(
        Tenant $tenant,
        int $grossAmount,
    ): array {
        $referralDiscount = 0;

        if (
            $grossAmount >=
            PromotionalCredit::REFERRAL_REWARD_AMOUNT
        ) {
            $hasPendingReferral =
                Referral::query()
                ->where(
                    'referred_tenant_id',
                    $tenant->id
                )
                ->where(
                    'status',
                    Referral::STATUS_PENDING
                )
                ->exists();

            if ($hasPendingReferral) {
                $referralDiscount =
                    PromotionalCredit::REFERRAL_REWARD_AMOUNT;
            }
        }

        return [
            'gross_amount' =>
            $grossAmount,

            'referral_discount_amount' =>
            $referralDiscount,

            'promotional_credit_amount' =>
            0,

            'amount' =>
            $grossAmount
                - $referralDiscount,
        ];
    }
}
