<?php

namespace App\Actions\Billing;

use App\Models\Payment;
use App\Models\PromotionalCredit;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use LogicException;

class ReleaseReservedPromotionalCredits
{
    public function execute(
        Payment $payment,
        CarbonInterface $releasedAt,
    ): void {
        DB::transaction(
            function () use (
                $payment,
                $releasedAt,
            ): void {
                $payment =
                    Payment::withoutGlobalScopes()
                    ->lockForUpdate()
                    ->findOrFail(
                        $payment->id
                    );

                if (
                    ! $payment->isFailed()
                    && ! $payment->isCanceled()
                ) {
                    throw new LogicException(
                        'Sólo un pago fallido o cancelado puede liberar créditos promocionales.'
                    );
                }

                /*
                 * Una liberación ya completada es
                 * idempotente.
                 */
                if (
                    $payment
                    ->promotional_credits_released_at
                ) {
                    return;
                }

                /*
                 * Si este Payment no utilizó créditos
                 * promocionales, no existe ninguna
                 * reserva que liberar.
                 *
                 * Tampoco marcamos released_at porque
                 * históricamente nunca hubo créditos
                 * asociados a este intento.
                 */
                if (
                    $payment
                    ->promotional_credit_amount === 0
                ) {
                    return;
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

                /*
                 * Payment conserva el importe promocional
                 * histórico del intento.
                 *
                 * Si la reserva real no coincide con ese
                 * importe, existe una inconsistencia y no
                 * debemos ocultarla liberando parcialmente.
                 */
                if (
                    $reservedAmount !==
                    $payment
                    ->promotional_credit_amount
                ) {
                    throw new LogicException(
                        'La reserva de créditos no coincide con el importe promocional del pago.'
                    );
                }

                foreach ($credits as $credit) {
                    $credit->release();
                }

                /*
                 * El importe histórico del Payment no se
                 * modifica. Únicamente registramos que la
                 * reserva asociada al intento ya fue
                 * liberada.
                 */
                $payment->update([
                    'promotional_credits_released_at' =>
                    $releasedAt,
                ]);
            }
        );
    }
}
