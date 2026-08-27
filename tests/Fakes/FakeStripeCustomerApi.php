<?php

namespace Tests\Fakes;

use App\Contracts\StripeCustomerApi;
use LogicException;
use Stripe\Customer;

class FakeStripeCustomerApi implements StripeCustomerApi
{
    public ?array $receivedParams = null;

    public ?array $receivedOptions = null;

    public int $callCount = 0;

    private ?Customer $result = null;

    private ?\Throwable $exception = null;

    public function returnCustomer(
        string $customerId =
        'cus_test_123'
    ): void {
        $this->result =
            Customer::constructFrom([
                'id' =>
                $customerId,
            ]);

        $this->exception =
            null;
    }

    public function throwException(
        \Throwable $exception
    ): void {
        $this->exception =
            $exception;

        $this->result =
            null;
    }

    public function create(
        array $params,
        array $options = [],
    ): Customer {
        $this->callCount++;

        $this->receivedParams =
            $params;

        $this->receivedOptions =
            $options;

        if ($this->exception) {
            throw $this->exception;
        }

        if (! $this->result) {
            throw new LogicException(
                'No se configuró un Stripe Customer fake.'
            );
        }

        return $this->result;
    }
}
