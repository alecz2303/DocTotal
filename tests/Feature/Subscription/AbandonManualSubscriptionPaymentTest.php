<?php

namespace Tests\Feature\Subscription;

use App\Actions\Billing\AbandonManualSubscriptionPayment;
use App\Contracts\StripePaymentIntentApi;
use App\Models\Payment;
use App\Models\PromotionalCredit;
use App\Models\Referral;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use LogicException;
use RuntimeException;
use Stripe\PaymentIntent;
use Tests\Fakes\FakeStripePaymentIntentApi;
use Tests\TestCase;

class AbandonManualSubscriptionPaymentTest extends TestCase
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

    public function test_abandons_pending_manual_payment(): void
    {
        $tenant =
            $this->createTenant();

        $payment =
            $this->createPendingPayment(
                $tenant
            );

        $this->prepareCanceledStripePaymentIntent(
            $payment->provider_payment_id
        );

        $canceledAt =
            Carbon::parse(
                '2026-08-29 02:30:00'
            );

        $result =
            $this->action()->execute(
                $tenant,
                $payment,
                $canceledAt
            );

        $this->assertSame(
            Payment::STATUS_CANCELED,
            $result->status
        );

        $this->assertTrue(
            $result->canceled_at->equalTo(
                $canceledAt
            )
        );

        $this->assertNull(
            $result->failed_at
        );

        $this->assertNull(
            $result->paid_at
        );
    }

    public function test_cancels_payment_intent_in_stripe(): void
    {
        $tenant =
            $this->createTenant();

        $payment =
            $this->createPendingPayment(
                $tenant,
                'pi_abandon_stripe'
            );

        $this->prepareCanceledStripePaymentIntent(
            'pi_abandon_stripe'
        );

        $this->action()->execute(
            $tenant,
            $payment,
            now()
        );

        $this->assertSame(
            'pi_abandon_stripe',
            $this->paymentIntents
                ->receivedCancelPaymentIntentId
        );
    }

    public function test_releases_reserved_promotional_credit(): void
    {
        $tenant =
            $this->createTenant();

        $payment =
            $this->createPendingPayment(
                $tenant
            );

        $credit =
            $this->createReservedPromotionalCredit(
                $tenant,
                $payment
            );

        $payment->update([
            'gross_amount' =>
            60000,

            'promotional_credit_amount' =>
            5000,

            'amount' =>
            55000,
        ]);

        $this->prepareCanceledStripePaymentIntent(
            $payment->provider_payment_id
        );

        $this->action()->execute(
            $tenant,
            $payment,
            now()
        );

        $credit->refresh();

        $this->assertSame(
            PromotionalCredit::STATUS_AVAILABLE,
            $credit->status
        );

        $this->assertNull(
            $credit->payment_id
        );

        $this->assertNull(
            $credit->consumed_at
        );
    }

    public function test_abandoning_checkout_does_not_qualify_pending_referral(): void
    {
        $referrer =
            $this->createTenant(
                'Consultorio Referidor'
            );

        $referred =
            $this->createTenant(
                'Consultorio Referido'
            );

        $referral =
            Referral::withoutGlobalScopes()
            ->create([
                'referrer_tenant_id' =>
                $referrer->id,

                'referred_tenant_id' =>
                $referred->id,

                'referral_code' =>
                $referrer->referral_code,

                'status' =>
                Referral::STATUS_PENDING,
            ]);

        $payment =
            $this->createPendingPayment(
                $referred
            );

        $payment->update([
            'gross_amount' =>
            60000,

            'referral_discount_amount' =>
            5000,

            'amount' =>
            55000,
        ]);

        $this->prepareCanceledStripePaymentIntent(
            $payment->provider_payment_id
        );

        $this->action()->execute(
            $referred,
            $payment,
            now()
        );

        $referral->refresh();

        $this->assertSame(
            Referral::STATUS_PENDING,
            $referral->status
        );

        $this->assertNull(
            $referral->qualifying_payment_id
        );

        $this->assertNull(
            $referral->qualified_at
        );

        $this->assertNull(
            $referral->reward_status
        );
    }

    public function test_abandoning_checkout_does_not_create_referrer_reward(): void
    {
        $referrer =
            $this->createTenant(
                'Consultorio Referidor'
            );

        $referred =
            $this->createTenant(
                'Consultorio Referido'
            );

        $referral =
            Referral::withoutGlobalScopes()
            ->create([
                'referrer_tenant_id' =>
                $referrer->id,

                'referred_tenant_id' =>
                $referred->id,

                'referral_code' =>
                $referrer->referral_code,

                'status' =>
                Referral::STATUS_PENDING,
            ]);

        $payment =
            $this->createPendingPayment(
                $referred
            );

        $payment->update([
            'gross_amount' =>
            60000,

            'referral_discount_amount' =>
            5000,

            'amount' =>
            55000,
        ]);

        $this->prepareCanceledStripePaymentIntent(
            $payment->provider_payment_id
        );

        $this->action()->execute(
            $referred,
            $payment,
            now()
        );

        $this->assertSame(
            0,
            PromotionalCredit::withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $referrer->id
                )
                ->where(
                    'referral_id',
                    $referral->id
                )
                ->where(
                    'kind',
                    PromotionalCredit::KIND_REFERRER_REWARD
                )
                ->count()
        );
    }

    public function test_cannot_abandon_payment_from_another_tenant(): void
    {
        $tenant =
            $this->createTenant(
                'Consultorio Uno'
            );

        $otherTenant =
            $this->createTenant(
                'Consultorio Dos'
            );

        $payment =
            $this->createPendingPayment(
                $otherTenant
            );

        $this->expectException(
            LogicException::class
        );

        $this->expectExceptionMessage(
            'El pago pertenece a otro tenant.'
        );

        $this->action()->execute(
            $tenant,
            $payment,
            now()
        );
    }

    public function test_already_canceled_payment_is_idempotent(): void
    {
        $tenant =
            $this->createTenant();

        $payment =
            $this->createPendingPayment(
                $tenant
            );

        $this->prepareCanceledStripePaymentIntent(
            $payment->provider_payment_id
        );

        $first =
            $this->action()->execute(
                $tenant,
                $payment,
                now()
            );

        /*
         * No configuramos un segundo resultado fake
         * deliberadamente. La segunda ejecución no
         * debería volver a llamar Stripe.
         */
        $this->paymentIntents =
            new FakeStripePaymentIntentApi();

        $this->app->instance(
            StripePaymentIntentApi::class,
            $this->paymentIntents
        );

        $second =
            $this->action()->execute(
                $tenant,
                $first,
                now()->addMinute()
            );

        $this->assertSame(
            Payment::STATUS_CANCELED,
            $second->status
        );

        $this->assertNull(
            $this->paymentIntents
                ->receivedCancelPaymentIntentId
        );
    }

    public function test_succeeded_payment_cannot_be_abandoned(): void
    {
        $tenant =
            $this->createTenant();

        $payment =
            $this->createPendingPayment(
                $tenant
            );

        $payment->succeed(
            now(),
            $payment->provider_payment_id
        );

        $this->expectException(
            LogicException::class
        );

        $this->expectExceptionMessage(
            'El checkout no puede cancelarse desde el estado "succeeded".'
        );

        $this->action()->execute(
            $tenant,
            $payment,
            now()
        );
    }

    public function test_failed_payment_cannot_be_abandoned(): void
    {
        $tenant =
            $this->createTenant();

        $payment =
            $this->createPendingPayment(
                $tenant
            );

        $payment->fail(
            now(),
            'card_declined',
            'Tarjeta rechazada.',
            $payment->provider_payment_id
        );

        $this->expectException(
            LogicException::class
        );

        $this->expectExceptionMessage(
            'El checkout no puede cancelarse desde el estado "failed".'
        );

        $this->action()->execute(
            $tenant,
            $payment,
            now()
        );
    }

    public function test_stripe_failure_keeps_local_payment_pending(): void
    {
        $tenant =
            $this->createTenant();

        $payment =
            $this->createPendingPayment(
                $tenant
            );

        $this->paymentIntents
            ->throwException(
                new RuntimeException(
                    'Stripe cancel failed.'
                )
            );

        try {
            $this->action()->execute(
                $tenant,
                $payment,
                now()
            );

            $this->fail(
                'Se esperaba una excepción de Stripe.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Stripe cancel failed.',
                $exception->getMessage()
            );
        }

        $payment->refresh();

        $this->assertSame(
            Payment::STATUS_PENDING,
            $payment->status
        );

        $this->assertNull(
            $payment->canceled_at
        );
    }

    public function test_payment_without_provider_payment_id_can_be_abandoned_locally(): void
    {
        $tenant =
            $this->createTenant();

        $payment =
            $this->createPendingPayment(
                $tenant,
                null
            );

        $result =
            $this->action()->execute(
                $tenant,
                $payment,
                now()
            );

        $this->assertSame(
            Payment::STATUS_CANCELED,
            $result->status
        );

        $this->assertNotNull(
            $result->canceled_at
        );

        $this->assertNull(
            $this->paymentIntents
                ->receivedCancelPaymentIntentId
        );
    }

    private function action(): AbandonManualSubscriptionPayment
    {
        return app(
            AbandonManualSubscriptionPayment::class
        );
    }

    private function createTenant(
        string $name = 'Consultorio Checkout Abandonado',
    ): Tenant {
        return Tenant::create([
            'name' =>
            $name,

            'slug' =>
            'abandon-checkout-' .
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
        ?string $paymentIntentId = 'pi_abandon_test',
    ): Payment {
        return Payment::withoutGlobalScopes()
            ->create([
                'tenant_id' =>
                $tenant->id,

                'subscription_id' =>
                null,

                'billing_cycle' =>
                'monthly',

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
                now(),

                'provider' =>
                'stripe',

                'provider_payment_id' =>
                $paymentIntentId,

                'idempotency_key' =>
                'abandon-payment-' .
                    uniqid(),
            ]);
    }

    private function createReservedPromotionalCredit(
        Tenant $tenant,
        Payment $payment,
    ): PromotionalCredit {
        $referred =
            $this->createTenant(
                'Consultorio que generó crédito'
            );

        $referral =
            Referral::withoutGlobalScopes()
            ->create([
                'referrer_tenant_id' =>
                $tenant->id,

                'referred_tenant_id' =>
                $referred->id,

                'referral_code' =>
                $tenant->referral_code,
            ]);

        return PromotionalCredit::withoutGlobalScopes()
            ->create([
                'tenant_id' =>
                $tenant->id,

                'referral_id' =>
                $referral->id,

                'payment_id' =>
                $payment->id,

                'kind' =>
                PromotionalCredit::KIND_REFERRER_REWARD,

                'amount' =>
                5000,

                'currency' =>
                'MXN',

                'status' =>
                PromotionalCredit::STATUS_RESERVED,

                'available_at' =>
                now(),

                'idempotency_key' =>
                'abandon-credit-' .
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
