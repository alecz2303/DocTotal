<?php

namespace App\Contracts;

use Stripe\PaymentIntent;

interface StripePaymentIntentApi
{
    public function create(
        array $params,
        array $options = [],
    ): PaymentIntent;

    public function retrieve(
        string $paymentIntentId,
    ): PaymentIntent;

    public function update(
        string $paymentIntentId,
        array $params,
    ): PaymentIntent;

    public function cancel(
        string $paymentIntentId,
    ): PaymentIntent;
}
