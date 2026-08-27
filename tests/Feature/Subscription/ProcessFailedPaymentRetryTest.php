<?php

namespace Tests\Feature\Subscription;

use App\Actions\Billing\ProcessFailedPaymentRetry;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use LogicException;
use Tests\TestCase;

class ProcessFailedPaymentRetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_failed_retry_increments_retry_count(): void
    {
        [$subscription, $payment] =
            $this->createRecoveryPayment();

        app(
            ProcessFailedPaymentRetry::class
        )->execute(
            $payment,
            Carbon::parse(
                '2026-09-27 16:37:22'
            ),
            'card_declined',
            'Tarjeta rechazada.'
        );

        $subscription->refresh();

        $this->assertSame(
            1,
            $subscription->retry_count
        );

        $this->assertTrue(
            $subscription
                ->next_retry_at
                ->equalTo(
                    '2026-09-29 16:37:22'
                )
        );
    }

    public function test_second_failed_retry_schedules_third_retry(): void
    {
        [$subscription, $payment] =
            $this->createRecoveryPayment(
                retryCount: 1,
                nextRetryAt: '2026-09-29 16:37:22'
            );

        app(
            ProcessFailedPaymentRetry::class
        )->execute(
            $payment,
            Carbon::parse(
                '2026-09-29 16:37:22'
            )
        );

        $subscription->refresh();

        $this->assertSame(
            2,
            $subscription->retry_count
        );

        $this->assertTrue(
            $subscription
                ->next_retry_at
                ->equalTo(
                    '2026-10-02 16:37:22'
                )
        );
    }

    public function test_last_failed_retry_clears_next_retry(): void
    {
        [$subscription, $payment] =
            $this->createRecoveryPayment(
                retryCount: 2,
                nextRetryAt: '2026-10-02 16:37:22'
            );

        app(
            ProcessFailedPaymentRetry::class
        )->execute(
            $payment,
            Carbon::parse(
                '2026-10-02 16:37:22'
            )
        );

        $subscription->refresh();

        $this->assertSame(
            3,
            $subscription->retry_count
        );

        $this->assertNull(
            $subscription->next_retry_at
        );
    }

    public function test_failed_retry_does_not_restart_recovery_period(): void
    {
        [$subscription, $payment] =
            $this->createRecoveryPayment();

        $originalPastDueSince =
            $subscription
            ->past_due_since
            ->copy();

        $originalGraceEndsAt =
            $subscription
            ->grace_ends_at
            ->copy();

        app(
            ProcessFailedPaymentRetry::class
        )->execute(
            $payment,
            Carbon::parse(
                '2026-09-27 16:37:22'
            )
        );

        $subscription->refresh();

        $this->assertTrue(
            $subscription
                ->past_due_since
                ->equalTo(
                    $originalPastDueSince
                )
        );

        $this->assertTrue(
            $subscription
                ->grace_ends_at
                ->equalTo(
                    $originalGraceEndsAt
                )
        );
    }

    public function test_failed_retry_marks_payment_as_failed(): void
    {
        [, $payment] =
            $this->createRecoveryPayment();

        $failedAt = Carbon::parse(
            '2026-09-27 16:37:22'
        );

        $payment = app(
            ProcessFailedPaymentRetry::class
        )->execute(
            $payment,
            $failedAt,
            'insufficient_funds',
            'Fondos insuficientes.',
            'provider-retry-1'
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
            'insufficient_funds',
            $payment->failure_code
        );

        $this->assertSame(
            'provider-retry-1',
            $payment->provider_payment_id
        );
    }

    public function test_retry_requires_past_due_subscription(): void
    {
        [$subscription, $payment] =
            $this->createRecoveryPayment();

        $subscription->update([
            'status' =>
            Subscription::STATUS_ACTIVE,
        ]);

        $this->expectException(
            LogicException::class
        );

        app(
            ProcessFailedPaymentRetry::class
        )->execute(
            $payment,
            now()
        );
    }

    public function test_retry_cannot_be_processed_after_grace_period(): void
    {
        [, $payment] =
            $this->createRecoveryPayment();

        $this->expectException(
            LogicException::class
        );

        app(
            ProcessFailedPaymentRetry::class
        )->execute(
            $payment,
            Carbon::parse(
                '2026-10-03 16:37:22'
            )
        );
    }

    public function test_retry_cannot_exceed_configured_retry_count(): void
    {
        [$subscription, $payment] =
            $this->createRecoveryPayment(
                retryCount: 3,
                nextRetryAt: null
            );

        $this->expectException(
            LogicException::class
        );

        app(
            ProcessFailedPaymentRetry::class
        )->execute(
            $payment,
            Carbon::parse(
                '2026-10-02 17:00:00'
            )
        );
    }

    private function createRecoveryPayment(
        int $retryCount = 0,
        ?string $nextRetryAt =
        '2026-09-27 16:37:22',
    ): array {
        $tenant = Tenant::create([
            'name' =>
            'Consultorio Recovery',

            'slug' =>
            'consultorio-recovery-' .
                uniqid(),

            'status' =>
            'active',

            'onboarding_completed_at' =>
            now(),
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        $subscription =
            Subscription::create([
                'billing_cycle' =>
                Subscription::BILLING_CYCLE_MONTHLY,

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
                $nextRetryAt
                    ? Carbon::parse(
                        $nextRetryAt
                    )
                    : null,

                'retry_count' =>
                $retryCount,

                'cancel_at_period_end' =>
                false,
            ]);

        $payment = Payment::create([
            'subscription_id' =>
            $subscription->id,

            'amount' =>
            129900,

            'currency' =>
            'MXN',

            'status' =>
            Payment::STATUS_PENDING,

            'attempted_at' =>
            $nextRetryAt
                ? Carbon::parse(
                    $nextRetryAt
                )
                : now(),

            'provider' =>
            'test',

            'idempotency_key' =>
            'retry-' . uniqid(),
        ]);

        return [
            $subscription,
            $payment,
        ];
    }
}
