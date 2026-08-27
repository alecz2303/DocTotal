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

class ProcessSubscriptionPaymentRetriesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_command_processes_due_retry(): void
    {
        Carbon::setTestNow('2026-09-27 16:37:22');

        $subscription = $this->createPastDueSubscription(
            nextRetryAt: '2026-09-27 16:37:22'
        );

        $this->gateway()->failNextCharge(
            'card_declined',
            'Tarjeta rechazada.'
        );

        $this->artisan('billing:process-retries')
            ->expectsOutput('Reintentos de cobro procesados: 1')
            ->assertSuccessful();

        $subscription->refresh();

        $this->assertSame(1, $subscription->retry_count);
        $this->assertTrue(
            $subscription->next_retry_at->equalTo('2026-09-29 16:37:22')
        );

        $payment = Payment::withoutGlobalScopes()->first();
        $this->assertNotNull($payment);
        $this->assertSame(Payment::STATUS_FAILED, $payment->status);
        $this->assertSame('card_declined', $payment->failure_code);
        $this->assertSame(
            sprintf(
                'subscription:%d:recovery:20260926163722:retry:1',
                $subscription->id
            ),
            $payment->idempotency_key
        );
    }

    public function test_command_recovers_subscription_when_retry_succeeds(): void
    {
        Carbon::setTestNow('2026-09-27 16:37:22');

        $subscription = $this->createPastDueSubscription(
            nextRetryAt: '2026-09-27 16:37:22'
        );

        $this->gateway()->succeedNextCharge('provider-recovered-1');

        $this->artisan('billing:process-retries')
            ->expectsOutput('Reintentos de cobro procesados: 1')
            ->assertSuccessful();

        $subscription->refresh();

        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->status);
        $this->assertNull($subscription->past_due_since);
        $this->assertNull($subscription->grace_ends_at);
        $this->assertNull($subscription->next_retry_at);

        $payment = Payment::withoutGlobalScopes()->first();
        $this->assertNotNull($payment);
        $this->assertSame(Payment::STATUS_SUCCEEDED, $payment->status);
    }

    public function test_command_does_not_process_future_retry(): void
    {
        Carbon::setTestNow('2026-09-27 16:37:21');
        $this->createPastDueSubscription(nextRetryAt: '2026-09-27 16:37:22');

        $this->artisan('billing:process-retries')
            ->expectsOutput('Reintentos de cobro procesados: 0')
            ->assertSuccessful();

        $this->assertSame(0, Payment::withoutGlobalScopes()->count());
    }

    public function test_command_does_not_process_retry_after_grace_period(): void
    {
        Carbon::setTestNow('2026-10-03 16:37:22');

        $this->createPastDueSubscription(
            nextRetryAt: '2026-10-03 15:00:00',
            graceEndsAt: '2026-10-03 16:37:22',
        );

        $this->artisan('billing:process-retries')
            ->expectsOutput('Reintentos de cobro procesados: 0')
            ->assertSuccessful();

        $this->assertSame(0, Payment::withoutGlobalScopes()->count());
    }

    public function test_command_ignores_active_subscription(): void
    {
        Carbon::setTestNow('2026-09-27 16:37:22');

        $subscription = $this->createPastDueSubscription(
            nextRetryAt: '2026-09-27 16:37:22'
        );

        $subscription->update(['status' => Subscription::STATUS_ACTIVE]);

        $this->artisan('billing:process-retries')
            ->expectsOutput('Reintentos de cobro procesados: 0')
            ->assertSuccessful();

        $this->assertSame(0, Payment::withoutGlobalScopes()->count());
    }

    public function test_first_retry_idempotency_key_is_unique_per_recovery_episode(): void
    {
        Carbon::setTestNow('2026-09-27 16:37:22');

        $subscription = $this->createPastDueSubscription(
            nextRetryAt: '2026-09-27 16:37:22'
        );

        $this->gateway()->succeedNextCharge('provider-episode-1');

        $this->artisan('billing:process-retries')
            ->expectsOutput('Reintentos de cobro procesados: 1')
            ->assertSuccessful();

        $firstPayment = Payment::withoutGlobalScopes()->latest('id')->firstOrFail();
        $firstKey = $firstPayment->idempotency_key;

        $subscription->refresh();
        $subscription->update([
            'status' => Subscription::STATUS_PAST_DUE,
            'past_due_since' => Carbon::parse('2026-11-01 10:15:00'),
            'grace_ends_at' => Carbon::parse('2026-11-08 10:15:00'),
            'next_retry_at' => Carbon::parse('2026-11-02 10:15:00'),
            'retry_count' => 0,
        ]);

        Carbon::setTestNow('2026-11-02 10:15:00');
        $this->gateway()->succeedNextCharge('provider-episode-2');

        $this->artisan('billing:process-retries')
            ->expectsOutput('Reintentos de cobro procesados: 1')
            ->assertSuccessful();

        $secondPayment = Payment::withoutGlobalScopes()->latest('id')->firstOrFail();
        $secondKey = $secondPayment->idempotency_key;

        $this->assertNotSame($firstKey, $secondKey);
        $this->assertSame(
            sprintf(
                'subscription:%d:recovery:20260926163722:retry:1',
                $subscription->id
            ),
            $firstKey
        );
        $this->assertSame(
            sprintf(
                'subscription:%d:recovery:20261101101500:retry:1',
                $subscription->id
            ),
            $secondKey
        );
        $this->assertSame(2, Payment::withoutGlobalScopes()->count());
    }

    public function test_rerunning_same_due_retry_does_not_duplicate_payment(): void
    {
        Carbon::setTestNow('2026-09-27 16:37:22');

        $subscription = $this->createPastDueSubscription(
            nextRetryAt: '2026-09-27 16:37:22'
        );

        $this->gateway()->failNextCharge(
            'card_declined',
            'Tarjeta rechazada.'
        );

        $this->artisan('billing:process-retries')
            ->expectsOutput('Reintentos de cobro procesados: 1')
            ->assertSuccessful();

        $subscription->refresh();
        $this->assertSame(1, $subscription->retry_count);

        $paymentCount = Payment::withoutGlobalScopes()->count();

        $this->artisan('billing:process-retries')
            ->expectsOutput('Reintentos de cobro procesados: 0')
            ->assertSuccessful();

        $this->assertSame(
            $paymentCount,
            Payment::withoutGlobalScopes()->count()
        );
    }

    private function gateway(): FakePaymentGateway
    {
        $gateway = app(PaymentGateway::class);
        $this->assertInstanceOf(FakePaymentGateway::class, $gateway);
        return $gateway;
    }

    private function createPastDueSubscription(
        string $nextRetryAt,
        string $graceEndsAt = '2026-10-03 16:37:22',
    ): Subscription {
        $tenant = Tenant::create([
            'name' => 'Consultorio Retry ' . uniqid(),
            'slug' => 'consultorio-retry-' . uniqid(),
            'status' => 'active',
            'onboarding_completed_at' => now(),
        ]);

        app(TenantContext::class)->set($tenant);

        return Subscription::create([
            'billing_cycle' => Subscription::BILLING_CYCLE_MONTHLY,
            'billing_amount' => 129900,
            'billing_currency' => 'MXN',
            'status' => Subscription::STATUS_PAST_DUE,
            'starts_at' => Carbon::parse('2026-08-26 16:37:22'),
            'current_period_starts_at' => Carbon::parse('2026-08-26 16:37:22'),
            'current_period_ends_at' => Carbon::parse('2026-09-26 16:37:22'),
            'next_billing_at' => Carbon::parse('2026-09-26 16:37:22'),
            'past_due_since' => Carbon::parse('2026-09-26 16:37:22'),
            'grace_ends_at' => Carbon::parse($graceEndsAt),
            'next_retry_at' => Carbon::parse($nextRetryAt),
            'retry_count' => 0,
            'cancel_at_period_end' => false,
        ]);
    }
}
