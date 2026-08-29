<?php

namespace App\Actions\Billing;

use App\Models\Payment;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use LogicException;

class ProcessFailedPaymentRetry
{
    public function execute(
        Payment $payment,
        CarbonInterface $failedAt,
        ?string $failureCode = null,
        ?string $failureMessage = null,
        ?string $providerPaymentId = null,
    ): Payment {
        return DB::transaction(
            function () use (
                $payment,
                $failedAt,
                $failureCode,
                $failureMessage,
                $providerPaymentId,
            ): Payment {
                $payment->refresh();

                if (! $payment->isPending()) {
                    throw new LogicException(
                        sprintf(
                            'El reintento no puede procesarse como fallido desde el estado "%s".',
                            $payment->status
                        )
                    );
                }

                $subscription =
                    $payment->subscription;

                if (! $subscription) {
                    throw new LogicException(
                        'El pago no tiene una suscripción asociada.'
                    );
                }

                if (! $subscription->isPastDue()) {
                    throw new LogicException(
                        sprintf(
                            'El reintento fallido requiere una suscripción vencida, estado actual "%s".',
                            $subscription->status
                        )
                    );
                }

                if (
                    ! $subscription->past_due_since
                    || ! $subscription->grace_ends_at
                ) {
                    throw new LogicException(
                        'La suscripción no tiene un proceso de recuperación válido.'
                    );
                }

                if (
                    $failedAt->greaterThanOrEqualTo(
                        $subscription->grace_ends_at
                    )
                ) {
                    throw new LogicException(
                        'No puede procesarse un reintento después de finalizar el periodo de gracia.'
                    );
                }

                $retrySchedule = config(
                    'billing.retry_schedule_hours',
                    []
                );

                if (empty($retrySchedule)) {
                    throw new LogicException(
                        'La configuración de reintentos de pago no es válida.'
                    );
                }

                $newRetryCount =
                    $subscription->retry_count + 1;

                if (
                    $newRetryCount >
                    count($retrySchedule)
                ) {
                    throw new LogicException(
                        'La suscripción ya agotó sus reintentos de pago.'
                    );
                }

                /*
                 * Registramos primero el resultado fallido.
                 */
                $payment->fail(
                    $failedAt,
                    $failureCode,
                    $failureMessage,
                    $providerPaymentId
                );

                /*
                 * Los créditos reservados para este intento
                 * vuelven a estar disponibles para el siguiente
                 * Payment/retry.
                 */
                app(
                    ReleaseReservedPromotionalCredits::class
                )->execute(
                    $payment,
                    $failedAt
                );

                $nextRetryAt = null;

                if (
                    $newRetryCount <
                    count($retrySchedule)
                ) {
                    $nextRetryHours =
                        (int) $retrySchedule[$newRetryCount];

                    $nextRetryAt =
                        $subscription
                        ->past_due_since
                        ->copy()
                        ->addHours(
                            $nextRetryHours
                        );

                    /*
                     * Nunca programamos un retry después
                     * del final del grace period.
                     */
                    if (
                        $nextRetryAt
                        ->greaterThanOrEqualTo(
                            $subscription
                                ->grace_ends_at
                        )
                    ) {
                        $nextRetryAt = null;
                    }
                }

                $subscription->update([
                    'retry_count' =>
                    $newRetryCount,

                    'next_retry_at' =>
                    $nextRetryAt,
                ]);

                return $payment->refresh();
            }
        );
    }
}
