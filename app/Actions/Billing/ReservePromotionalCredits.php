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

                /*
                 * Mutex por tenant.
                 *
                 * Dos cobros simultáneos del mismo tenant
                 * no pueden seleccionar el mismo saldo.
                 */
                Tenant::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $payment->tenant_id
                    );

                $grossAmount =
                    $payment->contractualAmount();

                $referralDiscount =
                    $payment
                    ->referral_discount_amount;

                $chargeableBeforeCredits =
                    $grossAmount
                    - $referralDiscount;

                if ($chargeableBeforeCredits < 0) {
                    throw new LogicException(
                        'El descuento de referido supera el importe contractual del pago.'
                    );
                }

                /*
                 * Si ya existen créditos reservados para
                 * este Payment, reutilizamos esa reserva.
                 *
                 * Esto hace idempotente una segunda llamada
                 * para el mismo Payment.
                 */
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
                        /*
                         * Los créditos no se parten.
                         *
                         * Además, no reservamos un crédito
                         * si dejaría el importe final del
                         * Payment exactamente en cero.
                         */
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
