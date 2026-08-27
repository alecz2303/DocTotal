<?php

namespace App\Contracts;

use App\Data\PaymentChargeResult;
use App\Models\BillingCustomer;
use App\Models\Payment;
use App\Models\PaymentMethod;

interface StripePaymentIntentProcessor
{
    public function charge(
        Payment $payment,
        BillingCustomer $billingCustomer,
        PaymentMethod $paymentMethod,
    ): PaymentChargeResult;
}
