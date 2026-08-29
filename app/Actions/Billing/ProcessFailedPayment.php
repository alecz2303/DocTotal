<?php

namespace App\Actions\Billing;

use App\Models\Payment;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use LogicException;

class ProcessFailedPayment
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
                            'El pago no puede procesarse como fallido desde el estado "%s".',
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

                if (! $subscription->isActive()) {
                    throw new LogicException(
                        sprintf(
                            'El primer fallo de pago no puede iniciar recuperación desde una suscripción "%s".',
                            $subscription->status
                        )
                    );
                }

                /*
                 * Primero dejamos constancia histórica del fallo.
                 */
                $payment->fail(
                    $failedAt,
                    $failureCode,
                    $failureMessage,
                    $providerPaymentId
                );

                /*
                 * Un crédito sólo estaba reservado para este intento.
                 * Al fallar Stripe debe volver a quedar disponible.
                 *
                 * Payment conserva promotional_credit_amount y amount
                 * como historial de lo que realmente se intentó cobrar.
                 */
                app(
                    ReleaseReservedPromotionalCredits::class
                )->execute(
                    $payment,
                    $failedAt
                );

                app(
                    StartSubscriptionRecovery::class
                )->execute(
                    $subscription,
                    $failedAt
                );

                return $payment->refresh();
            }
        );
    }
}
