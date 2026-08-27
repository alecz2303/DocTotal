<?php

namespace App\Actions\Billing;

use App\Contracts\StripePaymentIntentApi;
use App\Models\Payment;
use App\Models\Tenant;
use LogicException;

class UpdateManualPaymentFutureUsage
{
    public function __construct(
        private readonly StripePaymentIntentApi $paymentIntents,
    ) {}

    public function execute(
        Tenant $tenant,
        Payment $payment,
        bool $saveForFuture,
    ): void {
        $payment->refresh();

        if (
            $payment->tenant_id !==
            $tenant->id
        ) {
            throw new LogicException(
                'El pago no pertenece al tenant.'
            );
        }

        if (! $payment->isPending()) {
            throw new LogicException(
                'Sólo puede modificarse un pago pendiente.'
            );
        }

        if (
            $payment->provider !==
            'stripe'
        ) {
            throw new LogicException(
                'El pago no pertenece a Stripe.'
            );
        }

        if (! $payment->provider_payment_id) {
            throw new LogicException(
                'El pago no tiene un PaymentIntent asociado.'
            );
        }

        $paymentIntent =
            $this->paymentIntents->retrieve(
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

        if (
            $paymentIntent->status ===
            'succeeded'
        ) {
            throw new LogicException(
                'El PaymentIntent ya fue pagado.'
            );
        }

        $metadata =
            $paymentIntent->metadata
            ?->toArray()
            ?? [];

        /*
         * Conservamos todo el metadata existente.
         * Sólo cambiamos la decisión de reutilización.
         */
        $metadata['save_for_future'] =
            $saveForFuture
            ? '1'
            : '0';

        $params = [
            'metadata' =>
            $metadata,
        ];

        if ($saveForFuture) {
            $params['setup_future_usage'] =
                'off_session';
        } else {
            /*
             * Stripe utiliza cadena vacía para
             * desestablecer algunos parámetros
             * opcionales mediante update.
             */
            $params['setup_future_usage'] =
                '';
        }

        $updated =
            $this->paymentIntents->update(
                $payment->provider_payment_id,
                $params
            );

        if (
            $updated->id !==
            $payment->provider_payment_id
        ) {
            throw new LogicException(
                'Stripe actualizó un PaymentIntent diferente al esperado.'
            );
        }
    }
}
