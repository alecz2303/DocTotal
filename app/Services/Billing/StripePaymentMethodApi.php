<?php

namespace App\Services\Billing;

use App\Contracts\StripePaymentMethodApi as StripePaymentMethodApiContract;
use Stripe\PaymentMethod;
use Stripe\StripeClient;

class StripePaymentMethodApi implements StripePaymentMethodApiContract
{
    public function __construct(
        private readonly StripeClient $stripe
    ) {}

    public function retrieve(
        string $paymentMethodId
    ): PaymentMethod {
        return $this->stripe
            ->paymentMethods
            ->retrieve(
                $paymentMethodId
            );
    }

    public function detach(
        string $paymentMethodId
    ): PaymentMethod {
        return $this->stripe
            ->paymentMethods
            ->detach(
                $paymentMethodId
            );
    }
}
