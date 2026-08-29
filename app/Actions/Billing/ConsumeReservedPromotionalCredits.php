<?php

namespace App\Actions\Billing;

use App\Models\Payment;
use App\Models\PromotionalCredit;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use LogicException;

class ConsumeReservedPromotionalCredits
{
    public function execute(
        Payment $payment,
        CarbonInterface $consumedAt,
    ): void {
        DB::transaction(
            function () use (
                $payment,
                $consumedAt
            ): void {
                $payment =
                    Payment::withoutGlobalScopes()
                    ->lockForUpdate()
                    ->findOrFail(
                        $payment->id
                    );

                $consumedCredits =
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
                        PromotionalCredit::STATUS_CONSUMED
                    )
                    ->lockForUpdate()
                    ->get();

                $consumedAmount =
                    $consumedCredits->sum('amount');

                if (
                    $consumedAmount ===
                    $payment->promotional_credit_amount
                ) {
                    return;
                }

                if (! $payment->isSucceeded()) {
                    throw new LogicException(
                        'Sólo un pago exitoso puede consumir créditos promocionales.'
                    );
                }

                $credits =
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
                    ->lockForUpdate()
                    ->get();

                $reservedAmount =
                    $credits->sum('amount');

                if (
                    $consumedAmount
                    + $reservedAmount
                    !==
                    $payment->promotional_credit_amount
                ) {
                    throw new LogicException(
                        'La reserva de créditos no coincide con el importe promocional del pago.'
                    );
                }

                foreach ($credits as $credit) {
                    $credit->consume(
                        $consumedAt
                    );
                }
            }
        );
    }
}
