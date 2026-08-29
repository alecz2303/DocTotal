<?php

namespace App\Actions\Billing;

use App\Contracts\StripePaymentIntentApi;
use App\Models\Payment;
use App\Models\Tenant;
use Carbon\CarbonInterface;
use LogicException;
use Throwable;

class CleanupAbandonedManualSubscriptionPayments
{
    public function __construct(
        private readonly StripePaymentIntentApi $paymentIntents,
        private readonly AbandonManualSubscriptionPayment $abandonPayment,
        private readonly ConfirmManualSubscriptionPayment $confirmPayment,
    ) {}

    public function execute(
        CarbonInterface $cutoff,
        CarbonInterface $processedAt,
    ): array {
        $canceled = 0;
        $reconciled = 0;
        $errors = 0;

        Payment::withoutGlobalScopes()
            ->where(
                'status',
                Payment::STATUS_PENDING
            )
            ->whereNull(
                'subscription_id'
            )
            ->where(
                'provider',
                'stripe'
            )
            ->whereNotNull(
                'attempted_at'
            )
            ->where(
                'attempted_at',
                '<=',
                $cutoff
            )
            ->orderBy('id')
            ->chunkById(
                100,
                function ($payments) use (
                    $processedAt,
                    &$canceled,
                    &$reconciled,
                    &$errors,
                ): void {
                    foreach ($payments as $payment) {
                        try {
                            $tenant =
                                Tenant::query()
                                ->findOrFail(
                                    $payment->tenant_id
                                );

                            /*
                             * Si todavía no existe un
                             * PaymentIntent, el checkout puede
                             * abandonarse únicamente de forma
                             * local.
                             */
                            if (! $payment->provider_payment_id) {
                                $this->abandonPayment
                                    ->execute(
                                        $tenant,
                                        $payment,
                                        $processedAt
                                    );

                                $canceled++;

                                continue;
                            }

                            /*
                             * Antes de cancelar consultamos el
                             * estado real de Stripe.
                             *
                             * Un Payment local puede seguir
                             * pending aunque Stripe ya lo haya
                             * cobrado.
                             */
                            $paymentIntent =
                                $this->paymentIntents
                                ->retrieve(
                                    $payment->provider_payment_id
                                );

                            if (
                                $paymentIntent->id !==
                                $payment->provider_payment_id
                            ) {
                                throw new LogicException(
                                    'Stripe devolvió un PaymentIntent diferente al esperado.'
                                );
                            }

                            /*
                             * Si Stripe ya cobró, nunca debemos
                             * cancelar el Payment local.
                             *
                             * Reutilizamos el flujo normal de
                             * confirmación para activar la
                             * suscripción, consumir créditos y
                             * procesar promociones.
                             */
                            if (
                                $paymentIntent->status ===
                                'succeeded'
                            ) {
                                $this->confirmPayment
                                    ->execute(
                                        $tenant,
                                        $payment,
                                        $processedAt
                                    );

                                $reconciled++;

                                continue;
                            }

                            /*
                             * El resto de estados se procesa
                             * mediante la Action de abandono.
                             *
                             * Ella es responsable de cancelar
                             * Stripe, cancelar localmente y
                             * liberar créditos promocionales.
                             */
                            $this->abandonPayment
                                ->execute(
                                    $tenant,
                                    $payment,
                                    $processedAt
                                );

                            $canceled++;
                        } catch (Throwable $exception) {
                            $errors++;

                            report($exception);
                        }
                    }
                }
            );

        return [
            'processed' =>
            $canceled + $reconciled,

            'canceled' =>
            $canceled,

            'reconciled' =>
            $reconciled,

            'errors' =>
            $errors,
        ];
    }
}
