<?php

namespace Tests\Feature\Subscription;

use App\Actions\Billing\RegisterManualPaymentMethodForFuture;
use App\Contracts\StripePaymentIntentApi;
use App\Contracts\StripePaymentMethodApi;
use App\Models\BillingCustomer;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Stripe\PaymentIntent;
use Tests\Fakes\FakeStripePaymentIntentApi;
use Tests\Fakes\FakeStripePaymentMethodApi;
use Tests\TestCase;

class RegisterManualPaymentMethodForFutureTest extends TestCase
{
    use RefreshDatabase;

    private FakeStripePaymentIntentApi $paymentIntents;

    private FakeStripePaymentMethodApi $paymentMethods;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentIntents =
            new FakeStripePaymentIntentApi();

        $this->paymentMethods =
            new FakeStripePaymentMethodApi();

        $this->app->instance(
            StripePaymentIntentApi::class,
            $this->paymentIntents
        );

        $this->app->instance(
            StripePaymentMethodApi::class,
            $this->paymentMethods
        );
    }

    public function test_saves_card_when_user_requested_it(): void
    {
        [$tenant, $payment] =
            $this->scenario();

        $this->prepareIntent(
            $tenant,
            $payment,
            true
        );

        $this->paymentMethods->returnCard(
            id: 'pm_manual_saved',
            customer: 'cus_manual_future',
        );

        $this->action()->execute(
            $tenant,
            $payment
        );

        $saved =
            $tenant->defaultPaymentMethod();

        $this->assertNotNull(
            $saved
        );

        $this->assertSame(
            'pm_manual_saved',
            $saved->provider_payment_method_id
        );

        $this->assertTrue(
            $saved->isDefault()
        );

        $this->assertTrue(
            $saved->isActive()
        );
    }

    public function test_does_not_save_card_when_user_did_not_request_it(): void
    {
        [$tenant, $payment] =
            $this->scenario();

        $this->prepareIntent(
            $tenant,
            $payment,
            false
        );

        $this->action()->execute(
            $tenant,
            $payment
        );

        $this->assertNull(
            $tenant->defaultPaymentMethod()
        );

        $this->assertSame(
            0,
            PaymentMethod::withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $tenant->id
                )
                ->count()
        );
    }

    public function test_uses_payment_method_from_payment_intent(): void
    {
        [$tenant, $payment] =
            $this->scenario();

        $this->prepareIntent(
            $tenant,
            $payment,
            true
        );

        $this->paymentMethods->returnCard(
            id: 'pm_manual_saved',
            customer: 'cus_manual_future',
        );

        $this->action()->execute(
            $tenant,
            $payment
        );

        $this->assertSame(
            'pm_manual_saved',
            $this->paymentMethods
                ->receivedPaymentMethodId
        );
    }

    public function test_pending_payment_cannot_save_card(): void
    {
        [$tenant, $payment] =
            $this->scenario();

        $payment->update([
            'status' =>
            Payment::STATUS_PENDING,
        ]);

        $this->expectException(
            LogicException::class
        );

        $this->action()->execute(
            $tenant,
            $payment
        );
    }

    public function test_payment_from_another_tenant_cannot_save_card(): void
    {
        [$tenant, $payment] =
            $this->scenario();

        $otherTenant =
            Tenant::create([
                'name' =>
                'Otro consultorio',

                'slug' =>
                'otro-' . uniqid(),

                'status' =>
                'active',

                'onboarding_completed_at' =>
                now(),
            ]);

        $this->expectException(
            LogicException::class
        );

        $this->action()->execute(
            $otherTenant,
            $payment
        );
    }

    public function test_save_request_requires_payment_method_from_stripe(): void
    {
        [$tenant, $payment] =
            $this->scenario();

        $intent =
            $this->intent(
                $tenant,
                $payment,
                true
            );

        $intent->payment_method =
            null;

        $this->paymentIntents
            ->returnRetrievedPaymentIntent(
                $intent
            );

        $this->expectException(
            LogicException::class
        );

        $this->action()->execute(
            $tenant,
            $payment
        );
    }

    private function action(): RegisterManualPaymentMethodForFuture
    {
        return app(
            RegisterManualPaymentMethodForFuture::class
        );
    }

    private function prepareIntent(
        Tenant $tenant,
        Payment $payment,
        bool $saveForFuture,
    ): void {
        $this->paymentIntents
            ->returnRetrievedPaymentIntent(
                $this->intent(
                    $tenant,
                    $payment,
                    $saveForFuture
                )
            );
    }

    private function intent(
        Tenant $tenant,
        Payment $payment,
        bool $saveForFuture,
    ): PaymentIntent {
        return PaymentIntent::constructFrom([
            'id' =>
            'pi_manual_future',

            'amount' =>
            60000,

            'currency' =>
            'mxn',

            'customer' =>
            'cus_manual_future',

            'status' =>
            'succeeded',

            'payment_method' =>
            'pm_manual_saved',

            'metadata' => [
                'doctotal_payment_uuid' =>
                $payment->uuid,

                'doctotal_tenant_id' =>
                (string) $tenant->id,

                'billing_cycle' =>
                Subscription::BILLING_CYCLE_MONTHLY,

                'payment_mode' =>
                'manual',

                'save_for_future' =>
                $saveForFuture
                    ? '1'
                    : '0',
            ],
        ]);
    }

    private function scenario(): array
    {
        $tenant =
            Tenant::create([
                'name' =>
                'Consultorio Future Card',

                'slug' =>
                'future-card-' .
                    uniqid(),

                'status' =>
                'active',

                'onboarding_completed_at' =>
                now(),
            ]);

        BillingCustomer::withoutGlobalScopes()
            ->create([
                'tenant_id' =>
                $tenant->id,

                'provider' =>
                BillingCustomer::PROVIDER_STRIPE,

                'provider_customer_id' =>
                'cus_manual_future',
            ]);

        $payment =
            Payment::withoutGlobalScopes()
            ->create([
                'tenant_id' =>
                $tenant->id,

                'subscription_id' =>
                null,

                'billing_cycle' =>
                Subscription::BILLING_CYCLE_MONTHLY,

                'amount' =>
                60000,

                'currency' =>
                'MXN',

                'status' =>
                Payment::STATUS_SUCCEEDED,

                'attempted_at' =>
                now(),

                'paid_at' =>
                now(),

                'provider' =>
                'stripe',

                'provider_payment_id' =>
                'pi_manual_future',

                'idempotency_key' =>
                'future-card-' .
                    uniqid(),
            ]);

        return [
            $tenant,
            $payment,
        ];
    }
}
