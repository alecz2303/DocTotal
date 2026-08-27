<?php

namespace App\Services\Billing;

use App\Contracts\StripePaymentIntentApi as StripePaymentIntentApiContract;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

class StripePaymentIntentApi implements StripePaymentIntentApiContract
{
    public function __construct(
        private readonly StripeClient $stripe
    ) {}

    public function create(
        array $params,
        array $options = [],
    ): PaymentIntent {
        return $this->stripe
            ->paymentIntents
            ->create(
                $params,
                $options
            );
    }

    public function retrieve(
        string $paymentIntentId
    ): PaymentIntent {
        return $this->stripe
            ->paymentIntents
            ->retrieve(
                $paymentIntentId
            );
    }

    public function update(
        string $paymentIntentId,
        array $params,
    ): PaymentIntent {
        return $this->stripe
            ->paymentIntents
            ->update(
                $paymentIntentId,
                $params
            );
    }
}
