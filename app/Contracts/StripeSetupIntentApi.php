<?php

namespace App\Contracts;

use Stripe\SetupIntent;

interface StripeSetupIntentApi
{
    public function create(
        array $params,
        array $options = [],
    ): SetupIntent;
}
