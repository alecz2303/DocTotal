<?php

namespace App\Services\Billing;

use App\Contracts\StripeCustomerApi as StripeCustomerApiContract;
use Stripe\Customer;
use Stripe\StripeClient;

class StripeCustomerApi implements StripeCustomerApiContract
{
    public function __construct(
        private readonly StripeClient $stripe
    ) {}

    public function create(
        array $params,
        array $options = [],
    ): Customer {
        return $this->stripe
            ->customers
            ->create(
                $params,
                $options
            );
    }
}
