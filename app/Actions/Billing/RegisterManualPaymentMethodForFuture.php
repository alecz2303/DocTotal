<?php

namespace App\Actions\Billing;

use App\Contracts\StripePaymentIntentApi;
use App\Models\Payment;
use App\Models\Tenant;
use LogicException;

class RegisterManualPaymentMethodForFuture
{
    public function __construct(
        private readonly StripePaymentIntentApi $paymentIntents,
        private readonly RegisterStripePaymentMethod $registerPaymentMethod,
    ) {}

    public function execute(
        Tenant $tenant,
        Payment $payment,
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

        if (! $payment->isSucceeded()) {
            throw new LogicException(
                'Sólo puede guardarse la tarjeta de un pago exitoso.'
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
            $paymentIntent->status !==
            'succeeded'
        ) {
            throw new LogicException(
                'El PaymentIntent no está pagado.'
            );
        }

        $metadata =
            $paymentIntent->metadata;

        if (
            (string) (
                $metadata
                ?->doctotal_payment_uuid
                ?? ''
            ) !==
            $payment->uuid
        ) {
            throw new LogicException(
                'El PaymentIntent no pertenece al pago de DocTotal.'
            );
        }

        if (
            (string) (
                $metadata
                ?->doctotal_tenant_id
                ?? ''
            ) !==
            (string) $tenant->id
        ) {
            throw new LogicException(
                'El PaymentIntent no pertenece al tenant de DocTotal.'
            );
        }

        if (
            (string) (
                $metadata
                ?->payment_mode
                ?? ''
            ) !==
            'manual'
        ) {
            throw new LogicException(
                'El PaymentIntent no corresponde a un pago manual.'
            );
        }

        /*
         * Ésta es la autorización explícita que el usuario
         * dio al marcar el checkbox antes de pagar.
         */
        if (
            (string) (
                $metadata
                ?->save_for_future
                ?? '0'
            ) !==
            '1'
        ) {
            return;
        }

        $paymentMethodId =
            $paymentIntent->payment_method;

        /*
         * Dependiendo de cómo Stripe haya expandido el
         * objeto, payment_method puede ser el ID o un
         * objeto PaymentMethod.
         */
        if (is_object($paymentMethodId)) {
            $paymentMethodId =
                $paymentMethodId->id
                ?? null;
        }

        if (
            ! is_string($paymentMethodId)
            || $paymentMethodId === ''
        ) {
            throw new LogicException(
                'Stripe no devolvió el método de pago utilizado.'
            );
        }

        $this->registerPaymentMethod->execute(
            $tenant,
            $paymentMethodId
        );
    }
}
