<?php

namespace Tests\Fakes;

use App\Contracts\StripePaymentIntentProcessor;
use App\Data\PaymentChargeResult;
use App\Models\BillingCustomer;
use App\Models\Payment;
use App\Models\PaymentMethod;
use LogicException;

class FakeStripePaymentIntentProcessor implements StripePaymentIntentProcessor
{
    public ?Payment $receivedPayment = null;

    public ?BillingCustomer $receivedCustomer = null;

    public ?PaymentMethod $receivedPaymentMethod = null;

    private ?PaymentChargeResult $result = null;

    public function succeed(
        string $providerPaymentId =
        'pi_test_success'
    ): void {
        $this->result =
            PaymentChargeResult::succeeded(
                $providerPaymentId
            );
    }

    public function fail(
        ?string $failureCode =
        'card_declined',
        ?string $failureMessage =
        'Tarjeta rechazada.',
        ?string $providerPaymentId =
        'pi_test_failed',
    ): void {
        $this->result =
            PaymentChargeResult::failed(
                failureCode: $failureCode,

                failureMessage: $failureMessage,

                providerPaymentId: $providerPaymentId,
            );
    }

    public function charge(
        Payment $payment,
        BillingCustomer $billingCustomer,
        PaymentMethod $paymentMethod,
    ): PaymentChargeResult {
        $this->receivedPayment =
            $payment;

        $this->receivedCustomer =
            $billingCustomer;

        $this->receivedPaymentMethod =
            $paymentMethod;

        if (! $this->result) {
            throw new LogicException(
                'No se configuró resultado Stripe fake.'
            );
        }

        return $this->result;
    }
}
