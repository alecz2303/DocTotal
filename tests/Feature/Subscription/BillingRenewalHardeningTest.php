<?php

namespace Tests\Feature\Subscription;

use App\Contracts\PaymentGateway;
use App\Data\PaymentChargeResult;
use App\Models\BillingCustomer;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\Billing\StripePaymentGateway;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use RuntimeException;
use Tests\TestCase;

class BillingRenewalHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_yearly_subscription_renews_for_six_thousand_mxn_and_advances_one_year(): void
    {
        Carbon::setTestNow(
            '2027-08-27 10:15:30'
        );

        $subscription =
            $this->createSubscription(
                billingCycle: Subscription::BILLING_CYCLE_YEARLY,
                billingAmount: 600000,
                startsAt: '2026-08-27 10:15:30',
                currentPeriodStartsAt: '2026-08-27 10:15:30',
                currentPeriodEndsAt: '2027-08-27 10:15:30',
                nextBillingAt: '2027-08-27 10:15:30',
            );

        $this->app->instance(
            PaymentGateway::class,
            new class implements PaymentGateway {
                public function name(): string
                {
                    return 'fake';
                }

                public function charge(
                    Payment $payment
                ): PaymentChargeResult {
                    return PaymentChargeResult::succeeded(
                        'provider-yearly-renewal'
                    );
                }
            }
        );

        $this->artisan(
            'billing:process-renewals'
        )
            ->expectsOutput(
                'Renovaciones procesadas: 1'
            )
            ->assertSuccessful();

        $subscription->refresh();

        $this->assertSame(
            Subscription::STATUS_ACTIVE,
            $subscription->status
        );

        $this->assertTrue(
            $subscription
                ->current_period_starts_at
                ->equalTo(
                    '2027-08-27 10:15:30'
                )
        );

        $this->assertTrue(
            $subscription
                ->current_period_ends_at
                ->equalTo(
                    '2028-08-27 10:15:30'
                )
        );

        $this->assertTrue(
            $subscription
                ->next_billing_at
                ->equalTo(
                    '2028-08-27 10:15:30'
                )
        );

        $payment =
            Payment::withoutGlobalScopes()
            ->firstOrFail();

        $this->assertSame(
            600000,
            $payment->amount
        );

        $this->assertSame(
            'MXN',
            $payment->currency
        );

        $this->assertSame(
            Payment::STATUS_SUCCEEDED,
            $payment->status
        );

        $this->assertSame(
            sprintf(
                'subscription:%d:renewal:20270827101530',
                $subscription->id
            ),
            $payment->idempotency_key
        );
    }

    public function test_renewal_command_continues_when_one_subscription_has_infrastructure_error(): void
    {
        Carbon::setTestNow(
            '2026-09-26 16:37:22'
        );

        $first =
            $this->createSubscription(
                nextBillingAt: '2026-09-26 16:37:22'
            );

        $second =
            $this->createSubscription(
                nextBillingAt: '2026-09-26 16:37:22'
            );

        $this->app->instance(
            PaymentGateway::class,
            new class implements PaymentGateway {
                private int $attempt = 0;

                public function name(): string
                {
                    return 'fake';
                }

                public function charge(
                    Payment $payment
                ): PaymentChargeResult {
                    $this->attempt++;

                    if ($this->attempt === 1) {
                        throw new RuntimeException(
                            'Fallo de infraestructura simulado.'
                        );
                    }

                    return PaymentChargeResult::succeeded(
                        'provider-second-renewal'
                    );
                }
            }
        );

        $this->artisan(
            'billing:process-renewals'
        )
            ->expectsOutput(
                'Renovaciones procesadas: 1'
            )
            ->expectsOutput(
                'Renovaciones con error inesperado: 1'
            )
            ->assertSuccessful();

        $first->refresh();
        $second->refresh();

        $this->assertSame(
            Subscription::STATUS_ACTIVE,
            $first->status
        );

        $this->assertTrue(
            $first
                ->current_period_ends_at
                ->equalTo(
                    '2026-09-26 16:37:22'
                )
        );

        $this->assertTrue(
            $second
                ->current_period_ends_at
                ->equalTo(
                    '2026-10-26 16:37:22'
                )
        );

        $this->assertSame(
            1,
            Payment::withoutGlobalScopes()
                ->count()
        );
    }

    public function test_retry_command_continues_when_one_subscription_has_infrastructure_error(): void
    {
        Carbon::setTestNow(
            '2026-09-27 16:37:22'
        );

        $first =
            $this->createPastDueSubscription(
                nextRetryAt: '2026-09-27 16:37:22'
            );

        $second =
            $this->createPastDueSubscription(
                nextRetryAt: '2026-09-27 16:37:22'
            );

        $this->app->instance(
            PaymentGateway::class,
            new class implements PaymentGateway {
                private int $attempt = 0;

                public function name(): string
                {
                    return 'fake';
                }

                public function charge(
                    Payment $payment
                ): PaymentChargeResult {
                    $this->attempt++;

                    if ($this->attempt === 1) {
                        throw new RuntimeException(
                            'Fallo de infraestructura simulado.'
                        );
                    }

                    return PaymentChargeResult::succeeded(
                        'provider-second-retry'
                    );
                }
            }
        );

        $this->artisan(
            'billing:process-retries'
        )
            ->expectsOutput(
                'Reintentos de cobro procesados: 1'
            )
            ->expectsOutput(
                'Reintentos con error inesperado: 1'
            )
            ->assertSuccessful();

        $first->refresh();
        $second->refresh();

        $this->assertSame(
            Subscription::STATUS_PAST_DUE,
            $first->status
        );

        $this->assertSame(
            0,
            $first->retry_count
        );

        $this->assertSame(
            Subscription::STATUS_ACTIVE,
            $second->status
        );

        $this->assertNull(
            $second->past_due_since
        );

        $this->assertNull(
            $second->grace_ends_at
        );

        $this->assertNull(
            $second->next_retry_at
        );

        $this->assertSame(
            1,
            Payment::withoutGlobalScopes()
                ->count()
        );

        $payment =
            Payment::withoutGlobalScopes()
            ->firstOrFail();

        $this->assertSame(
            Payment::STATUS_SUCCEEDED,
            $payment->status
        );
    }

    public function test_automatic_renewal_without_saved_card_enters_recovery_instead_of_crashing(): void
    {
        Carbon::setTestNow(
            '2026-09-26 16:37:22'
        );

        $subscription =
            $this->createSubscription(
                billingAmount: 60000,
                nextBillingAt: '2026-09-26 16:37:22',
                createStripeCustomer: true,
            );

        $this->app->instance(
            PaymentGateway::class,
            app(StripePaymentGateway::class)
        );

        $this->artisan(
            'billing:process-renewals'
        )
            ->expectsOutput(
                'Renovaciones procesadas: 1'
            )
            ->assertSuccessful();

        $subscription->refresh();

        $this->assertSame(
            Subscription::STATUS_PAST_DUE,
            $subscription->status
        );

        $this->assertTrue(
            $subscription
                ->past_due_since
                ->equalTo(now())
        );

        $this->assertTrue(
            $subscription
                ->grace_ends_at
                ->equalTo(
                    '2026-10-03 16:37:22'
                )
        );

        $payment =
            Payment::withoutGlobalScopes()
            ->firstOrFail();

        $this->assertSame(
            Payment::STATUS_FAILED,
            $payment->status
        );

        $this->assertSame(
            'payment_method_missing',
            $payment->failure_code
        );

        $this->assertSame(
            'El tenant no tiene un método de pago predeterminado.',
            $payment->failure_message
        );
    }

    private function createSubscription(
        string $billingCycle =
        Subscription::BILLING_CYCLE_MONTHLY,
        int $billingAmount = 60000,
        string $startsAt =
        '2026-08-26 16:37:22',
        string $currentPeriodStartsAt =
        '2026-08-26 16:37:22',
        string $currentPeriodEndsAt =
        '2026-09-26 16:37:22',
        string $nextBillingAt =
        '2026-09-26 16:37:22',
        bool $createStripeCustomer = false,
    ): Subscription {
        $tenant = Tenant::create([
            'name' =>
            'Consultorio Hardening ' .
                uniqid(),

            'slug' =>
            'consultorio-hardening-' .
                uniqid(),

            'status' =>
            'active',

            'onboarding_completed_at' =>
            now(),
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        if ($createStripeCustomer) {
            BillingCustomer::create([
                'provider' =>
                BillingCustomer::PROVIDER_STRIPE,

                'provider_customer_id' =>
                'cus_hardening_' .
                    uniqid(),
            ]);
        }

        return Subscription::create([
            'billing_cycle' =>
            $billingCycle,

            'billing_amount' =>
            $billingAmount,

            'billing_currency' =>
            'MXN',

            'status' =>
            Subscription::STATUS_ACTIVE,

            'starts_at' =>
            Carbon::parse(
                $startsAt
            ),

            'current_period_starts_at' =>
            Carbon::parse(
                $currentPeriodStartsAt
            ),

            'current_period_ends_at' =>
            Carbon::parse(
                $currentPeriodEndsAt
            ),

            'next_billing_at' =>
            Carbon::parse(
                $nextBillingAt
            ),

            'retry_count' =>
            0,

            'cancel_at_period_end' =>
            false,

            'cancelled_at' =>
            null,
        ]);
    }

    private function createPastDueSubscription(
        string $nextRetryAt,
    ): Subscription {
        $tenant = Tenant::create([
            'name' =>
            'Consultorio Hardening Retry ' .
                uniqid(),

            'slug' =>
            'consultorio-hardening-retry-' .
                uniqid(),

            'status' =>
            'active',

            'onboarding_completed_at' =>
            now(),
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        return Subscription::create([
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
                '2026-08-26 16:37:22'
            ),

            'current_period_starts_at' =>
            Carbon::parse(
                '2026-08-26 16:37:22'
            ),

            'current_period_ends_at' =>
            Carbon::parse(
                '2026-09-26 16:37:22'
            ),

            'next_billing_at' =>
            Carbon::parse(
                '2026-09-26 16:37:22'
            ),

            'past_due_since' =>
            Carbon::parse(
                '2026-09-26 16:37:22'
            ),

            'grace_ends_at' =>
            Carbon::parse(
                '2026-10-03 16:37:22'
            ),

            'next_retry_at' =>
            Carbon::parse(
                $nextRetryAt
            ),

            'retry_count' =>
            0,

            'cancel_at_period_end' =>
            false,
        ]);
    }
}
