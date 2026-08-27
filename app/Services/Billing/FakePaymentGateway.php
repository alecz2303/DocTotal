<?php

namespace App\Services\Billing;

use App\Contracts\PaymentGateway;
use App\Data\PaymentChargeResult;
use App\Models\Payment;
use LogicException;

class FakePaymentGateway implements PaymentGateway
{
    private ?PaymentChargeResult $nextResult =
    null;

    public function succeedNextCharge(
        string $providerPaymentId =
        'fake-payment-success'
    ): void {
        $this->nextResult =
            PaymentChargeResult::succeeded(
                $providerPaymentId
            );
    }

    public function failNextCharge(
        ?string $failureCode =
        'card_declined',
        ?string $failureMessage =
        'El pago fue rechazado.',
        ?string $providerPaymentId = null,
    ): void {
        $this->nextResult =
            PaymentChargeResult::failed(
                failureCode: $failureCode,
                failureMessage: $failureMessage,
                providerPaymentId: $providerPaymentId,
            );
    }

    public function charge(
        Payment $payment
    ): PaymentChargeResult {
        if (! $payment->isPending()) {
            throw new LogicException(
                'Sólo puede intentarse el cobro de un pago pendiente.'
            );
        }

        if (! $this->nextResult) {
            throw new LogicException(
                'FakePaymentGateway no tiene un resultado configurado.'
            );
        }

        $result =
            $this->nextResult;

        $this->nextResult =
            null;

        return $result;
    }

    public function name(): string
    {
        return 'fake';
    }
}
