<?php

namespace Tests\Feature\Subscription;

use App\Contracts\PaymentGateway;
use App\Data\PaymentChargeResult;
use App\Models\Payment;
use App\Services\Billing\FakePaymentGateway;
use LogicException;
use Tests\TestCase;

class PaymentGatewayTest extends TestCase
{
    public function test_fake_gateway_implements_payment_gateway_contract(): void
    {
        $gateway = app(
            PaymentGateway::class
        );

        $this->assertInstanceOf(
            PaymentGateway::class,
            $gateway
        );

        $this->assertInstanceOf(
            FakePaymentGateway::class,
            $gateway
        );
    }

    public function test_success_result_contains_provider_payment_id(): void
    {
        $result =
            PaymentChargeResult::succeeded(
                'provider-123'
            );

        $this->assertTrue(
            $result->isSucceeded()
        );

        $this->assertFalse(
            $result->isFailed()
        );

        $this->assertSame(
            'provider-123',
            $result->providerPaymentId
        );

        $this->assertNull(
            $result->failureCode
        );

        $this->assertNull(
            $result->failureMessage
        );
    }

    public function test_failed_result_contains_failure_information(): void
    {
        $result =
            PaymentChargeResult::failed(
                failureCode: 'insufficient_funds',
                failureMessage: 'Fondos insuficientes.',
                providerPaymentId: 'provider-456',
            );

        $this->assertFalse(
            $result->isSucceeded()
        );

        $this->assertTrue(
            $result->isFailed()
        );

        $this->assertSame(
            'insufficient_funds',
            $result->failureCode
        );

        $this->assertSame(
            'Fondos insuficientes.',
            $result->failureMessage
        );

        $this->assertSame(
            'provider-456',
            $result->providerPaymentId
        );
    }

    public function test_fake_gateway_returns_configured_success(): void
    {
        $gateway = app(
            PaymentGateway::class
        );

        $this->assertInstanceOf(
            FakePaymentGateway::class,
            $gateway
        );

        $gateway->succeedNextCharge(
            'fake-success-123'
        );

        $payment =
            $this->pendingPayment();

        $result =
            $gateway->charge(
                $payment
            );

        $this->assertTrue(
            $result->isSucceeded()
        );

        $this->assertSame(
            'fake-success-123',
            $result->providerPaymentId
        );
    }

    public function test_fake_gateway_returns_configured_failure(): void
    {
        $gateway = app(
            PaymentGateway::class
        );

        $this->assertInstanceOf(
            FakePaymentGateway::class,
            $gateway
        );

        $gateway->failNextCharge(
            'card_declined',
            'Tarjeta rechazada.'
        );

        $result =
            $gateway->charge(
                $this->pendingPayment()
            );

        $this->assertTrue(
            $result->isFailed()
        );

        $this->assertSame(
            'card_declined',
            $result->failureCode
        );

        $this->assertSame(
            'Tarjeta rechazada.',
            $result->failureMessage
        );
    }

    public function test_fake_gateway_requires_configured_result(): void
    {
        $gateway = app(
            PaymentGateway::class
        );

        $this->expectException(
            LogicException::class
        );

        $gateway->charge(
            $this->pendingPayment()
        );
    }

    private function pendingPayment(): Payment
    {
        $payment = new Payment();

        $payment->status =
            Payment::STATUS_PENDING;

        return $payment;
    }
}
