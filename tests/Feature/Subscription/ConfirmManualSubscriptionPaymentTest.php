<?php

namespace Tests\Feature\Subscription;

use App\Actions\Billing\ConfirmManualSubscriptionPayment;
use App\Contracts\StripePaymentIntentApi;
use App\Models\BillingCustomer;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use LogicException;
use Stripe\PaymentIntent;
use Tests\Fakes\FakeStripePaymentIntentApi;
use Tests\TestCase;

class ConfirmManualSubscriptionPaymentTest extends TestCase
{
    use RefreshDatabase;

    private FakeStripePaymentIntentApi $stripe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stripe =
            new FakeStripePaymentIntentApi();

        $this->app->instance(
            StripePaymentIntentApi::class,
            $this->stripe
        );
    }

    public function test_successful_manual_payment_is_confirmed(): void
    {
        [$tenant, $payment] =
            $this->scenario();

        $this->stripe
            ->returnRetrievedPaymentIntent(
                $this->successfulIntent(
                    $tenant,
                    $payment
                )
            );

        $result =
            $this->action()->execute(
                $tenant,
                $payment,
                Carbon::parse(
                    '2026-08-26 23:10:00'
                )
            );

        $this->assertTrue(
            $result->isSucceeded()
        );

        $this->assertNotNull(
            $result->paid_at
        );

        $this->assertSame(
            'pi_manual_success',
            $result->provider_payment_id
        );
    }

    public function test_successful_manual_payment_activates_subscription(): void
    {
        [$tenant, $payment] =
            $this->scenario();

        $confirmedAt =
            Carbon::parse(
                '2026-08-26 23:10:00'
            );

        $this->stripe
            ->returnRetrievedPaymentIntent(
                $this->successfulIntent(
                    $tenant,
                    $payment
                )
            );

        $result =
            $this->action()->execute(
                $tenant,
                $payment,
                $confirmedAt
            );

        $subscription =
            $result->subscription;

        $this->assertNotNull(
            $subscription
        );

        $this->assertTrue(
            $subscription->isActive()
        );

        $this->assertSame(
            60000,
            $subscription->billing_amount
        );

        $this->assertSame(
            'MXN',
            $subscription->billing_currency
        );

        $this->assertTrue(
            $subscription
                ->current_period_starts_at
                ->equalTo(
                    $confirmedAt
                )
        );
    }

    public function test_successful_yearly_manual_payment_activates_yearly_subscription(): void
    {
        [$tenant, $payment] =
            $this->scenario(
                billingCycle:
                    Subscription::BILLING_CYCLE_YEARLY,

                amount:
                    600000,
            );

        $confirmedAt =
            Carbon::parse(
                '2026-08-27 10:00:00'
            );

        $this->stripe
            ->returnRetrievedPaymentIntent(
                $this->successfulIntent(
                    $tenant,
                    $payment
                )
            );

        $result =
            $this->action()->execute(
                $tenant,
                $payment,
                $confirmedAt
            );

        $subscription =
            $result->subscription;

        $this->assertNotNull(
            $subscription
        );

        $this->assertTrue(
            $subscription->isActive()
        );

        $this->assertTrue(
            $subscription->isYearly()
        );

        $this->assertSame(
            600000,
            $subscription->billing_amount
        );

        $this->assertSame(
            'MXN',
            $subscription->billing_currency
        );

        $this->assertTrue(
            $subscription
                ->current_period_starts_at
                ->equalTo(
                    $confirmedAt
                )
        );

        $this->assertTrue(
            $subscription
                ->current_period_ends_at
                ->equalTo(
                    $confirmedAt
                        ->copy()
                        ->addYearNoOverflow()
                )
        );

        $this->assertTrue(
            $subscription
                ->next_billing_at
                ->equalTo(
                    $confirmedAt
                        ->copy()
                        ->addYearNoOverflow()
                )
        );
    }

    public function test_confirmation_retrieves_expected_payment_intent(): void
    {
        [$tenant, $payment] =
            $this->scenario();

        $this->stripe
            ->returnRetrievedPaymentIntent(
                $this->successfulIntent(
                    $tenant,
                    $payment
                )
            );

        $this->action()->execute(
            $tenant,
            $payment,
            now()
        );

        $this->assertSame(
            'pi_manual_success',
            $this->stripe
                ->receivedPaymentIntentId
        );
    }

    public function test_payment_intent_amount_must_match_payment(): void
    {
        [$tenant, $payment] =
            $this->scenario();

        $intent =
            $this->successfulIntent(
                $tenant,
                $payment
            );

        $intent->amount =
            50000;

        $this->stripe
            ->returnRetrievedPaymentIntent(
                $intent
            );

        $this->expectException(
            LogicException::class
        );

        $this->action()->execute(
            $tenant,
            $payment,
            now()
        );
    }

    public function test_payment_intent_customer_must_match_tenant_customer(): void
    {
        [$tenant, $payment] =
            $this->scenario();

        $intent =
            $this->successfulIntent(
                $tenant,
                $payment
            );

        $intent->customer =
            'cus_someone_else';

        $this->stripe
            ->returnRetrievedPaymentIntent(
                $intent
            );

        $this->expectException(
            LogicException::class
        );

        $this->action()->execute(
            $tenant,
            $payment,
            now()
        );
    }

    public function test_payment_intent_metadata_must_match_payment(): void
    {
        [$tenant, $payment] =
            $this->scenario();

        $intent =
            $this->successfulIntent(
                $tenant,
                $payment
            );

        $intent->metadata =
            [
                'doctotal_payment_uuid' =>
                    'wrong-payment',

                'doctotal_tenant_id' =>
                    (string) $tenant->id,

                'payment_mode' =>
                    'manual',
            ];

        $this->stripe
            ->returnRetrievedPaymentIntent(
                $intent
            );

        $this->expectException(
            LogicException::class
        );

        $this->action()->execute(
            $tenant,
            $payment,
            now()
        );
    }

    public function test_non_succeeded_payment_intent_does_not_activate_subscription(): void
    {
        [$tenant, $payment] =
            $this->scenario();

        $intent =
            $this->successfulIntent(
                $tenant,
                $payment
            );

        $intent->status =
            'requires_payment_method';

        $this->stripe
            ->returnRetrievedPaymentIntent(
                $intent
            );

        try {
            $this->action()->execute(
                $tenant,
                $payment,
                now()
            );

            $this->fail(
                'Se esperaba una LogicException.'
            );
        } catch (LogicException) {
            //
        }

        $this->assertTrue(
            $payment
                ->refresh()
                ->isPending()
        );

        $this->assertSame(
            0,
            Subscription::withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $tenant->id
                )
                ->count()
        );
    }

    public function test_confirming_succeeded_payment_again_is_idempotent(): void
    {
        [$tenant, $payment] =
            $this->scenario();

        $this->stripe
            ->returnRetrievedPaymentIntent(
                $this->successfulIntent(
                    $tenant,
                    $payment
                )
            );

        $first =
            $this->action()->execute(
                $tenant,
                $payment,
                now()
            );

        $second =
            $this->action()->execute(
                $tenant,
                $first,
                now()->addSecond()
            );

        $this->assertTrue(
            $first->is($second)
        );

        $this->assertSame(
            1,
            Subscription::withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $tenant->id
                )
                ->count()
        );
    }

    private function action(): ConfirmManualSubscriptionPayment
    {
        return app(
            ConfirmManualSubscriptionPayment::class
        );
    }

    private function scenario(
        string $billingCycle =
            Subscription::BILLING_CYCLE_MONTHLY,
        int $amount =
            60000,
    ): array {
        $tenant =
            Tenant::create([
                'name' =>
                    'Consultorio Pago Manual',

                'slug' =>
                    'pago-manual-' .
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
                    'cus_manual_123',
            ]);

        $payment =
            Payment::withoutGlobalScopes()
                ->create([
                    'tenant_id' =>
                        $tenant->id,

                    'subscription_id' =>
                        null,

                    'billing_cycle' =>
                        $billingCycle,

                    'amount' =>
                        $amount,

                    'currency' =>
                        'MXN',

                    'status' =>
                        Payment::STATUS_PENDING,

                    'attempted_at' =>
                        now(),

                    'provider' =>
                        'stripe',

                    'provider_payment_id' =>
                        'pi_manual_success',

                    'idempotency_key' =>
                        'manual-confirm-' .
                        uniqid(),
                ]);

        return [
            $tenant,
            $payment,
        ];
    }

    private function successfulIntent(
        Tenant $tenant,
        Payment $payment,
    ): PaymentIntent {
        return PaymentIntent::constructFrom([
            'id' =>
                'pi_manual_success',

            'amount' =>
                $payment->amount,

            'currency' =>
                strtolower(
                    $payment->currency
                ),

            'customer' =>
                'cus_manual_123',

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
        ]);
    }
}
