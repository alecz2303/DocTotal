<?php

namespace Tests\Feature\Subscription;

use App\Actions\Billing\CleanupAbandonedManualSubscriptionPayments;
use App\Contracts\StripePaymentIntentApi;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\BillingCustomer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use RuntimeException;
use Stripe\PaymentIntent;
use Tests\Fakes\FakeStripePaymentIntentApi;
use Tests\TestCase;

class CleanupAbandonedManualSubscriptionPaymentsTest extends TestCase
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

    public function test_cancels_expired_pending_manual_checkout(): void
    {
        $tenant =
            $this->createTenant();

        $payment =
            $this->createPendingPayment(
                $tenant,
                Carbon::parse(
                    '2026-08-28 10:00:00'
                )
            );

        $this->paymentIntents
            ->returnRetrievedPaymentIntent(
                PaymentIntent::constructFrom([
                    'id' =>
                    $payment->provider_payment_id,

                    'status' =>
                    'requires_payment_method',
                ])
            );

        $this->prepareCanceledStripePaymentIntent(
            $payment->provider_payment_id
        );

        $result =
            $this->action()->execute(
                Carbon::parse(
                    '2026-08-28 12:00:00'
                ),
                Carbon::parse(
                    '2026-08-29 12:00:00'
                )
            );

        $payment->refresh();

        $this->assertSame(
            Payment::STATUS_CANCELED,
            $payment->status
        );

        $this->assertSame(
            1,
            $result['processed']
        );

        $this->assertSame(
            0,
            $result['errors']
        );
    }

    public function test_does_not_cancel_recent_manual_checkout(): void
    {
        $tenant =
            $this->createTenant();

        $payment =
            $this->createPendingPayment(
                $tenant,
                Carbon::parse(
                    '2026-08-29 11:00:00'
                )
            );

        $result =
            $this->action()->execute(
                Carbon::parse(
                    '2026-08-28 12:00:00'
                ),
                Carbon::parse(
                    '2026-08-29 12:00:00'
                )
            );

        $payment->refresh();

        $this->assertSame(
            Payment::STATUS_PENDING,
            $payment->status
        );

        $this->assertSame(
            0,
            $result['processed']
        );

        $this->assertSame(
            0,
            $result['errors']
        );

        $this->assertNull(
            $this->paymentIntents
                ->receivedCancelPaymentIntentId
        );
    }

    public function test_does_not_cancel_recovery_payment(): void
    {
        $tenant =
            $this->createTenant();

        $subscription =
            Subscription::withoutGlobalScopes()
            ->create([
                'tenant_id' =>
                $tenant->id,

                'billing_cycle' =>
                Subscription::BILLING_CYCLE_MONTHLY,

                'billing_amount' =>
                60000,

                'billing_currency' =>
                'MXN',

                'status' =>
                Subscription::STATUS_PAST_DUE,

                'starts_at' =>
                Carbon::parse(
                    '2026-08-01 00:00:00'
                ),

                'current_period_starts_at' =>
                Carbon::parse(
                    '2026-08-01 00:00:00'
                ),

                'current_period_ends_at' =>
                Carbon::parse(
                    '2026-09-01 00:00:00'
                ),

                'next_billing_at' =>
                Carbon::parse(
                    '2026-09-01 00:00:00'
                ),

                'cancel_at_period_end' =>
                false,

                'retry_count' =>
                0,
            ]);

        $payment =
            $this->createPendingPayment(
                $tenant,
                Carbon::parse(
                    '2026-08-27 10:00:00'
                ),
                $subscription->id
            );

        $result =
            $this->action()->execute(
                Carbon::parse(
                    '2026-08-28 12:00:00'
                ),
                Carbon::parse(
                    '2026-08-29 12:00:00'
                )
            );

        $payment->refresh();

        $this->assertSame(
            Payment::STATUS_PENDING,
            $payment->status
        );

        $this->assertSame(
            0,
            $result['processed']
        );
    }

    public function test_stripe_failure_does_not_stop_other_expired_checkouts(): void
    {
        $firstTenant =
            $this->createTenant(
                'Consultorio Uno'
            );

        $secondTenant =
            $this->createTenant(
                'Consultorio Dos'
            );

        $first =
            $this->createPendingPayment(
                $firstTenant,
                Carbon::parse(
                    '2026-08-27 10:00:00'
                ),
                null,
                'pi_cleanup_first'
            );

        $second =
            $this->createPendingPayment(
                $secondTenant,
                Carbon::parse(
                    '2026-08-27 11:00:00'
                ),
                null,
                null
            );

        /*
         * El primero falla al cancelar en Stripe.
         *
         * El segundo no tiene PaymentIntent, por lo que
         * puede cancelarse localmente y demuestra que el
         * proceso continúa después del error.
         */
        $this->paymentIntents
            ->throwException(
                new RuntimeException(
                    'Stripe cancel failed.'
                )
            );

        $result =
            $this->action()->execute(
                Carbon::parse(
                    '2026-08-28 12:00:00'
                ),
                Carbon::parse(
                    '2026-08-29 12:00:00'
                )
            );

        $first->refresh();
        $second->refresh();

        $this->assertSame(
            Payment::STATUS_PENDING,
            $first->status
        );

        $this->assertSame(
            Payment::STATUS_CANCELED,
            $second->status
        );

        $this->assertSame(
            1,
            $result['processed']
        );

        $this->assertSame(
            1,
            $result['errors']
        );
    }

    public function test_reconciles_expired_checkout_that_already_succeeded_in_stripe(): void
    {
        $tenant =
            $this->createTenant(
                'Consultorio Checkout Cobrado'
            );

        BillingCustomer::withoutGlobalScopes()
            ->create([
                'tenant_id' =>
                $tenant->id,

                'provider' =>
                BillingCustomer::PROVIDER_STRIPE,

                'provider_customer_id' =>
                'cus_cleanup_succeeded',
            ]);

        $payment =
            $this->createPendingPayment(
                $tenant,
                Carbon::parse(
                    '2026-08-27 10:00:00'
                ),
                null,
                'pi_cleanup_succeeded'
            );

        $this->paymentIntents
            ->returnRetrievedPaymentIntent(
                PaymentIntent::constructFrom([
                    'id' =>
                    'pi_cleanup_succeeded',

                    'amount' =>
                    $payment->amount,

                    'currency' =>
                    strtolower(
                        $payment->currency
                    ),

                    'customer' =>
                    'cus_cleanup_succeeded',

                    'status' =>
                    'succeeded',

                    'metadata' => [
                        'doctotal_payment_uuid' =>
                        $payment->uuid,

                        'doctotal_tenant_id' =>
                        (string) $tenant->id,

                        'billing_cycle' =>
                        $payment->billing_cycle,

                        'payment_mode' =>
                        'manual',
                    ],
                ])
            );

        $processedAt =
            Carbon::parse(
                '2026-08-29 12:00:00'
            );

        $result =
            $this->action()->execute(
                Carbon::parse(
                    '2026-08-28 12:00:00'
                ),
                $processedAt
            );

        $payment->refresh();

        $this->assertTrue(
            $payment->isSucceeded()
        );

        $this->assertNotNull(
            $payment->paid_at
        );

        $this->assertNotNull(
            $payment->subscription_id
        );

        $this->assertTrue(
            $payment->paid_at->equalTo(
                $processedAt
            )
        );

        $subscription =
            $payment->subscription;

        $this->assertNotNull(
            $subscription
        );

        $this->assertTrue(
            $subscription->isActive()
        );

        $this->assertSame(
            1,
            $result['processed']
        );

        $this->assertSame(
            0,
            $result['canceled']
        );

        $this->assertSame(
            1,
            $result['reconciled']
        );

        $this->assertSame(
            0,
            $result['errors']
        );

        $this->assertNull(
            $this->paymentIntents
                ->receivedCancelPaymentIntentId
        );
    }

    private function action(): CleanupAbandonedManualSubscriptionPayments
    {
        return app(
            CleanupAbandonedManualSubscriptionPayments::class
        );
    }

    private function createTenant(
        string $name = 'Consultorio Cleanup',
    ): Tenant {
        return Tenant::create([
            'name' =>
            $name,

            'slug' =>
            'cleanup-checkout-' .
                uniqid(),

            'status' =>
            'trial',

            'currency' =>
            'MXN',

            'onboarding_completed_at' =>
            now(),
        ]);
    }

    private function createPendingPayment(
        Tenant $tenant,
        Carbon $attemptedAt,
        ?int $subscriptionId = null,
        ?string $paymentIntentId = 'pi_cleanup_test',
    ): Payment {
        return Payment::withoutGlobalScopes()
            ->create([
                'tenant_id' =>
                $tenant->id,

                'subscription_id' =>
                $subscriptionId,

                'billing_cycle' =>
                Subscription::BILLING_CYCLE_MONTHLY,

                'gross_amount' =>
                60000,

                'referral_discount_amount' =>
                0,

                'promotional_credit_amount' =>
                0,

                'amount' =>
                60000,

                'currency' =>
                'MXN',

                'status' =>
                Payment::STATUS_PENDING,

                'attempted_at' =>
                $attemptedAt,

                'provider' =>
                'stripe',

                'provider_payment_id' =>
                $paymentIntentId,

                'idempotency_key' =>
                'cleanup-payment-' .
                    uniqid(),
            ]);
    }

    private function prepareCanceledStripePaymentIntent(
        string $paymentIntentId,
    ): void {
        $this->paymentIntents
            ->returnCanceledPaymentIntent(
                PaymentIntent::constructFrom([
                    'id' =>
                    $paymentIntentId,

                    'status' =>
                    'canceled',
                ])
            );
    }
}
