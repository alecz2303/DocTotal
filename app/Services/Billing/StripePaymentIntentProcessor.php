<?php

namespace App\Services\Billing;

use App\Contracts\StripePaymentIntentApi;
use App\Contracts\StripePaymentIntentProcessor as StripePaymentIntentProcessorContract;
use App\Data\PaymentChargeResult;
use App\Models\BillingCustomer;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Stripe\Exception\CardException;

class StripePaymentIntentProcessor implements StripePaymentIntentProcessorContract
{
    public function __construct(
        private readonly StripePaymentIntentApi $paymentIntents
    ) {}

    public function charge(
        Payment $payment,
        BillingCustomer $billingCustomer,
        PaymentMethod $paymentMethod,
    ): PaymentChargeResult {
        try {
            $paymentIntent =
                $this->paymentIntents->create(
                    [
                        'amount' =>
                        $payment->amount,

                        'currency' =>
                        strtolower(
                            $payment->currency
                        ),

                        'customer' =>
                        $billingCustomer
                            ->provider_customer_id,

                        'payment_method' =>
                        $paymentMethod
                            ->provider_payment_method_id,

                        'off_session' =>
                        true,

                        'confirm' =>
                        true,

                        'metadata' => [
                            'doctotal_payment_uuid' =>
                            $payment->uuid,

                            'tenant_id' =>
                            (string) $payment->tenant_id,

                            'subscription_id' =>
                            (string) $payment
                                ->subscription_id,
                        ],
                    ],
                    [
                        'idempotency_key' =>
                        $payment->idempotency_key,
                    ]
                );

            if (
                $paymentIntent->status ===
                'succeeded'
            ) {
                return PaymentChargeResult::succeeded(
                    $paymentIntent->id
                );
            }

            return PaymentChargeResult::failed(
                failureCode: $paymentIntent
                    ->last_payment_error
                    ?->code,

                failureMessage: $paymentIntent
                    ->last_payment_error
                    ?->message,

                providerPaymentId: $paymentIntent->id,
            );
        } catch (CardException $exception) {
            return PaymentChargeResult::failed(
                failureCode: $exception->getStripeCode(),

                failureMessage: $exception->getMessage(),

                providerPaymentId: $exception
                    ->getError()
                    ?->payment_intent
                    ?->id,
            );
        }
    }
}
