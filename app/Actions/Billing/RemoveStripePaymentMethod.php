<?php

namespace App\Actions\Billing;

use App\Contracts\StripePaymentMethodApi;
use App\Models\PaymentMethod;
use App\Models\Tenant;
use LogicException;

class RemoveStripePaymentMethod
{
    public function __construct(
        private readonly StripePaymentMethodApi $paymentMethods
    ) {}

    public function execute(
        Tenant $tenant,
        PaymentMethod $paymentMethod,
    ): PaymentMethod {
        $paymentMethod->refresh();

        if (
            $paymentMethod->tenant_id !==
            $tenant->id
        ) {
            throw new LogicException(
                'El método de pago no pertenece al tenant.'
            );
        }

        if (! $paymentMethod->isStripe()) {
            throw new LogicException(
                'El método de pago no pertenece a Stripe.'
            );
        }

        /*
         * Ya fue eliminado anteriormente.
         *
         * La operación es idempotente y no volvemos
         * a llamar a Stripe.
         */
        if (! $paymentMethod->isActive()) {
            return $paymentMethod;
        }

        $billingCustomer =
            $tenant->stripeBillingCustomer();

        if (! $billingCustomer) {
            throw new LogicException(
                'El tenant no tiene un cliente Stripe configurado.'
            );
        }

        $stripePaymentMethod =
            $this->paymentMethods->retrieve(
                $paymentMethod
                    ->provider_payment_method_id
            );

        if (
            $stripePaymentMethod->id !==
            $paymentMethod
            ->provider_payment_method_id
        ) {
            throw new LogicException(
                'Stripe devolvió un método de pago diferente al esperado.'
            );
        }

        /*
         * customer = null significa que Stripe ya lo
         * desvinculó anteriormente.
         *
         * Esto permite recuperar una ejecución parcial
         * donde Stripe hizo detach pero la actualización
         * local falló.
         */
        if (
            $stripePaymentMethod->customer !== null
            && $stripePaymentMethod->customer !==
            $billingCustomer->provider_customer_id
        ) {
            throw new LogicException(
                'El método de pago no pertenece al cliente Stripe del tenant.'
            );
        }

        if ($stripePaymentMethod->customer !== null) {
            $detached =
                $this->paymentMethods->detach(
                    $paymentMethod
                        ->provider_payment_method_id
                );

            if (
                $detached->id !==
                $paymentMethod
                ->provider_payment_method_id
            ) {
                throw new LogicException(
                    'Stripe desvinculó un método de pago diferente al esperado.'
                );
            }
        }

        $paymentMethod->deactivate();

        return $paymentMethod->refresh();
    }
}
