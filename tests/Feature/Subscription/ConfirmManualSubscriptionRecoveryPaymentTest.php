<?php

namespace Tests\Feature\Subscription;

use App\Actions\Billing\ConfirmManualSubscriptionRecoveryPayment;
use App\Contracts\StripePaymentIntentApi;
use App\Models\BillingCustomer;
use App\Models\Payment;
use App\Models\PromotionalCredit;
use App\Models\Referral;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use LogicException;
use Stripe\PaymentIntent;
use Tests\Fakes\FakeStripePaymentIntentApi;
use Tests\TestCase;

class ConfirmManualSubscriptionRecoveryPaymentTest extends TestCase
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

    public function test_successful_manual_recovery_payment_reactivates_subscription(): void
    {
        [$tenant, $subscription, $payment] =
            $this->scenario();

        $confirmedAt =
            Carbon::parse(
                '2026-08-28 13:00:00'
            );

        $this->stripe
            ->returnRetrievedPaymentIntent(
                $this->successfulIntent(
                    $tenant,
                    $subscription,
                    $payment
                )
            );

        $result = $this->action()->execute(
            $tenant,
            $payment,
            $confirmedAt
        );

        $this->assertTrue(
            $result->isSucceeded()
        );

        $this->assertSame(
            'pi_manual_recovery_success',
            $result->provider_payment_id
        );

        $subscription->refresh();

        $this->assertTrue(
            $subscription->isActive()
        );

        $this->assertNull(
            $subscription->past_due_since
        );

        $this->assertNull(
            $subscription->grace_ends_at
        );

        $this->assertNull(
            $subscription->next_retry_at
        );

        $this->assertSame(
            0,
            $subscription->retry_count
        );
    }

    public function test_successful_monthly_recovery_changes_unpaid_yearly_subscription_to_monthly(): void
    {
        [$tenant, $subscription, $payment] =
            $this->scenario();

        $subscription->update([
            'billing_cycle' =>
            Subscription::BILLING_CYCLE_YEARLY,

            'billing_amount' =>
            600000,

            'pending_billing_cycle' =>
            Subscription::BILLING_CYCLE_MONTHLY,
        ]);

        $payment->update([
            'billing_cycle' =>
            Subscription::BILLING_CYCLE_MONTHLY,

            'gross_amount' =>
            60000,

            'amount' =>
            60000,
        ]);

        $this->stripe
            ->returnRetrievedPaymentIntent(
                $this->successfulIntent(
                    $tenant,
                    $subscription,
                    $payment->refresh()
                )
            );

        $this->action()->execute(
            $tenant,
            $payment,
            Carbon::parse(
                '2026-08-28 13:00:00'
            )
        );

        $subscription->refresh();

        $this->assertTrue(
            $subscription->isActive()
        );

        $this->assertSame(
            Subscription::BILLING_CYCLE_MONTHLY,
            $subscription->billing_cycle
        );

        $this->assertSame(
            60000,
            $subscription->billing_amount
        );

        $this->assertNull(
            $subscription->pending_billing_cycle
        );
    }

    public function test_successful_manual_recovery_reactivates_suspended_tenant(): void
    {
        [$tenant, $subscription, $payment] =
            $this->scenario(
                tenantStatus: 'suspended'
            );

        $tenant->update([
            'suspended_at' =>
            Carbon::parse(
                '2026-08-28 12:00:00'
            ),
        ]);

        $this->stripe
            ->returnRetrievedPaymentIntent(
                $this->successfulIntent(
                    $tenant,
                    $subscription,
                    $payment
                )
            );

        $this->action()->execute(
            $tenant,
            $payment,
            Carbon::parse(
                '2026-08-28 13:00:00'
            )
        );

        $tenant->refresh();

        $this->assertSame(
            'active',
            $tenant->status
        );

        $this->assertNull(
            $tenant->suspended_at
        );
    }

    public function test_confirmation_retrieves_expected_payment_intent(): void
    {
        [$tenant, $subscription, $payment] =
            $this->scenario();

        $this->stripe
            ->returnRetrievedPaymentIntent(
                $this->successfulIntent(
                    $tenant,
                    $subscription,
                    $payment
                )
            );

        $this->action()->execute(
            $tenant,
            $payment,
            now()
        );

        $this->assertSame(
            'pi_manual_recovery_success',
            $this->stripe
                ->receivedPaymentIntentId
        );
    }

    public function test_payment_must_belong_to_tenant(): void
    {
        [$tenant, $subscription, $payment] =
            $this->scenario();

        $otherTenant = Tenant::create([
            'name' => 'Tenant Incorrecto',
            'slug' => 'incorrecto-' . uniqid(),
            'status' => 'active',
            'onboarding_completed_at' => now(),
        ]);

        $this->expectException(
            LogicException::class
        );

        $this->action()->execute(
            $otherTenant,
            $payment,
            now()
        );
    }

    public function test_payment_must_still_be_pending(): void
    {
        [$tenant, $subscription, $payment] =
            $this->scenario();

        $payment->update([
            'status' =>
            Payment::STATUS_FAILED,

            'failed_at' => now(),
        ]);

        $this->expectException(
            LogicException::class
        );

        $this->action()->execute(
            $tenant,
            $payment,
            now()
        );
    }

    public function test_subscription_must_still_be_past_due(): void
    {
        [$tenant, $subscription, $payment] =
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
            $payment,
            now()
        );
    }

    public function test_payment_intent_amount_must_match_payment(): void
    {
        [$tenant, $subscription, $payment] =
            $this->scenario();

        $intent = $this->successfulIntent(
            $tenant,
            $subscription,
            $payment
        );

        $intent->amount = 50000;

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
        [$tenant, $subscription, $payment] =
            $this->scenario();

        $intent = $this->successfulIntent(
            $tenant,
            $subscription,
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

    public function test_payment_intent_metadata_must_match_subscription(): void
    {
        [$tenant, $subscription, $payment] =
            $this->scenario();

        $intent = $this->successfulIntent(
            $tenant,
            $subscription,
            $payment
        );

        $intent->metadata =
            [
                'doctotal_payment_uuid' =>
                $payment->uuid,

                'doctotal_tenant_id' =>
                (string) $tenant->id,

                'subscription_id' =>
                '999999',

                'payment_mode' =>
                'manual_recovery',
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

    public function test_payment_intent_must_be_manual_recovery(): void
    {
        [$tenant, $subscription, $payment] =
            $this->scenario();

        $intent = $this->successfulIntent(
            $tenant,
            $subscription,
            $payment
        );

        $intent->metadata =
            [
                'doctotal_payment_uuid' =>
                $payment->uuid,

                'doctotal_tenant_id' =>
                (string) $tenant->id,

                'subscription_id' =>
                (string) $subscription->id,

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

    public function test_non_succeeded_payment_intent_does_not_recover_subscription(): void
    {
        [$tenant, $subscription, $payment] =
            $this->scenario();

        $intent = $this->successfulIntent(
            $tenant,
            $subscription,
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

        $this->assertTrue(
            $subscription
                ->refresh()
                ->isPastDue()
        );
    }

    public function test_confirming_succeeded_recovery_payment_again_is_idempotent(): void
    {
        [$tenant, $subscription, $payment] =
            $this->scenario();

        $this->stripe
            ->returnRetrievedPaymentIntent(
                $this->successfulIntent(
                    $tenant,
                    $subscription,
                    $payment
                )
            );

        $first = $this->action()->execute(
            $tenant,
            $payment,
            now()
        );

        $second = $this->action()->execute(
            $tenant,
            $first,
            now()->addSecond()
        );

        $this->assertTrue(
            $first->is($second)
        );

        $this->assertTrue(
            $subscription
                ->refresh()
                ->isActive()
        );
    }

    public function test_successful_manual_recovery_consumes_reserved_promotional_credit(): void
    {
        [$tenant, $subscription, $payment] =
            $this->scenario();

        $payment->update([
            'gross_amount' =>
            60000,

            'referral_discount_amount' =>
            0,

            'promotional_credit_amount' =>
            5000,

            'amount' =>
            55000,
        ]);

        $credit =
            $this->createReservedPromotionalCredit(
                $tenant,
                $payment,
                5000
            );

        $this->stripe
            ->returnRetrievedPaymentIntent(
                $this->successfulIntent(
                    $tenant,
                    $subscription,
                    $payment->refresh()
                )
            );

        $result =
            $this->action()->execute(
                $tenant,
                $payment,
                Carbon::parse(
                    '2026-08-29 00:45:00'
                )
            );

        $credit->refresh();

        $this->assertTrue(
            $result->isSucceeded()
        );

        $this->assertSame(
            55000,
            $result->amount
        );

        $this->assertSame(
            5000,
            $result->promotional_credit_amount
        );

        $this->assertSame(
            PromotionalCredit::STATUS_CONSUMED,
            $credit->status
        );

        $this->assertSame(
            $result->id,
            $credit->payment_id
        );

        $this->assertNotNull(
            $credit->consumed_at
        );

        $this->assertTrue(
            $subscription
                ->refresh()
                ->isActive()
        );
    }

    private function action(): ConfirmManualSubscriptionRecoveryPayment
    {
        return app(
            ConfirmManualSubscriptionRecoveryPayment::class
        );
    }

    private function scenario(
        string $tenantStatus = 'active',
    ): array {
        $tenant = Tenant::create([
            'name' => 'Consultorio Confirm Recovery',
            'slug' =>
            'confirm-recovery-' . uniqid(),
            'status' => $tenantStatus,
            'onboarding_completed_at' => now(),
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        BillingCustomer::withoutGlobalScopes()
            ->create([
                'tenant_id' => $tenant->id,

                'provider' =>
                BillingCustomer::PROVIDER_STRIPE,

                'provider_customer_id' =>
                'cus_manual_recovery_123',
            ]);

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

                'billing_amount' => 60000,

                'billing_currency' => 'MXN',

                'past_due_since' =>
                $endsAt,

                'grace_ends_at' =>
                $endsAt
                    ->copy()
                    ->addDays(7),

                'next_retry_at' =>
                $endsAt
                    ->copy()
                    ->addDay(),

                'retry_count' => 1,
            ]);

        $payment =
            Payment::withoutGlobalScopes()
            ->create([
                'tenant_id' =>
                $tenant->id,

                'subscription_id' =>
                $subscription->id,

                'billing_cycle' =>
                $subscription->billing_cycle,

                'amount' => 60000,

                'currency' => 'MXN',

                'status' =>
                Payment::STATUS_PENDING,

                'attempted_at' => now(),

                'provider' => 'stripe',

                'provider_payment_id' =>
                'pi_manual_recovery_success',

                'idempotency_key' =>
                'confirm-manual-recovery-' .
                    uniqid(),
            ]);

        return [
            $tenant,
            $subscription,
            $payment,
        ];
    }

    private function createReservedPromotionalCredit(
        Tenant $tenant,
        Payment $payment,
        int $amount = 5000,
    ): PromotionalCredit {
        $referred =
            Tenant::create([
                'name' =>
                'Consultorio Recovery Referido',

                'slug' =>
                'confirm-recovery-referred-' .
                    uniqid(),

                'status' =>
                'active',

                'currency' =>
                'MXN',

                'onboarding_completed_at' =>
                now(),
            ]);

        $referral =
            Referral::create([
                'referrer_tenant_id' =>
                $tenant->id,

                'referred_tenant_id' =>
                $referred->id,

                'referral_code' =>
                $tenant->referral_code,
            ]);

        return PromotionalCredit::create([
            'tenant_id' =>
            $tenant->id,

            'referral_id' =>
            $referral->id,

            'payment_id' =>
            $payment->id,

            'kind' =>
            PromotionalCredit::KIND_REFERRER_REWARD,

            'amount' =>
            $amount,

            'currency' =>
            'MXN',

            'status' =>
            PromotionalCredit::STATUS_RESERVED,

            'available_at' =>
            now()->subMinute(),

            'reserved_at' =>
            now(),

            'idempotency_key' =>
            'confirm-recovery-credit-' .
                uniqid(),
        ]);
    }

    private function successfulIntent(
        Tenant $tenant,
        Subscription $subscription,
        Payment $payment,
    ): PaymentIntent {
        return PaymentIntent::constructFrom([
            'id' =>
            'pi_manual_recovery_success',

            'amount' =>
            $payment->amount,

            'currency' =>
            strtolower(
                $payment->currency
            ),

            'customer' =>
            'cus_manual_recovery_123',

            'status' =>
            'succeeded',

            'metadata' => [
                'doctotal_payment_uuid' =>
                $payment->uuid,

                'doctotal_tenant_id' =>
                (string) $tenant->id,

                'subscription_id' =>
                (string) $subscription->id,

                'billing_cycle' =>
                $payment->billing_cycle,

                'payment_mode' =>
                'manual_recovery',
            ],
        ]);
    }
}
