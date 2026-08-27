<?php

namespace App\Data;

use App\Models\Payment;

class ManualSubscriptionPaymentIntent
{
    public function __construct(
        public readonly Payment $payment,
        public readonly string $clientSecret,
    ) {}
}
