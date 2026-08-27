<?php

namespace Tests\Feature\Subscription;

use App\Actions\Billing\ProcessFailedPayment;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use LogicException;
use Tests\TestCase;

class ProcessFailedPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_payment_marks_payment_as_failed(): void
    {
        $payment = $this->createPayment();

        $failedAt = Carbon::parse(
            '2026-09-26 16:37:22'
        );

        $payment = app(
            ProcessFailedPayment::class
        )->execute(
            $payment,
            $failedAt,
            'card_declined',
            'La tarjeta fue rechazada.',
            'provider-payment-100'
        );

        $this->assertSame(
            Payment::STATUS_FAILED,
            $payment->status
        );

        $this->assertTrue(
            $payment->failed_at->equalTo(
                $failedAt
            )
        );

        $this->assertSame(
            'card_declined',
            $payment->failure_code
        );

        $this->assertSame(
            'La tarjeta fue rechazada.',
            $payment->failure_message
        );

        $this->assertSame(
            'provider-payment-100',
            $payment->provider_payment_id
        );
    }

    public function test_failed_payment_starts_subscription_recovery(): void
    {
        $payment = $this->createPayment();

        $failedAt = Carbon::parse(
            '2026-09-26 16:37:22'
        );

        app(
            ProcessFailedPayment::class
        )->execute(
            $payment,
            $failedAt
        );

        $subscription =
            $payment->subscription->refresh();

        $this->assertSame(
            Subscription::STATUS_PAST_DUE,
            $subscription->status
        );

        $this->assertTrue(
            $subscription
                ->past_due_since
                ->equalTo($failedAt)
        );

        $this->assertTrue(
            $subscription
                ->grace_ends_at
                ->equalTo(
                    Carbon::parse(
                        '2026-10-03 16:37:22'
                    )
                )
        );

        $this->assertTrue(
            $subscription
                ->next_retry_at
                ->equalTo(
                    Carbon::parse(
                        '2026-09-27 16:37:22'
                    )
                )
        );

        $this->assertSame(
            0,
            $subscription->retry_count
        );
    }

    public function test_failed_payment_does_not_suspend_tenant_immediately(): void
    {
        $payment = $this->createPayment();

        app(
            ProcessFailedPayment::class
        )->execute(
            $payment,
            Carbon::parse(
                '2026-09-26 16:37:22'
            )
        );

        $tenant =
            $payment->tenant->refresh();

        $this->assertSame(
            'active',
            $tenant->status
        );

        $this->assertNull(
            $tenant->suspended_at
        );
    }

    public function test_failed_payment_preserves_exact_failure_time(): void
    {
        $payment = $this->createPayment();

        $failedAt = Carbon::parse(
            '2026-09-26 23:48:51'
        );

        app(
            ProcessFailedPayment::class
        )->execute(
            $payment,
            $failedAt
        );

        $payment->refresh();

        $subscription =
            $payment->subscription->refresh();

        $this->assertSame(
            '23:48:51',
            $payment
                ->failed_at
                ->format('H:i:s')
        );

        $this->assertSame(
            '23:48:51',
            $subscription
                ->past_due_since
                ->format('H:i:s')
        );

        $this->assertSame(
            '23:48:51',
            $subscription
                ->grace_ends_at
                ->format('H:i:s')
        );

        $this->assertSame(
            '23:48:51',
            $subscription
                ->next_retry_at
                ->format('H:i:s')
        );
    }

    public function test_succeeded_payment_cannot_be_processed_as_failed(): void
    {
        $payment = $this->createPayment([
            'status' =>
            Payment::STATUS_SUCCEEDED,

            'paid_at' =>
            now(),
        ]);

        $this->expectException(
            LogicException::class
        );

        app(
            ProcessFailedPayment::class
        )->execute(
            $payment,
            now()
        );
    }

    public function test_failed_payment_cannot_be_processed_as_failed_again(): void
    {
        $payment = $this->createPayment([
            'status' =>
            Payment::STATUS_FAILED,

            'failed_at' =>
            now(),
        ]);

        $this->expectException(
            LogicException::class
        );

        app(
            ProcessFailedPayment::class
        )->execute(
            $payment,
            now()
        );
    }

    public function test_first_failure_cannot_restart_existing_recovery(): void
    {
        $payment = $this->createPayment();

        $payment->subscription->update([
            'status' =>
            Subscription::STATUS_PAST_DUE,

            'past_due_since' =>
            now()->subDay(),

            'grace_ends_at' =>
            now()->addDays(6),

            'next_retry_at' =>
            now(),

            'retry_count' =>
            0,
        ]);

        $this->expectException(
            LogicException::class
        );

        app(
            ProcessFailedPayment::class
        )->execute(
            $payment,
            now()
        );
    }

    private function createPayment(
        array $attributes = []
    ): Payment {
        $tenant = Tenant::create([
            'name' =>
            'Consultorio Test',

            'slug' =>
            'consultorio-test-' . uniqid(),

            'status' =>
            'active',

            'onboarding_completed_at' =>
            now(),
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        $startsAt = Carbon::parse(
            '2026-08-26 16:37:22'
        );

        $periodEndsAt = Carbon::parse(
            '2026-09-26 16:37:22'
        );

        $subscription =
            Subscription::create([
                'billing_cycle' =>
                Subscription::BILLING_CYCLE_MONTHLY,

                'status' =>
                Subscription::STATUS_ACTIVE,

                'starts_at' =>
                $startsAt,

                'current_period_starts_at' =>
                $startsAt,

                'current_period_ends_at' =>
                $periodEndsAt,

                'next_billing_at' =>
                $periodEndsAt,

                'past_due_since' =>
                null,

                'grace_ends_at' =>
                null,

                'next_retry_at' =>
                null,

                'retry_count' =>
                0,

                'cancel_at_period_end' =>
                false,

                'cancelled_at' =>
                null,
            ]);

        return Payment::create(
            array_merge([
                'subscription_id' =>
                $subscription->id,

                'amount' =>
                129900,

                'currency' =>
                'MXN',

                'status' =>
                Payment::STATUS_PENDING,

                'attempted_at' =>
                $periodEndsAt,

                'paid_at' =>
                null,

                'failed_at' =>
                null,

                'failure_code' =>
                null,

                'failure_message' =>
                null,

                'provider' =>
                'test',

                'provider_payment_id' =>
                null,

                'idempotency_key' =>
                'payment-' . uniqid(),
            ], $attributes)
        );
    }
}
