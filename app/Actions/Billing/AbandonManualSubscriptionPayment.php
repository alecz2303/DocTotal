<?php

namespace App\Actions\Billing;

use App\Contracts\StripePaymentIntentApi;
use App\Models\Payment;
use App\Models\Tenant;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use LogicException;
use Throwable;

class AbandonManualSubscriptionPayment
{
    public function __construct(
        private readonly StripePaymentIntentApi $paymentIntents,
        private readonly ReleaseReservedPromotionalCredits $releasePromotionalCredits,
    ) {}

    public function execute(
        Tenant $tenant,
        Payment $payment,
        CarbonInterface $canceledAt,
    ): Payment {
        return DB::transaction(
            function () use (
                $tenant,
                $payment,
                $canceledAt,
            ): Payment {
                $payment =
                    Payment::withoutGlobalScopes()
                    ->lockForUpdate()
                    ->findOrFail(
                        $payment->id
                    );

                if (
                    (int) $payment->tenant_id !==
                    (int) $tenant->id
                ) {
                    throw new LogicException(
                        'El pago pertenece a otro tenant.'
                    );
                }

                /*
                 * La operación es idempotente.
                 *
                 * Si el checkout ya fue cancelado,
                 * simplemente devolvemos el pago.
                 */
                if ($payment->isCanceled()) {
                    return $payment;
                }

                if (! $payment->isPending()) {
                    throw new LogicException(
                        sprintf(
                            'El checkout no puede cancelarse desde el estado "%s".',
                            $payment->status
                        )
                    );
                }

                /*
                 * Si Stripe ya tiene un PaymentIntent,
                 * intentamos cancelarlo antes de cerrar
                 * nuestro Payment local.
                 */
                if ($payment->provider_payment_id) {
                    try {
                        $paymentIntent =
                            $this->paymentIntents
                            ->cancel(
                                $payment->provider_payment_id
                            );

                        /*
                         * Conservamos el identificador que Stripe
                         * devolvió por si el proveedor normalizó
                         * o confirmó el mismo PaymentIntent.
                         */
                        if ($paymentIntent->id) {
                            $payment->provider_payment_id =
                                $paymentIntent->id;
                        }
                    } catch (Throwable $exception) {
                        /*
                         * No ocultamos errores de Stripe.
                         *
                         * Si no sabemos si el PaymentIntent quedó
                         * cancelado, tampoco debemos marcar nuestro
                         * Payment local como cancelado.
                         */
                        throw $exception;
                    }
                }

                /*
                 * El descuento inicial del referido no es un
                 * PromotionalCredit reservado.
                 *
                 * Como el Referral continúa pending, volverá
                 * a aplicarse automáticamente cuando el usuario
                 * cree el siguiente checkout.
                 */
                $payment->cancel(
                    $canceledAt,
                    $payment->provider_payment_id
                );


                /*
                 * Un crédito reservado pertenece únicamente
                 * a este intento de pago.
                 *
                 * Al abandonar el checkout debe regresar a
                 * available para poder utilizarse en el
                 * siguiente intento.
                 */
                $this->releasePromotionalCredits
                    ->execute(
                        $payment,
                        $canceledAt
                    );


                return $payment->refresh();
            }
        );
    }
}
