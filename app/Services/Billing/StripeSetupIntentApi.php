<?php

namespace App\Services\Billing;

use App\Contracts\StripeSetupIntentApi as StripeSetupIntentApiContract;
use Stripe\SetupIntent;
use Stripe\StripeClient;

class StripeSetupIntentApi implements StripeSetupIntentApiContract
{
    public function __construct(
        private readonly StripeClient $stripe
    ) {}

    public function create(
        array $params,
        array $options = [],
    ): SetupIntent {
        return $this->stripe
            ->setupIntents
            ->create(
                $params,
                $options
            );
    }
}
