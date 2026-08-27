<?php

namespace Tests\Fakes;

use App\Contracts\StripePaymentMethodApi;
use LogicException;
use Stripe\PaymentMethod;

class FakeStripePaymentMethodApi implements StripePaymentMethodApi
{
    public ?string $receivedPaymentMethodId =
    null;

    public ?string $receivedDetachPaymentMethodId =
    null;

    public int $retrieveCallCount = 0;

    public int $detachCallCount = 0;

    private ?PaymentMethod $retrieveResult =
    null;

    private ?PaymentMethod $detachResult =
    null;

    public function returnCard(
        string $id = 'pm_test_4242',
        ?string $customer = 'cus_test_123',
        string $brand = 'visa',
        string $lastFour = '4242',
        int $expiresMonth = 12,
        int $expiresYear = 2030,
    ): void {
        $this->retrieveResult =
            PaymentMethod::constructFrom([
                'id' =>
                $id,

                'customer' =>
                $customer,

                'type' =>
                'card',

                'card' => [
                    'brand' =>
                    $brand,

                    'last4' =>
                    $lastFour,

                    'exp_month' =>
                    $expiresMonth,

                    'exp_year' =>
                    $expiresYear,
                ],
            ]);
    }

    public function returnDetachedCard(
        string $id = 'pm_test_4242',
    ): void {
        $this->detachResult =
            PaymentMethod::constructFrom([
                'id' =>
                $id,

                'customer' =>
                null,

                'type' =>
                'card',
            ]);
    }

    public function retrieve(
        string $paymentMethodId
    ): PaymentMethod {
        $this->retrieveCallCount++;

        $this->receivedPaymentMethodId =
            $paymentMethodId;

        if (! $this->retrieveResult) {
            throw new LogicException(
                'No se configuró un PaymentMethod Stripe fake para retrieve.'
            );
        }

        return $this->retrieveResult;
    }

    public function detach(
        string $paymentMethodId
    ): PaymentMethod {
        $this->detachCallCount++;

        $this->receivedDetachPaymentMethodId =
            $paymentMethodId;

        if (! $this->detachResult) {
            throw new LogicException(
                'No se configuró un PaymentMethod Stripe fake para detach.'
            );
        }

        return $this->detachResult;
    }
}
