<?php

namespace Tests\Feature\Subscription;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use LogicException;
use Tests\TestCase;

class PaymentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_payment_can_succeed(): void
    {
        $payment = $this->createPayment();

        $paidAt = Carbon::parse(
            '2026-09-26 16:37:23'
        );

        $payment->succeed(
            $paidAt,
            'provider-payment-123'
        );

        $payment->refresh();

        $this->assertSame(
            Payment::STATUS_SUCCEEDED,
            $payment->status
        );

        $this->assertTrue(
            $payment->paid_at->equalTo(
                $paidAt
            )
        );

        $this->assertNull(
            $payment->failed_at
        );

        $this->assertNull(
            $payment->failure_code
        );

        $this->assertNull(
            $payment->failure_message
        );

        $this->assertSame(
            'provider-payment-123',
            $payment->provider_payment_id
        );
    }

    public function test_pending_payment_can_fail(): void
    {
        $payment = $this->createPayment();

        $failedAt = Carbon::parse(
            '2026-09-26 16:37:23'
        );

        $payment->fail(
            $failedAt,
            'card_declined',
            'La tarjeta fue rechazada.',
            'provider-payment-456'
        );

        $payment->refresh();

        $this->assertSame(
            Payment::STATUS_FAILED,
            $payment->status
        );

        $this->assertTrue(
            $payment->failed_at->equalTo(
                $failedAt
            )
        );

        $this->assertNull(
            $payment->paid_at
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
            'provider-payment-456',
            $payment->provider_payment_id
        );
    }

    public function test_succeeded_payment_cannot_succeed_again(): void
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

        $payment->succeed(
            now()
        );
    }

    public function test_failed_payment_cannot_succeed(): void
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

        $payment->succeed(
            now()
        );
    }

    public function test_succeeded_payment_cannot_fail(): void
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

        $payment->fail(
            now()
        );
    }

    public function test_failed_payment_cannot_fail_again(): void
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

        $payment->fail(
            now()
        );
    }

    public function test_succeed_preserves_existing_provider_payment_id_when_none_is_given(): void
    {
        $payment = $this->createPayment([
            'provider_payment_id' =>
            'provider-existing-id',
        ]);

        $payment->succeed(
            now()
        );

        $payment->refresh();

        $this->assertSame(
            'provider-existing-id',
            $payment->provider_payment_id
        );
    }

    public function test_fail_preserves_existing_provider_payment_id_when_none_is_given(): void
    {
        $payment = $this->createPayment([
            'provider_payment_id' =>
            'provider-existing-id',
        ]);

        $payment->fail(
            now(),
            'declined',
            'Pago rechazado.'
        );

        $payment->refresh();

        $this->assertSame(
            'provider-existing-id',
            $payment->provider_payment_id
        );
    }

    private function createPayment(
        array $attributes = []
    ): Payment {
        $tenant = Tenant::create([
            'name' => 'Consultorio Test',
            'slug' => 'consultorio-test-' . uniqid(),
            'status' => 'active',
            'onboarding_completed_at' => now(),
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

        $subscription = Subscription::create([
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
                Carbon::parse(
                    '2026-09-26 16:37:22'
                ),

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
