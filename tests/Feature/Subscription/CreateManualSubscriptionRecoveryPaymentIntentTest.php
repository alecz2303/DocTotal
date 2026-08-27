<?php

namespace Tests\Feature\Subscription;

use App\Actions\Billing\CreateManualSubscriptionRecoveryPaymentIntent;
use App\Contracts\StripeCustomerApi;
use App\Contracts\StripePaymentIntentApi;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use LogicException;
use Stripe\PaymentIntent;
use Tests\Fakes\FakeStripeCustomerApi;
use Tests\Fakes\FakeStripePaymentIntentApi;
use Tests\TestCase;

class CreateManualSubscriptionRecoveryPaymentIntentTest extends TestCase
{
    use RefreshDatabase;

    private FakeStripeCustomerApi $customers;

    private FakeStripePaymentIntentApi $paymentIntents;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_creates_pending_recovery_payment_for_same_subscription(): void
    {
        [$tenant, $subscription] =
            $this->scenario();

        $this->prepareStripe();

        $result = $this->action()->execute(
            $tenant,
            $subscription,
            Carbon::parse('2026-08-28 12:00:00'),
            'manual-recovery-123',
        );

        $payment = $result->payment;

        $this->assertSame(
            $subscription->id,
            $payment->subscription_id
        );

        $this->assertSame(
            Payment::STATUS_PENDING,
            $payment->status
        );

        $this->assertSame(
            $subscription->billing_cycle,
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

    public function test_creates_manual_recovery_payment_intent_payload(): void
    {
        [$tenant, $subscription] =
            $this->scenario();

        $this->prepareStripe(
            customerId: 'cus_recovery_123'
        );

        $result = $this->action()->execute(
            $tenant,
            $subscription,
            now(),
            'manual-recovery-payload',
        );

        $params =
            $this->paymentIntents->receivedParams;

        $this->assertSame(
            60000,
            $params['amount']
        );

        $this->assertSame(
            'mxn',
            $params['currency']
        );

        $this->assertSame(
            'cus_recovery_123',
            $params['customer']
        );

        $this->assertSame(
            ['card'],
            $params['payment_method_types']
        );

        $this->assertSame(
            $result->payment->uuid,
            $params['metadata'][
                'doctotal_payment_uuid'
            ]
        );

        $this->assertSame(
            (string) $tenant->id,
            $params['metadata'][
                'doctotal_tenant_id'
            ]
        );

        $this->assertSame(
            (string) $subscription->id,
            $params['metadata'][
                'subscription_id'
            ]
        );

        $this->assertSame(
            'manual_recovery',
            $params['metadata'][
                'payment_mode'
            ]
        );
    }

    public function test_can_prepare_recovery_card_for_future_off_session_charges(): void
    {
        [$tenant, $subscription] =
            $this->scenario();

        $this->prepareStripe();

        $this->action()->execute(
            $tenant,
            $subscription,
            now(),
            'manual-recovery-save-card',
            true,
        );

        $params =
            $this->paymentIntents->receivedParams;

        $this->assertSame(
            'off_session',
            $params['setup_future_usage']
        );

        $this->assertSame(
            '1',
            $params['metadata'][
                'save_for_future'
            ]
        );
    }

    public function test_does_not_prepare_card_for_future_by_default(): void
    {
        [$tenant, $subscription] =
            $this->scenario();

        $this->prepareStripe();

        $this->action()->execute(
            $tenant,
            $subscription,
            now(),
            'manual-recovery-no-save',
        );

        $params =
            $this->paymentIntents->receivedParams;

        $this->assertArrayNotHasKey(
            'setup_future_usage',
            $params
        );

        $this->assertSame(
            '0',
            $params['metadata'][
                'save_for_future'
            ]
        );
    }

    public function test_reuses_existing_pending_payment_intent_for_same_idempotency_key(): void
    {
        [$tenant, $subscription] =
            $this->scenario();

        $this->prepareStripe(
            paymentIntentId:
                'pi_recovery_existing',
            clientSecret:
                'pi_recovery_existing_secret',
        );

        $first = $this->action()->execute(
            $tenant,
            $subscription,
            now(),
            'manual-recovery-same',
        );

        $this->paymentIntents
            ->returnRetrievedPaymentIntent(
                PaymentIntent::constructFrom([
                    'id' =>
                        'pi_recovery_existing',

                    'status' =>
                        'requires_payment_method',

                    'client_secret' =>
                        'pi_recovery_existing_secret',
                ])
            );

        $second = $this->action()->execute(
            $tenant,
            $subscription,
            now()->addMinute(),
            'manual-recovery-same',
        );

        $this->assertTrue(
            $first->payment->is(
                $second->payment
            )
        );

        $this->assertSame(
            'pi_recovery_existing_secret',
            $second->clientSecret
        );

        $this->assertSame(
            1,
            Payment::withoutGlobalScopes()
                ->where(
                    'idempotency_key',
                    'manual-recovery-same'
                )
                ->count()
        );
    }

    public function test_subscription_must_belong_to_tenant(): void
    {
        [$tenant, $subscription] =
            $this->scenario();

        $otherTenant = Tenant::create([
            'name' => 'Otro Tenant',
            'slug' => 'otro-' . uniqid(),
            'status' => 'active',
            'onboarding_completed_at' => now(),
        ]);

        $this->expectException(
            LogicException::class
        );

        $this->action()->execute(
            $otherTenant,
            $subscription,
            now(),
            'manual-recovery-wrong-tenant',
        );
    }

    public function test_subscription_must_be_past_due(): void
    {
        [$tenant, $subscription] =
            $this->scenario();

        $subscription->update([
            'status' =>
                Subscription::STATUS_ACTIVE,
        ]);

        $this->expectException(
            LogicException::class
        );

        $this->action()->execute(
            $tenant,
            $subscription,
            now(),
            'manual-recovery-active',
        );
    }

    public function test_existing_idempotency_key_cannot_belong_to_another_operation(): void
    {
        [$tenant, $subscription] =
            $this->scenario();

        $otherTenant = Tenant::create([
            'name' => 'Tenant Ajeno',
            'slug' => 'ajeno-' . uniqid(),
            'status' => 'active',
            'onboarding_completed_at' => now(),
        ]);

        Payment::withoutGlobalScopes()
            ->create([
                'tenant_id' =>
                    $otherTenant->id,

                'subscription_id' => null,

                'billing_cycle' =>
                    Subscription::BILLING_CYCLE_MONTHLY,

                'amount' => 60000,

                'currency' => 'MXN',

                'status' =>
                    Payment::STATUS_PENDING,

                'attempted_at' => now(),

                'provider' => 'stripe',

                'idempotency_key' =>
                    'manual-recovery-collision',
            ]);

        $this->prepareStripe();

        $this->expectException(
            LogicException::class
        );

        $this->action()->execute(
            $tenant,
            $subscription,
            now(),
            'manual-recovery-collision',
        );
    }

    private function action(): CreateManualSubscriptionRecoveryPaymentIntent
    {
        return app(
            CreateManualSubscriptionRecoveryPaymentIntent::class
        );
    }

    private function scenario(): array
    {
        $tenant = Tenant::create([
            'name' => 'Consultorio Recovery',
            'slug' => 'recovery-' . uniqid(),
            'status' => 'active',
            'onboarding_completed_at' => now(),
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        $startsAt =
            Carbon::parse(
                '2026-07-26 22:20:32'
            );

        $endsAt =
            Carbon::parse(
                '2026-08-26 22:20:32'
            );

        $subscription =
            Subscription::create([
                'billing_cycle' =>
                    Subscription::BILLING_CYCLE_MONTHLY,

                'status' =>
                    Subscription::STATUS_PAST_DUE,

                'starts_at' => $startsAt,

                'current_period_starts_at' =>
                    $startsAt,

                'current_period_ends_at' =>
                    $endsAt,

                'next_billing_at' =>
                    $endsAt,

                'billing_amount' =>
                    60000,

                'billing_currency' =>
                    'MXN',

                'past_due_since' =>
                    $endsAt,

                'grace_ends_at' =>
                    $endsAt
                        ->copy()
                        ->addDays(7),

                'retry_count' => 0,
            ]);

        return [
            $tenant,
            $subscription,
        ];
    }

    private function prepareStripe(
        string $customerId =
            'cus_recovery_test',
        string $paymentIntentId =
            'pi_recovery_test',
        string $clientSecret =
            'pi_recovery_test_secret',
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
