<?php

namespace Tests\Feature\Subscription;

use App\Actions\Billing\UpdateManualPaymentFutureUsage;
use App\Contracts\StripePaymentIntentApi;
use App\Models\BillingCustomer;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Stripe\PaymentIntent;
use Tests\Fakes\FakeStripePaymentIntentApi;
use Tests\TestCase;

class UpdateManualPaymentFutureUsageTest extends TestCase
{
    use RefreshDatabase;

    private FakeStripePaymentIntentApi $paymentIntents;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentIntents =
            new FakeStripePaymentIntentApi();

        $this->app->instance(
            StripePaymentIntentApi::class,
            $this->paymentIntents
        );
    }

    public function test_can_enable_future_usage_for_pending_manual_payment(): void
    {
        [$tenant, $payment] =
            $this->scenario();

        $this->paymentIntents
            ->returnRetrievedPaymentIntent(
                $this->pendingIntent(
                    $tenant,
                    $payment
                )
            );

        $this->paymentIntents
            ->returnUpdatedPaymentIntent(
                $this->pendingIntent(
                    $tenant,
                    $payment,
                    true
                )
            );

        app(
            UpdateManualPaymentFutureUsage::class
        )->execute(
            $tenant,
            $payment,
            true
        );

        $this->assertSame(
            $payment->provider_payment_id,
            $this->paymentIntents
                ->receivedUpdatePaymentIntentId
        );

        $this->assertSame(
            'off_session',
            $this->paymentIntents
                ->receivedUpdateParams['setup_future_usage']
        );

        $this->assertSame(
            '1',
            $this->paymentIntents
                ->receivedUpdateParams['metadata']['save_for_future']
        );
    }

    public function test_can_disable_future_usage_for_pending_manual_payment(): void
    {
        [$tenant, $payment] =
            $this->scenario();

        $this->paymentIntents
            ->returnRetrievedPaymentIntent(
                $this->pendingIntent(
                    $tenant,
                    $payment,
                    true
                )
            );

        $this->paymentIntents
            ->returnUpdatedPaymentIntent(
                $this->pendingIntent(
                    $tenant,
                    $payment,
                    false
                )
            );

        app(
            UpdateManualPaymentFutureUsage::class
        )->execute(
            $tenant,
            $payment,
            false
        );

        $params =
            $this->paymentIntents
            ->receivedUpdateParams;

        $this->assertSame(
            '',
            $params['setup_future_usage']
        );

        $this->assertSame(
            '0',
            $params['metadata']['save_for_future']
        );
    }

    public function test_updating_future_usage_preserves_payment_metadata(): void
    {
        [$tenant, $payment] =
            $this->scenario();

        $this->paymentIntents
            ->returnRetrievedPaymentIntent(
                $this->pendingIntent(
                    $tenant,
                    $payment
                )
            );

        $this->paymentIntents
            ->returnUpdatedPaymentIntent(
                $this->pendingIntent(
                    $tenant,
                    $payment,
                    true
                )
            );

        app(
            UpdateManualPaymentFutureUsage::class
        )->execute(
            $tenant,
            $payment,
            true
        );

        $metadata =
            $this->paymentIntents
                ->receivedUpdateParams['metadata'];

        $this->assertSame(
            $payment->uuid,
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

        $this->assertSame(
            '1',
            $metadata['save_for_future']
        );
    }

    public function test_succeeded_payment_cannot_change_future_usage(): void
    {
        [$tenant, $payment] =
            $this->scenario();

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
            UpdateManualPaymentFutureUsage::class
        )->execute(
            $tenant,
            $payment,
            true
        );
    }

    private function scenario(): array
    {
        $tenant =
            Tenant::create([
                'name' =>
                'Consultorio Future Usage',

                'slug' =>
                'future-usage-' .
                    uniqid(),

                'status' =>
                'trial',

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
                'cus_future_usage',
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
                Payment::STATUS_PENDING,

                'attempted_at' =>
                now(),

                'provider' =>
                'stripe',

                'provider_payment_id' =>
                'pi_future_usage',

                'idempotency_key' =>
                'future-usage-' .
                    uniqid(),
            ]);

        return [
            $tenant,
            $payment,
        ];
    }

    private function pendingIntent(
        Tenant $tenant,
        Payment $payment,
        bool $saveForFuture = false,
    ): PaymentIntent {
        return PaymentIntent::constructFrom([
            'id' =>
            $payment->provider_payment_id,

            'amount' =>
            $payment->amount,

            'currency' =>
            strtolower(
                $payment->currency
            ),

            'customer' =>
            'cus_future_usage',

            'status' =>
            'requires_payment_method',

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
}
