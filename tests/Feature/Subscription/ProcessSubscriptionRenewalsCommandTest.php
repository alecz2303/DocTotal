<?php

namespace Tests\Feature\Subscription;

use App\Contracts\PaymentGateway;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\Billing\FakePaymentGateway;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ProcessSubscriptionRenewalsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_command_processes_subscription_at_exact_billing_instant(): void
    {
        Carbon::setTestNow(
            '2026-09-26 16:37:22'
        );

        $subscription =
            $this->createSubscription(
                nextBillingAt: '2026-09-26 16:37:22'
            );

        $this->gateway()
            ->succeedNextCharge(
                'provider-renewal-1'
            );

        $this->artisan(
            'billing:process-renewals'
        )
            ->expectsOutput(
                'Renovaciones procesadas: 1'
            )
            ->assertSuccessful();

        $subscription->refresh();

        $this->assertTrue(
            $subscription
                ->current_period_starts_at
                ->equalTo(
                    '2026-09-26 16:37:22'
                )
        );

        $this->assertTrue(
            $subscription
                ->current_period_ends_at
                ->equalTo(
                    '2026-10-26 16:37:22'
                )
        );

        $this->assertTrue(
            $subscription
                ->next_billing_at
                ->equalTo(
                    '2026-10-26 16:37:22'
                )
        );

        $payment =
            Payment::withoutGlobalScopes()
            ->first();

        $this->assertNotNull(
            $payment
        );

        $this->assertSame(
            Payment::STATUS_SUCCEEDED,
            $payment->status
        );
    }

    public function test_command_does_not_process_subscription_before_billing_instant(): void
    {
        Carbon::setTestNow(
            '2026-09-26 16:37:21'
        );

        $this->createSubscription(
            nextBillingAt: '2026-09-26 16:37:22'
        );

        $this->artisan(
            'billing:process-renewals'
        )
            ->expectsOutput(
                'Renovaciones procesadas: 0'
            )
            ->assertSuccessful();

        $this->assertSame(
            0,
            Payment::withoutGlobalScopes()
                ->count()
        );
    }

    public function test_failed_renewal_enters_payment_recovery(): void
    {
        Carbon::setTestNow(
            '2026-09-26 16:37:22'
        );

        $subscription =
            $this->createSubscription(
                nextBillingAt: '2026-09-26 16:37:22'
            );

        $this->gateway()
            ->failNextCharge(
                'card_declined',
                'Tarjeta rechazada.'
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
            ->first();

        $this->assertSame(
            Payment::STATUS_FAILED,
            $payment->status
        );
    }

    public function test_command_does_not_charge_subscription_scheduled_for_cancellation(): void
    {
        Carbon::setTestNow(
            '2026-09-26 16:37:22'
        );

        $this->createSubscription(
            nextBillingAt: '2026-09-26 16:37:22',
            cancelAtPeriodEnd: true,
        );

        $this->artisan(
            'billing:process-renewals'
        )
            ->expectsOutput(
                'Renovaciones procesadas: 0'
            )
            ->assertSuccessful();

        $this->assertSame(
            0,
            Payment::withoutGlobalScopes()
                ->count()
        );
    }

    public function test_command_ignores_past_due_subscription(): void
    {
        Carbon::setTestNow(
            '2026-09-26 16:37:22'
        );

        $subscription =
            $this->createSubscription(
                nextBillingAt: '2026-09-26 16:37:22'
            );

        $subscription->update([
            'status' =>
            Subscription::STATUS_PAST_DUE,

            'past_due_since' =>
            now()->subDay(),

            'grace_ends_at' =>
            now()->addDays(6),

            'next_retry_at' =>
            now()->addDay(),
        ]);

        $this->artisan(
            'billing:process-renewals'
        )
            ->expectsOutput(
                'Renovaciones procesadas: 0'
            )
            ->assertSuccessful();

        $this->assertSame(
            0,
            Payment::withoutGlobalScopes()
                ->count()
        );
    }

    public function test_renewal_payment_uses_contractual_period_for_idempotency_key(): void
    {
        Carbon::setTestNow(
            '2026-09-26 16:40:00'
        );

        $subscription =
            $this->createSubscription(
                nextBillingAt: '2026-09-26 16:37:22'
            );

        $this->gateway()
            ->succeedNextCharge(
                'provider-renewal-key'
            );

        $this->artisan(
            'billing:process-renewals'
        )->assertSuccessful();

        $payment =
            Payment::withoutGlobalScopes()
            ->first();

        $this->assertSame(
            sprintf(
                'subscription:%d:renewal:20260926163722',
                $subscription->id
            ),
            $payment->idempotency_key
        );
    }

    private function gateway(): FakePaymentGateway
    {
        $gateway = app(
            PaymentGateway::class
        );

        $this->assertInstanceOf(
            FakePaymentGateway::class,
            $gateway
        );

        return $gateway;
    }

    private function createSubscription(
        string $nextBillingAt,
        bool $cancelAtPeriodEnd = false,
    ): Subscription {
        $tenant = Tenant::create([
            'name' =>
            'Consultorio Renewal ' .
                uniqid(),

            'slug' =>
            'consultorio-renewal-' .
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
            129900,

            'billing_currency' =>
            'MXN',

            'status' =>
            Subscription::STATUS_ACTIVE,

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
                $nextBillingAt
            ),

            'retry_count' =>
            0,

            'cancel_at_period_end' =>
            $cancelAtPeriodEnd,

            'cancelled_at' =>
            null,
        ]);
    }
}
