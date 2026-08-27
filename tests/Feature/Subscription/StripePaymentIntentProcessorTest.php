<?php

namespace Tests\Feature\Subscription;

use App\Contracts\StripePaymentIntentApi;
use App\Models\BillingCustomer;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Services\Billing\StripePaymentIntentProcessor;
use RuntimeException;
use Stripe\PaymentIntent;
use Tests\Fakes\FakeStripePaymentIntentApi;
use Tests\TestCase;

class StripePaymentIntentProcessorTest extends TestCase
{
    private FakeStripePaymentIntentApi $api;

    protected function setUp(): void
    {
        parent::setUp();

        $this->api =
            new FakeStripePaymentIntentApi();

        $this->app->instance(
            StripePaymentIntentApi::class,
            $this->api
        );
    }

    public function test_processor_sends_correct_payment_amount_and_currency(): void
    {
        [$payment, $customer, $method] =
            $this->scenario();

        $this->api->returnPaymentIntent(
            $this->successfulIntent()
        );

        $this->processor()->charge(
            $payment,
            $customer,
            $method
        );

        $this->assertSame(
            60000,
            $this->api
                ->receivedParams['amount']
        );

        $this->assertSame(
            'mxn',
            $this->api
                ->receivedParams['currency']
        );
    }

    public function test_processor_sends_customer_and_payment_method(): void
    {
        [$payment, $customer, $method] =
            $this->scenario();

        $this->api->returnPaymentIntent(
            $this->successfulIntent()
        );

        $this->processor()->charge(
            $payment,
            $customer,
            $method
        );

        $this->assertSame(
            'cus_test_123',
            $this->api
                ->receivedParams['customer']
        );

        $this->assertSame(
            'pm_test_4242',
            $this->api
                ->receivedParams['payment_method']
        );
    }

    public function test_processor_creates_off_session_confirmed_payment(): void
    {
        [$payment, $customer, $method] =
            $this->scenario();

        $this->api->returnPaymentIntent(
            $this->successfulIntent()
        );

        $this->processor()->charge(
            $payment,
            $customer,
            $method
        );

        $this->assertTrue(
            $this->api
                ->receivedParams['off_session']
        );

        $this->assertTrue(
            $this->api
                ->receivedParams['confirm']
        );

        $this->assertArrayNotHasKey(
            'error_on_requires_action',
            $this->api->receivedParams
        );
    }

    public function test_processor_sends_doctotal_metadata(): void
    {
        [$payment, $customer, $method] =
            $this->scenario();

        $this->api->returnPaymentIntent(
            $this->successfulIntent()
        );

        $this->processor()->charge(
            $payment,
            $customer,
            $method
        );

        $metadata =
            $this->api
                ->receivedParams['metadata'];

        $this->assertSame(
            'payment-uuid-123',
            $metadata['doctotal_payment_uuid']
        );

        $this->assertSame(
            '10',
            $metadata['tenant_id']
        );

        $this->assertSame(
            '20',
            $metadata['subscription_id']
        );
    }

    public function test_processor_sends_payment_idempotency_key(): void
    {
        [$payment, $customer, $method] =
            $this->scenario();

        $this->api->returnPaymentIntent(
            $this->successfulIntent()
        );

        $this->processor()->charge(
            $payment,
            $customer,
            $method
        );

        $this->assertSame(
            'subscription:20:renewal:20260926163722',
            $this->api
                ->receivedOptions['idempotency_key']
        );
    }

    public function test_succeeded_payment_intent_returns_success_result(): void
    {
        [$payment, $customer, $method] =
            $this->scenario();

        $this->api->returnPaymentIntent(
            $this->successfulIntent(
                'pi_success_123'
            )
        );

        $result =
            $this->processor()->charge(
                $payment,
                $customer,
                $method
            );

        $this->assertTrue(
            $result->isSucceeded()
        );

        $this->assertSame(
            'pi_success_123',
            $result->providerPaymentId
        );
    }

    public function test_non_successful_payment_intent_returns_failed_result(): void
    {
        [$payment, $customer, $method] =
            $this->scenario();

        $intent = PaymentIntent::constructFrom([
            'id' =>
            'pi_failed_123',

            'status' =>
            'requires_payment_method',

            'last_payment_error' => [
                'code' =>
                'card_declined',

                'message' =>
                'Your card was declined.',
            ],
        ]);

        $this->api->returnPaymentIntent(
            $intent
        );

        $result =
            $this->processor()->charge(
                $payment,
                $customer,
                $method
            );

        $this->assertTrue(
            $result->isFailed()
        );

        $this->assertSame(
            'card_declined',
            $result->failureCode
        );

        $this->assertSame(
            'Your card was declined.',
            $result->failureMessage
        );

        $this->assertSame(
            'pi_failed_123',
            $result->providerPaymentId
        );
    }

    public function test_infrastructure_exception_is_not_converted_to_payment_failure(): void
    {
        [$payment, $customer, $method] =
            $this->scenario();

        $this->api->throwException(
            new RuntimeException(
                'Stripe network unavailable.'
            )
        );

        $this->expectException(
            RuntimeException::class
        );

        $this->processor()->charge(
            $payment,
            $customer,
            $method
        );
    }

    private function processor(): StripePaymentIntentProcessor
    {
        return app(
            StripePaymentIntentProcessor::class
        );
    }

    private function successfulIntent(
        string $id = 'pi_success'
    ): PaymentIntent {
        return PaymentIntent::constructFrom([
            'id' =>
            $id,

            'status' =>
            'succeeded',
        ]);
    }

    private function scenario(): array
    {
        $payment = new Payment([
            'amount' =>
            60000,

            'currency' =>
            'MXN',

            'idempotency_key' =>
            'subscription:20:renewal:20260926163722',
        ]);

        /*
         * No necesitamos persistir estos modelos.
         * Este test sólo verifica la frontera con Stripe.
         */
        $payment->id = 30;
        $payment->uuid =
            'payment-uuid-123';
        $payment->tenant_id =
            10;
        $payment->subscription_id =
            20;

        $customer =
            new BillingCustomer([
                'provider' =>
                BillingCustomer::PROVIDER_STRIPE,

                'provider_customer_id' =>
                'cus_test_123',
            ]);

        $method =
            new PaymentMethod([
                'provider' =>
                PaymentMethod::PROVIDER_STRIPE,

                'provider_payment_method_id' =>
                'pm_test_4242',

                'type' =>
                PaymentMethod::TYPE_CARD,

                'is_default' =>
                true,

                'is_active' =>
                true,
            ]);

        return [
            $payment,
            $customer,
            $method,
        ];
    }
}
