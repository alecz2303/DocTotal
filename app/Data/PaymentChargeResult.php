<?php

namespace App\Data;

class PaymentChargeResult
{
    private function __construct(
        public readonly bool $succeeded,
        public readonly ?string $providerPaymentId,
        public readonly ?string $failureCode,
        public readonly ?string $failureMessage,
    ) {}

    public static function succeeded(
        string $providerPaymentId
    ): self {
        return new self(
            succeeded: true,
            providerPaymentId: $providerPaymentId,
            failureCode: null,
            failureMessage: null,
        );
    }

    public static function failed(
        ?string $failureCode = null,
        ?string $failureMessage = null,
        ?string $providerPaymentId = null,
    ): self {
        return new self(
            succeeded: false,
            providerPaymentId: $providerPaymentId,
            failureCode: $failureCode,
            failureMessage: $failureMessage,
        );
    }

    public function isSucceeded(): bool
    {
        return $this->succeeded;
    }

    public function isFailed(): bool
    {
        return ! $this->succeeded;
    }
}
