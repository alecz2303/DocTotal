<?php

namespace App\Contracts;

use Stripe\PaymentMethod;

interface StripePaymentMethodApi
{
    public function retrieve(
        string $paymentMethodId
    ): PaymentMethod;

    public function detach(
        string $paymentMethodId
    ): PaymentMethod;
}
