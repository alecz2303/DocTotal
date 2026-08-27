<?php

namespace Tests\Fakes;

use App\Contracts\StripeSetupIntentApi;
use LogicException;
use Stripe\SetupIntent;

class FakeStripeSetupIntentApi implements StripeSetupIntentApi
{
    public ?array $receivedParams = null;

    public ?array $receivedOptions = null;

    public int $callCount = 0;

    private ?SetupIntent $result = null;

    public function returnSetupIntent(
        string $id = 'seti_test_123',
        string $clientSecret =
        'seti_test_123_secret_abc',
    ): void {
        $this->result =
            SetupIntent::constructFrom([
                'id' =>
                $id,

                'client_secret' =>
                $clientSecret,

                'status' =>
                'requires_payment_method',
            ]);
    }

    public function create(
        array $params,
        array $options = [],
    ): SetupIntent {
        $this->callCount++;

        $this->receivedParams =
            $params;

        $this->receivedOptions =
            $options;

        if (! $this->result) {
            throw new LogicException(
                'No se configuró un SetupIntent fake.'
            );
        }

        return $this->result;
    }
}
