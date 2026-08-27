<?php

namespace Tests\Feature\Subscription;

use App\Contracts\StripePaymentIntentProcessor;
use App\Models\BillingCustomer;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\Billing\StripePaymentGateway;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\Fakes\FakeStripePaymentIntentProcessor;
use Tests\TestCase;

class StripePaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    private FakeStripePaymentIntentProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor =
            new FakeStripePaymentIntentProcessor();

        $this->app->instance(
            StripePaymentIntentProcessor::class,
            $this->processor
        );
    }

    public function test_gateway_name_is_stripe(): void
    {
        $gateway =
            app(StripePaymentGateway::class);

        $this->assertSame(
            'stripe',
            $gateway->name()
        );
    }

    public function test_gateway_processes_successful_charge(): void
    {
        $payment =
            $this->createCompleteBillingScenario();

        $this->processor->succeed(
            'pi_success_123'
        );

        $result =
            app(
                StripePaymentGateway::class
            )->charge($payment);

        $this->assertTrue(
            $result->isSucceeded()
        );

        $this->assertSame(
            'pi_success_123',
            $result->providerPaymentId
        );
    }

    public function test_gateway_processes_failed_charge(): void
    {
        $payment =
            $this->createCompleteBillingScenario();

        $this->processor->fail(
            'card_declined',
            'Tarjeta rechazada.',
            'pi_failed_123'
        );

        $result =
            app(
                StripePaymentGateway::class
            )->charge($payment);

        $this->assertTrue(
            $result->isFailed()
        );

        $this->assertSame(
            'card_declined',
            $result->failureCode
        );

        $this->assertSame(
            'pi_failed_123',
            $result->providerPaymentId
        );
    }

    public function test_gateway_passes_correct_payment_to_processor(): void
    {
        $payment =
            $this->createCompleteBillingScenario();

        $this->processor->succeed();

        app(
            StripePaymentGateway::class
        )->charge($payment);

        $this->assertTrue(
            $this->processor
                ->receivedPayment
                ->is($payment)
        );
    }

    public function test_gateway_passes_stripe_customer_to_processor(): void
    {
        $payment =
            $this->createCompleteBillingScenario();

        $this->processor->succeed();

        app(
            StripePaymentGateway::class
        )->charge($payment);

        $this->assertSame(
            'cus_test_123',
            $this->processor
                ->receivedCustomer
                ->provider_customer_id
        );
    }

    public function test_gateway_passes_default_payment_method_to_processor(): void
    {
        $payment =
            $this->createCompleteBillingScenario();

        $this->processor->succeed();

        app(
            StripePaymentGateway::class
        )->charge($payment);

        $this->assertSame(
            'pm_test_4242',
            $this->processor
                ->receivedPaymentMethod
                ->provider_payment_method_id
        );
    }

    public function test_non_pending_payment_cannot_be_charged(): void
    {
        $payment =
            $this->createCompleteBillingScenario();

        $payment->update([
            'status' =>
            Payment::STATUS_SUCCEEDED,

            'paid_at' =>
            now(),
        ]);

        $this->expectException(
            LogicException::class
        );

        app(
            StripePaymentGateway::class
        )->charge($payment);
    }

    public function test_tenant_without_stripe_customer_returns_failed_result(): void
    {
        $payment =
            $this->createCompleteBillingScenario(
                createCustomer: false
            );

        $result =
            app(
                StripePaymentGateway::class
            )->charge($payment);

        $this->assertTrue(
            $result->isFailed()
        );

        $this->assertSame(
            'stripe_customer_missing',
            $result->failureCode
        );

        $this->assertSame(
            'El tenant no tiene un cliente Stripe configurado.',
            $result->failureMessage
        );

        $this->assertNull(
            $result->providerPaymentId
        );
    }

    public function test_tenant_without_default_payment_method_returns_failed_result(): void
    {
        $payment =
            $this->createCompleteBillingScenario(
                createPaymentMethod: false
            );

        $result =
            app(
                StripePaymentGateway::class
            )->charge($payment);

        $this->assertTrue(
            $result->isFailed()
        );

        $this->assertSame(
            'payment_method_missing',
            $result->failureCode
        );

        $this->assertSame(
            'El tenant no tiene un método de pago predeterminado.',
            $result->failureMessage
        );

        $this->assertNull(
            $result->providerPaymentId
        );
    }

    private function createCompleteBillingScenario(
        bool $createCustomer = true,
        bool $createPaymentMethod = true,
    ): Payment {
        $tenant = Tenant::create([
            'name' =>
            'Consultorio Stripe',

            'slug' =>
            'consultorio-stripe-' .
                uniqid(),

            'status' =>
            'active',

            'onboarding_completed_at' =>
            now(),
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        if ($createCustomer) {
            BillingCustomer::create([
                'provider' =>
                BillingCustomer::PROVIDER_STRIPE,

                'provider_customer_id' =>
                'cus_test_123',
            ]);
        }

        if ($createPaymentMethod) {
            PaymentMethod::create([
                'provider' =>
                PaymentMethod::PROVIDER_STRIPE,

                'provider_payment_method_id' =>
                'pm_test_4242',

                'type' =>
                PaymentMethod::TYPE_CARD,

                'brand' =>
                'visa',

                'last_four' =>
                '4242',

                'expires_month' =>
                12,

                'expires_year' =>
                2030,

                'is_default' =>
                true,

                'is_active' =>
                true,
            ]);
        }

        $subscription =
            Subscription::create([
                'billing_cycle' =>
                Subscription::BILLING_CYCLE_MONTHLY,

                'billing_amount' =>
                60000,

                'billing_currency' =>
                'MXN',

                'status' =>
                Subscription::STATUS_ACTIVE,

                'starts_at' =>
                now(),

                'current_period_starts_at' =>
                now(),

                'current_period_ends_at' =>
                now()->addMonth(),

                'next_billing_at' =>
                now()->addMonth(),

                'retry_count' =>
                0,

                'cancel_at_period_end' =>
                false,
            ]);

        return Payment::create([
            'subscription_id' =>
            $subscription->id,

            'amount' =>
            60000,

            'currency' =>
            'MXN',

            'status' =>
            Payment::STATUS_PENDING,

            'attempted_at' =>
            now(),

            'provider' =>
            'stripe',

            'idempotency_key' =>
            'stripe-test-' . uniqid(),
        ]);
    }
}
