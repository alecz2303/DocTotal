<?php

namespace Tests\Feature\Subscription;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PaymentModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_generates_uuid_automatically(): void
    {
        $payment = $this->createPayment();

        $this->assertNotNull(
            $payment->uuid
        );
    }

    public function test_payment_uses_uuid_as_route_key(): void
    {
        $payment = $this->createPayment();

        $this->assertSame(
            'uuid',
            $payment->getRouteKeyName()
        );
    }

    public function test_payment_belongs_to_tenant(): void
    {
        $payment = $this->createPayment();

        $this->assertInstanceOf(
            Tenant::class,
            $payment->tenant
        );

        $this->assertSame(
            $payment->tenant_id,
            $payment->tenant->id
        );
    }

    public function test_payment_belongs_to_subscription(): void
    {
        $payment = $this->createPayment();

        $this->assertInstanceOf(
            Subscription::class,
            $payment->subscription
        );

        $this->assertSame(
            $payment->subscription_id,
            $payment->subscription->id
        );
    }

    public function test_tenant_has_payments(): void
    {
        $payment = $this->createPayment();

        $tenant = $payment->tenant;

        $this->assertTrue(
            $tenant->payments->contains($payment)
        );
    }

    public function test_subscription_has_payments(): void
    {
        $payment = $this->createPayment();

        $subscription = $payment->subscription;

        $this->assertTrue(
            $subscription->payments->contains($payment)
        );
    }

    public function test_payment_amount_is_cast_to_integer(): void
    {
        $payment = $this->createPayment([
            'amount' => 129900,
        ]);

        $this->assertIsInt(
            $payment->amount
        );

        $this->assertSame(
            129900,
            $payment->amount
        );
    }

    public function test_payment_dates_are_cast_to_datetime(): void
    {
        $payment = $this->createPayment([
            'attempted_at' =>
            '2026-09-26 16:37:22',

            'paid_at' =>
            '2026-09-26 16:37:23',

            'failed_at' =>
            null,
        ]);

        $this->assertInstanceOf(
            Carbon::class,
            $payment->attempted_at
        );

        $this->assertInstanceOf(
            Carbon::class,
            $payment->paid_at
        );

        $this->assertNull(
            $payment->failed_at
        );
    }

    public function test_new_payment_defaults_to_pending_status(): void
    {
        $payment = $this->createPayment([
            'status' => null,
        ]);

        $this->assertSame(
            Payment::STATUS_PENDING,
            $payment->status
        );

        $this->assertTrue(
            $payment->isPending()
        );

        $this->assertFalse(
            $payment->isSucceeded()
        );

        $this->assertFalse(
            $payment->isFailed()
        );
    }

    public function test_payment_identifies_succeeded_status(): void
    {
        $payment = $this->createPayment([
            'status' =>
            Payment::STATUS_SUCCEEDED,
        ]);

        $this->assertTrue(
            $payment->isSucceeded()
        );

        $this->assertFalse(
            $payment->isPending()
        );

        $this->assertFalse(
            $payment->isFailed()
        );
    }

    public function test_payment_identifies_failed_status(): void
    {
        $payment = $this->createPayment([
            'status' =>
            Payment::STATUS_FAILED,
        ]);

        $this->assertTrue(
            $payment->isFailed()
        );

        $this->assertFalse(
            $payment->isPending()
        );

        $this->assertFalse(
            $payment->isSucceeded()
        );
    }

    public function test_payment_preserves_currency(): void
    {
        $payment = $this->createPayment([
            'currency' => 'MXN',
        ]);

        $this->assertSame(
            'MXN',
            $payment->currency
        );
    }

    public function test_idempotency_key_must_be_unique(): void
    {
        $payment = $this->createPayment([
            'idempotency_key' =>
            'subscription-1-period-2026-09',
        ]);

        $this->expectException(
            \Illuminate\Database\QueryException::class
        );

        Payment::create([
            'subscription_id' =>
            $payment->subscription_id,

            'amount' =>
            129900,

            'currency' =>
            'MXN',

            'status' =>
            Payment::STATUS_PENDING,

            'attempted_at' =>
            now(),

            'idempotency_key' =>
            'subscription-1-period-2026-09',
        ]);
    }

    public function test_payments_are_isolated_by_tenant(): void
    {
        $paymentA = $this->createPayment();

        $tenantB = Tenant::create([
            'name' => 'Consultorio B',
            'slug' => 'consultorio-b',
            'status' => 'active',
            'onboarding_completed_at' => now(),
        ]);

        app(TenantContext::class)->set(
            $tenantB
        );

        $this->assertNull(
            Payment::find($paymentA->id)
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

                'billing_cycle' =>
                $subscription->billing_cycle,

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
                null,

                'provider_payment_id' =>
                null,

                'idempotency_key' =>
                'payment-' . uniqid(),
            ], $attributes)
        );
    }

    public function test_manual_payment_can_exist_without_subscription(): void
    {
        $existingPayment =
            $this->createPayment();

        $tenant =
            $existingPayment->tenant;

        $payment =
            Payment::create([
                'tenant_id' =>
                $tenant->id,

                'subscription_id' =>
                null,

                'billing_cycle' =>
                Subscription::BILLING_CYCLE_MONTHLY,

                'amount' =>
                60000,

                'currency' =>
                'MXN',

                'status' =>
                Payment::STATUS_PENDING,

                'attempted_at' =>
                now(),

                'idempotency_key' =>
                'manual-payment-without-subscription',
            ]);

        $this->assertNull(
            $payment->subscription_id
        );

        $this->assertSame(
            Subscription::BILLING_CYCLE_MONTHLY,
            $payment->billing_cycle
        );
    }

    public function test_existing_subscription_payment_can_still_reference_subscription(): void
    {
        $payment =
            $this->createPayment([
                'billing_cycle' =>
                Subscription::BILLING_CYCLE_MONTHLY,
            ]);

        $subscription =
            $payment->subscription;

        $this->assertNotNull(
            $payment->subscription_id
        );

        $this->assertInstanceOf(
            Subscription::class,
            $subscription
        );

        $this->assertSame(
            $payment->subscription_id,
            $subscription->id
        );

        $this->assertSame(
            Subscription::BILLING_CYCLE_MONTHLY,
            $payment->billing_cycle
        );
    }

    public function test_payment_casts_promotional_amounts_as_integers(): void
    {
        $payment = $this->createPayment([
            'gross_amount' => '60000',
            'referral_discount_amount' => '5000',
            'promotional_credit_amount' => '15000',
            'amount' => 40000,
        ]);

        $this->assertSame(
            60000,
            $payment->gross_amount
        );

        $this->assertSame(
            5000,
            $payment->referral_discount_amount
        );

        $this->assertSame(
            15000,
            $payment->promotional_credit_amount
        );
    }

    public function test_payment_calculates_total_discount_amount(): void
    {
        $payment = $this->createPayment([
            'gross_amount' => 60000,
            'referral_discount_amount' => 5000,
            'promotional_credit_amount' => 15000,
            'amount' => 40000,
        ]);

        $this->assertSame(
            20000,
            $payment->totalDiscountAmount()
        );
    }

    public function test_payment_returns_contractual_amount(): void
    {
        $payment = $this->createPayment([
            'gross_amount' => 60000,
            'referral_discount_amount' => 5000,
            'amount' => 55000,
        ]);

        $this->assertSame(
            60000,
            $payment->contractualAmount()
        );
    }
}
