<?php

namespace App\Actions\Referrals;

use App\Models\Payment;
use App\Models\PromotionalCredit;
use App\Models\Referral;
use App\Models\Tenant;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use LogicException;

class QualifyReferralFromSuccessfulPayment
{
    public function execute(
        Payment $payment,
        CarbonInterface $qualifiedAt,
    ): ?Referral {
        return DB::transaction(
            function () use (
                $payment,
                $qualifiedAt
            ): ?Referral {
                $payment = Payment::query()
                    ->withoutGlobalScopes()
                    ->lockForUpdate()
                    ->findOrFail(
                        $payment->id
                    );

                if (! $payment->isSucceeded()) {
                    throw new LogicException(
                        'La referencia sólo puede calificarse con un pago exitoso.'
                    );
                }

                $referral = Referral::query()
                    ->where(
                        'referred_tenant_id',
                        $payment->tenant_id
                    )
                    ->lockForUpdate()
                    ->first();

                if (! $referral) {
                    return null;
                }

                if ($referral->isQualified()) {
                    return $referral;
                }

                /*
                 * Serializa la concesión de premios por referidor.
                 * Dos referidos del mismo tenant no pueden calcular
                 * simultáneamente el mismo cupo mensual.
                 */
                $referrer = Tenant::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $referral->referrer_tenant_id
                    );

                $rewardMonth = $qualifiedAt
                    ->copy()
                    ->setTimezone(
                        $referrer->timezone
                    )
                    ->startOfMonth()
                    ->toDateString();

                $rewardCount = Referral::query()
                    ->where(
                        'referrer_tenant_id',
                        $referrer->id
                    )
                    ->whereDate(
                        'reward_month',
                        $rewardMonth
                    )
                    ->where(
                        'reward_status',
                        Referral::REWARD_GRANTED
                    )
                    ->count();

                $rewardGranted =
                    $rewardCount
                    < Referral::MONTHLY_REWARD_LIMIT;

                $referral->update([
                    'status' =>
                    Referral::STATUS_QUALIFIED,

                    'qualifying_payment_id' =>
                    $payment->id,

                    'qualified_at' =>
                    $qualifiedAt,

                    'reward_status' =>
                    $rewardGranted
                        ? Referral::REWARD_GRANTED
                        : Referral::REWARD_MONTHLY_CAP_REACHED,

                    'reward_month' =>
                    $rewardMonth,
                ]);

                if ($rewardGranted) {
                    PromotionalCredit::withoutGlobalScopes()
                        ->firstOrCreate(
                            [
                                'idempotency_key' =>
                                sprintf(
                                    'referral:%d:referrer-reward',
                                    $referral->id
                                ),
                            ],
                            [
                                'tenant_id' =>
                                $referrer->id,

                                'referral_id' =>
                                $referral->id,

                                'payment_id' =>
                                null,

                                'kind' =>
                                PromotionalCredit::KIND_REFERRER_REWARD,

                                'amount' =>
                                PromotionalCredit::REFERRAL_REWARD_AMOUNT,

                                'currency' =>
                                $referrer->currency,

                                'status' =>
                                PromotionalCredit::STATUS_AVAILABLE,

                                'available_at' =>
                                $qualifiedAt,
                            ]
                        );
                }

                return $referral->refresh();
            }
        );
    }
}
