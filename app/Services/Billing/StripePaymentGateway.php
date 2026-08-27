<?php

namespace App\Services\Billing;

use App\Contracts\PaymentGateway;
use App\Contracts\StripePaymentIntentProcessor;
use App\Data\PaymentChargeResult;
use App\Models\Payment;
use LogicException;

class StripePaymentGateway implements PaymentGateway
{
    public function __construct(
        private readonly StripePaymentIntentProcessor $processor
    ) {}

    public function name(): string
    {
        return 'stripe';
    }

    public function charge(
        Payment $payment
    ): PaymentChargeResult {
        if (! $payment->isPending()) {
            throw new LogicException(
                'Sólo puede intentarse el cobro de un pago pendiente.'
            );
        }

        $tenant = $payment->tenant;

        if (! $tenant) {
            throw new LogicException(
                'El pago no tiene un tenant asociado.'
            );
        }

        $billingCustomer =
            $tenant->stripeBillingCustomer();

        if (! $billingCustomer) {
            return PaymentChargeResult::failed(
                failureCode: 'stripe_customer_missing',
                failureMessage: 'El tenant no tiene un cliente Stripe configurado.'
            );
        }

        $paymentMethod =
            $tenant->defaultPaymentMethod();

        if (! $paymentMethod) {
            return PaymentChargeResult::failed(
                failureCode: 'payment_method_missing',
                failureMessage: 'El tenant no tiene un método de pago predeterminado.'
            );
        }

        if (! $paymentMethod->isStripe()) {
            return PaymentChargeResult::failed(
                failureCode: 'invalid_payment_method_provider',
                failureMessage: 'El método de pago predeterminado no pertenece a Stripe.'
            );
        }

        return $this->processor->charge(
            $payment,
            $billingCustomer,
            $paymentMethod
        );
    }
}
