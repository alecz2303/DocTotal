<?php

namespace App\Actions\Billing;

use App\Models\Payment;
use App\Models\PromotionalCredit;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use LogicException;

class ReservePromotionalCredits
{
    public function execute(
        Payment $payment
    ): Payment {
        return DB::transaction(
            function () use ($payment): Payment {
                $payment =
                    Payment::withoutGlobalScopes()
                    ->lockForUpdate()
                    ->findOrFail(
                        $payment->id
                    );

                if (! $payment->isPending()) {
                    throw new LogicException(
                        'Sólo un pago pendiente puede reservar créditos promocionales.'
                    );
                }

                Tenant::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $payment->tenant_id
                    );

                $grossAmount =
                    $payment->contractualAmount();

                $referralDiscount =
                    $payment->referral_discount_amount;

                $promoCodeDiscount =
                    $payment->promo_code_discount_amount;

                if ($promoCodeDiscount === 0) {
                    $inferredPromoDiscount = max(
                        0,
                        $grossAmount
                        - $referralDiscount
                        - $payment->promotional_credit_amount
                        - $payment->amount
                    );

                    $promoCodeDiscount = $inferredPromoDiscount;
                }

                $chargeableBeforeCredits =
                    $grossAmount
                    - $referralDiscount
                    - $promoCodeDiscount;

                if ($chargeableBeforeCredits < 0) {
                    throw new LogicException(
                        'Los descuentos superan el importe contractual del pago.'
                    );
                }

                $reservedAmount =
                    PromotionalCredit::withoutGlobalScopes()
                    ->where(
                        'tenant_id',
                        $payment->tenant_id
                    )
                    ->where(
                        'payment_id',
                        $payment->id
                    )
                    ->where(
                        'status',
                        PromotionalCredit::STATUS_RESERVED
                    )
                    ->sum('amount');

                if (
                    $reservedAmount >
                    $chargeableBeforeCredits
                ) {
                    throw new LogicException(
                        'Los créditos reservados superan el importe disponible del pago.'
                    );
                }

                if ($reservedAmount === 0) {
                    $availableCredits =
                        PromotionalCredit::withoutGlobalScopes()
                        ->where(
                            'tenant_id',
                            $payment->tenant_id
                        )
                        ->where(
                            'kind',
                            PromotionalCredit::KIND_REFERRER_REWARD
                        )
                        ->where(
                            'status',
                            PromotionalCredit::STATUS_AVAILABLE
                        )
                        ->whereRaw(
                            'UPPER(currency) = ?',
                            [
                                strtoupper(
                                    $payment->currency
                                ),
                            ]
                        )
                        ->orderBy('available_at')
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get();

                    foreach (
                        $availableCredits
                        as $credit
                    ) {
                        if (
                            $reservedAmount
                            + $credit->amount
                            >=
                            $chargeableBeforeCredits
                        ) {
                            continue;
                        }

                        $credit->reserve(
                            $payment
                        );

                        $reservedAmount +=
                            $credit->amount;
                    }
                }

                $payment->update([
                    'promo_code_discount_amount' =>
                    $promoCodeDiscount,

                    'promotional_credit_amount' =>
                    $reservedAmount,

                    'amount' =>
                    $chargeableBeforeCredits
                        - $reservedAmount,
                ]);

                return $payment->refresh();
            }
        );
    }
}
