<?php

namespace Tests\Fakes;

use App\Contracts\StripePaymentIntentApi;
use LogicException;
use Stripe\PaymentIntent;

class FakeStripePaymentIntentApi implements StripePaymentIntentApi
{
    public ?array $receivedParams = null;

    public ?array $receivedOptions = null;

    public ?string $receivedPaymentIntentId =
    null;

    private ?PaymentIntent $createResult =
    null;

    private ?PaymentIntent $retrieveResult =
    null;

    private ?\Throwable $exception =
    null;

    public ?string $receivedUpdatePaymentIntentId = null;

    public ?array $receivedUpdateParams = null;

    private ?PaymentIntent $updateResult = null;

    public function returnPaymentIntent(
        PaymentIntent $paymentIntent
    ): void {
        $this->createResult =
            $paymentIntent;

        $this->exception =
            null;
    }

    public function returnUpdatedPaymentIntent(
        PaymentIntent $paymentIntent
    ): void {
        $this->updateResult =
            $paymentIntent;
    }

    public function update(
        string $paymentIntentId,
        array $params,
    ): PaymentIntent {
        $this->receivedUpdatePaymentIntentId =
            $paymentIntentId;

        $this->receivedUpdateParams =
            $params;

        if ($this->exception) {
            throw $this->exception;
        }

        if (! $this->updateResult) {
            throw new LogicException(
                'No se configuró un PaymentIntent actualizado fake.'
            );
        }

        return $this->updateResult;
    }

    public function returnRetrievedPaymentIntent(
        PaymentIntent $paymentIntent
    ): void {
        $this->retrieveResult =
            $paymentIntent;

        $this->exception =
            null;
    }

    public function throwException(
        \Throwable $exception
    ): void {
        $this->exception =
            $exception;

        $this->createResult =
            null;

        $this->retrieveResult =
            null;
    }

    public function create(
        array $params,
        array $options = [],
    ): PaymentIntent {
        $this->receivedParams =
            $params;

        $this->receivedOptions =
            $options;

        if ($this->exception) {
            throw $this->exception;
        }

        if (! $this->createResult) {
            throw new LogicException(
                'No se configuró un PaymentIntent fake para create.'
            );
        }

        return $this->createResult;
    }

    public function retrieve(
        string $paymentIntentId
    ): PaymentIntent {
        $this->receivedPaymentIntentId =
            $paymentIntentId;

        if ($this->exception) {
            throw $this->exception;
        }

        if (! $this->retrieveResult) {
            throw new LogicException(
                'No se configuró un PaymentIntent fake para retrieve.'
            );
        }

        return $this->retrieveResult;
    }
}
