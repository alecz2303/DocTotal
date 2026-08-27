<?php

namespace Tests\Feature\Subscription;

use App\Actions\Billing\CreateManualSubscriptionPaymentIntent;
use App\Contracts\StripeCustomerApi;
use App\Contracts\StripePaymentIntentApi;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Stripe\PaymentIntent;
use Tests\Fakes\FakeStripeCustomerApi;
use Tests\Fakes\FakeStripePaymentIntentApi;
use Tests\TestCase;

class CreateManualSubscriptionPaymentIntentTest extends TestCase
{
    use RefreshDatabase;

    private FakeStripeCustomerApi $customers;

    private FakeStripePaymentIntentApi $paymentIntents;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'billing.plans.monthly' => [
                'amount' =>
                    60000,

                'currency' =>
                    'MXN',
            ],

            'billing.plans.yearly' => [
                'amount' =>
                    600000,

                'currency' =>
                    'MXN',
            ],
        ]);

        $this->customers =
            new FakeStripeCustomerApi();

        $this->paymentIntents =
            new FakeStripePaymentIntentApi();

        $this->app->instance(
            StripeCustomerApi::class,
            $this->customers
        );

        $this->app->instance(
            StripePaymentIntentApi::class,
            $this->paymentIntents
        );
    }

    public function test_creates_pending_manual_payment_without_subscription(): void
    {
        $tenant =
            $this->createTenant();

        $this->prepareStripe();

        $result =
            $this->action()->execute(
                $tenant,
                Subscription::BILLING_CYCLE_MONTHLY,
                Carbon::parse(
                    '2026-08-26 22:30:00'
                ),
                'manual-checkout-123',
            );

        $payment =
            $result->payment;

        $this->assertNull(
            $payment->subscription_id
        );

        $this->assertSame(
            Payment::STATUS_PENDING,
            $payment->status
        );

        $this->assertSame(
            Subscription::BILLING_CYCLE_MONTHLY,
            $payment->billing_cycle
        );

        $this->assertSame(
            60000,
            $payment->amount
        );

        $this->assertSame(
            'MXN',
            $payment->currency
        );
    }

    public function test_creates_payment_intent_with_manual_checkout_payload(): void
    {
        $tenant =
            $this->createTenant();

        $this->prepareStripe(
            customerId: 'cus_manual_123'
        );

        $this->action()->execute(
            $tenant,
            Subscription::BILLING_CYCLE_MONTHLY,
            now(),
            'manual-checkout-payload',
        );

        $params =
            $this->paymentIntents
                ->receivedParams;

        $this->assertSame(
            60000,
            $params['amount']
        );

        $this->assertSame(
            'mxn',
            $params['currency']
        );

        $this->assertSame(
            'cus_manual_123',
            $params['customer']
        );

        $this->assertSame(
            ['card'],
            $params['payment_method_types']
        );

        $this->assertArrayNotHasKey(
            'payment_method',
            $params
        );

        $this->assertArrayNotHasKey(
            'off_session',
            $params
        );

        $this->assertArrayNotHasKey(
            'confirm',
            $params
        );
    }

    public function test_returns_client_secret_and_stores_payment_intent_id(): void
    {
        $tenant =
            $this->createTenant();

        $this->prepareStripe(
            paymentIntentId: 'pi_manual_123',
            clientSecret: 'pi_manual_123_secret_xyz',
        );

        $result =
            $this->action()->execute(
                $tenant,
                Subscription::BILLING_CYCLE_MONTHLY,
                now(),
                'manual-checkout-secret',
            );

        $this->assertSame(
            'pi_manual_123_secret_xyz',
            $result->clientSecret
        );

        $this->assertSame(
            'pi_manual_123',
            $result
                ->payment
                ->provider_payment_id
        );
    }

    public function test_sends_manual_payment_metadata(): void
    {
        $tenant =
            $this->createTenant();

        $this->prepareStripe();

        $result =
            $this->action()->execute(
                $tenant,
                Subscription::BILLING_CYCLE_MONTHLY,
                now(),
                'manual-checkout-metadata',
            );

        $metadata =
            $this->paymentIntents
                ->receivedParams['metadata'];

        $this->assertSame(
            $result->payment->uuid,
            $metadata['doctotal_payment_uuid']
        );

        $this->assertSame(
            (string) $tenant->id,
            $metadata['doctotal_tenant_id']
        );

        $this->assertSame(
            Subscription::BILLING_CYCLE_MONTHLY,
            $metadata['billing_cycle']
        );

        $this->assertSame(
            'manual',
            $metadata['payment_mode']
        );
    }

    public function test_uses_payment_idempotency_key_with_stripe(): void
    {
        $tenant =
            $this->createTenant();

        $this->prepareStripe();

        $this->action()->execute(
            $tenant,
            Subscription::BILLING_CYCLE_MONTHLY,
            now(),
            'manual-checkout-idempotent',
        );

        $this->assertSame(
            'manual-checkout-idempotent',
            $this->paymentIntents
                ->receivedOptions['idempotency_key']
        );
    }

    public function test_same_idempotency_key_does_not_create_second_local_payment(): void
    {
        $tenant =
            $this->createTenant();

        $this->prepareStripe(
            paymentIntentId: 'pi_same_123'
        );

        $first =
            $this->action()->execute(
                $tenant,
                Subscription::BILLING_CYCLE_MONTHLY,
                now(),
                'manual-checkout-same-key',
            );

        $second =
            $this->action()->execute(
                $tenant,
                Subscription::BILLING_CYCLE_MONTHLY,
                now(),
                'manual-checkout-same-key',
            );

        $this->assertTrue(
            $first
                ->payment
                ->is(
                    $second->payment
                )
        );

        $this->assertSame(
            1,
            Payment::withoutGlobalScopes()
                ->where(
                    'idempotency_key',
                    'manual-checkout-same-key'
                )
                ->count()
        );
    }

    public function test_unavailable_billing_cycle_is_rejected(): void
    {
        $tenant =
            $this->createTenant();

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->action()->execute(
            $tenant,
            'weekly',
            now(),
            'manual-weekly-not-configured',
        );
    }

    public function test_manual_payment_does_not_save_payment_method_by_default(): void
    {
        $tenant =
            $this->createTenant();

        $this->prepareStripe();

        $this->action()->execute(
            $tenant,
            Subscription::BILLING_CYCLE_MONTHLY,
            now(),
            'manual-without-saving-card',
        );

        $params =
            $this->paymentIntents
                ->receivedParams;

        $this->assertArrayNotHasKey(
            'setup_future_usage',
            $params
        );

        $this->assertSame(
            '0',
            $params['metadata']['save_for_future']
        );
    }

    public function test_manual_payment_can_prepare_card_for_future_off_session_charges(): void
    {
        $tenant =
            $this->createTenant();

        $this->prepareStripe();

        $this->action()->execute(
            $tenant,
            Subscription::BILLING_CYCLE_MONTHLY,
            now(),
            'manual-saving-card',
            true,
        );

        $params =
            $this->paymentIntents
                ->receivedParams;

        $this->assertSame(
            'off_session',
            $params['setup_future_usage']
        );

        $this->assertSame(
            '1',
            $params['metadata']['save_for_future']
        );
    }

    public function test_manual_payment_saving_card_still_does_not_send_payment_method(): void
    {
        $tenant =
            $this->createTenant();

        $this->prepareStripe();

        $this->action()->execute(
            $tenant,
            Subscription::BILLING_CYCLE_MONTHLY,
            now(),
            'manual-new-card-for-future',
            true,
        );

        $this->assertArrayNotHasKey(
            'payment_method',
            $this->paymentIntents
                ->receivedParams
        );
    }

    public function test_yearly_checkout_uses_yearly_plan_amount(): void
    {
        $tenant =
            $this->createTenant();

        $this->prepareStripe();

        $result =
            $this->action()->execute(
                $tenant,
                Subscription::BILLING_CYCLE_YEARLY,
                now(),
                'manual-yearly-price',
            );

        $this->assertSame(
            600000,
            $result->payment->amount
        );

        $this->assertSame(
            'MXN',
            $result->payment->currency
        );

        $this->assertSame(
            600000,
            $this->paymentIntents
                ->receivedParams['amount']
        );

        $this->assertSame(
            'mxn',
            $this->paymentIntents
                ->receivedParams['currency']
        );
    }

    public function test_yearly_checkout_stores_yearly_billing_cycle(): void
    {
        $tenant =
            $this->createTenant();

        $this->prepareStripe();

        $result =
            $this->action()->execute(
                $tenant,
                Subscription::BILLING_CYCLE_YEARLY,
                now(),
                'manual-yearly-cycle',
            );

        $this->assertSame(
            Subscription::BILLING_CYCLE_YEARLY,
            $result->payment->billing_cycle
        );

        $this->assertSame(
            Subscription::BILLING_CYCLE_YEARLY,
            $this->paymentIntents
                ->receivedParams[
                    'metadata'
                ][
                    'billing_cycle'
                ]
        );
    }

    private function action(): CreateManualSubscriptionPaymentIntent
    {
        return app(
            CreateManualSubscriptionPaymentIntent::class
        );
    }

    private function createTenant(): Tenant
    {
        return Tenant::create([
            'name' =>
                'Consultorio Checkout Manual',

            'slug' =>
                'manual-checkout-' .
                uniqid(),

            'status' =>
                'trial',

            'onboarding_completed_at' =>
                now(),
        ]);
    }

    private function prepareStripe(
        string $customerId =
            'cus_manual_test',
        string $paymentIntentId =
            'pi_manual_test',
        string $clientSecret =
            'pi_manual_test_secret_123',
    ): void {
        $this->customers->returnCustomer(
            $customerId
        );

        $this->paymentIntents
            ->returnPaymentIntent(
                PaymentIntent::constructFrom([
                    'id' =>
                        $paymentIntentId,

                    'status' =>
                        'requires_payment_method',

                    'client_secret' =>
                        $clientSecret,
                ])
            );
    }
}
