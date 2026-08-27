<?php

namespace App\Contracts;

use Stripe\Customer;

interface StripeCustomerApi
{
    public function create(
        array $params,
        array $options = [],
    ): Customer;
}
