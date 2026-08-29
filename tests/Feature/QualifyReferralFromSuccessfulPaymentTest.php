<?php

namespace Tests\Feature;

use App\Actions\Referrals\QualifyReferralFromSuccessfulPayment;
use App\Models\Payment;
use App\Models\PromotionalCredit;
use App\Models\Referral;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use LogicException;
use Tests\TestCase;

class QualifyReferralFromSuccessfulPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_payment_cannot_qualify_referral(): void
    {
        [$referrer, $referred, $referral] =
            $this->createReferral();

        $payment = $this->createPayment(
            $referred,
            Payment::STATUS_FAILED
        );

        $this->expectException(
            LogicException::class
        );

        app(
            QualifyReferralFromSuccessfulPayment::class
        )->execute(
            $payment,
            Carbon::parse('2026-08-15 12:00:00')
        );
    }

    public function test_successful_payment_without_referral_returns_null(): void
    {
        $tenant = $this->createTenant(
            'Tenant Sin Referencia'
        );

        $payment = $this->createPayment(
            $tenant,
            Payment::STATUS_SUCCEEDED
        );

        $result = app(
            QualifyReferralFromSuccessfulPayment::class
        )->execute(
            $payment,
            Carbon::parse('2026-08-15 12:00:00')
        );

        $this->assertNull($result);

        $this->assertDatabaseCount(
            'promotional_credits',
            0
        );
    }

    public function test_first_successful_payment_qualifies_referral(): void
    {
        [$referrer, $referred, $referral] =
            $this->createReferral();

        $payment = $this->createPayment(
            $referred,
            Payment::STATUS_SUCCEEDED
        );

        $qualifiedAt =
            Carbon::parse('2026-08-15 12:00:00');

        $result = app(
            QualifyReferralFromSuccessfulPayment::class
        )->execute(
            $payment,
            $qualifiedAt
        );

        $this->assertNotNull($result);

        $this->assertSame(
            Referral::STATUS_QUALIFIED,
            $result->status
        );

        $this->assertSame(
            $payment->id,
            $result->qualifying_payment_id
        );

        $this->assertTrue(
            $result->qualified_at->equalTo(
                $qualifiedAt
            )
        );

        $this->assertSame(
            Referral::REWARD_GRANTED,
            $result->reward_status
        );

        $this->assertSame(
            '2026-08-01',
            $result->reward_month
                ->toDateString()
        );
    }

    public function test_qualification_creates_fifty_peso_credit_for_referrer(): void
    {
        [$referrer, $referred, $referral] =
            $this->createReferral();

        $payment = $this->createPayment(
            $referred,
            Payment::STATUS_SUCCEEDED
        );

        app(
            QualifyReferralFromSuccessfulPayment::class
        )->execute(
            $payment,
            Carbon::parse('2026-08-15 12:00:00')
        );

        $credit =
            PromotionalCredit::withoutGlobalScopes()
            ->sole();

        $this->assertSame(
            $referrer->id,
            $credit->tenant_id
        );

        $this->assertSame(
            $referral->id,
            $credit->referral_id
        );

        $this->assertSame(
            PromotionalCredit::KIND_REFERRER_REWARD,
            $credit->kind
        );

        $this->assertSame(
            5000,
            $credit->amount
        );

        $this->assertSame(
            PromotionalCredit::STATUS_AVAILABLE,
            $credit->status
        );

        $this->assertNull(
            $credit->payment_id
        );
    }

    public function test_qualification_is_idempotent(): void
    {
        [$referrer, $referred, $referral] =
            $this->createReferral();

        $payment = $this->createPayment(
            $referred,
            Payment::STATUS_SUCCEEDED
        );

        $action = app(
            QualifyReferralFromSuccessfulPayment::class
        );

        $qualifiedAt =
            Carbon::parse('2026-08-15 12:00:00');

        $first = $action->execute(
            $payment,
            $qualifiedAt
        );

        $second = $action->execute(
            $payment,
            $qualifiedAt->copy()->addHour()
        );

        $this->assertSame(
            $first->id,
            $second->id
        );

        $this->assertSame(
            $payment->id,
            $second->qualifying_payment_id
        );

        $this->assertTrue(
            $second->qualified_at->equalTo(
                $qualifiedAt
            )
        );

        $this->assertDatabaseCount(
            'promotional_credits',
            1
        );
    }

    public function test_first_five_qualified_referrals_in_month_receive_reward(): void
    {
        $referrer = $this->createTenant(
            'Tenant Referidor'
        );

        for ($i = 1; $i <= 5; $i++) {
            $referred = $this->createTenant(
                'Tenant Referido ' . $i
            );

            $this->createReferralFor(
                $referrer,
                $referred
            );

            $payment = $this->createPayment(
                $referred,
                Payment::STATUS_SUCCEEDED
            );

            app(
                QualifyReferralFromSuccessfulPayment::class
            )->execute(
                $payment,
                Carbon::parse(
                    sprintf(
                        '2026-08-%02d 12:00:00',
                        $i
                    )
                )
            );
        }

        $this->assertSame(
            5,
            Referral::query()
                ->where(
                    'referrer_tenant_id',
                    $referrer->id
                )
                ->where(
                    'reward_status',
                    Referral::REWARD_GRANTED
                )
                ->count()
        );

        $this->assertSame(
            5,
            PromotionalCredit::withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $referrer->id
                )
                ->count()
        );
    }

    public function test_sixth_qualified_referral_in_month_receives_no_referrer_reward(): void
    {
        $referrer = $this->createTenant(
            'Tenant Referidor'
        );

        $sixthReferral = null;

        for ($i = 1; $i <= 6; $i++) {
            $referred = $this->createTenant(
                'Tenant Referido ' . $i
            );

            $referral =
                $this->createReferralFor(
                    $referrer,
                    $referred
                );

            $payment = $this->createPayment(
                $referred,
                Payment::STATUS_SUCCEEDED
            );

            $qualified =
                app(
                    QualifyReferralFromSuccessfulPayment::class
                )->execute(
                    $payment,
                    Carbon::parse(
                        sprintf(
                            '2026-08-%02d 12:00:00',
                            $i
                        )
                    )
                );

            if ($i === 6) {
                $sixthReferral = $qualified;
            }
        }

        $this->assertNotNull(
            $sixthReferral
        );

        $this->assertSame(
            Referral::STATUS_QUALIFIED,
            $sixthReferral->status
        );

        $this->assertSame(
            Referral::REWARD_MONTHLY_CAP_REACHED,
            $sixthReferral->reward_status
        );

        $this->assertSame(
            '2026-08-01',
            $sixthReferral
                ->reward_month
                ->toDateString()
        );

        $this->assertSame(
            5,
            PromotionalCredit::withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $referrer->id
                )
                ->count()
        );

        $this->assertFalse(
            PromotionalCredit::withoutGlobalScopes()
                ->where(
                    'referral_id',
                    $sixthReferral->id
                )
                ->exists()
        );
    }

    public function test_reward_limit_resets_in_next_calendar_month(): void
    {
        $referrer = $this->createTenant(
            'Tenant Referidor'
        );

        for ($i = 1; $i <= 5; $i++) {
            $referred = $this->createTenant(
                'Tenant Agosto ' . $i
            );

            $this->createReferralFor(
                $referrer,
                $referred
            );

            $payment = $this->createPayment(
                $referred,
                Payment::STATUS_SUCCEEDED
            );

            app(
                QualifyReferralFromSuccessfulPayment::class
            )->execute(
                $payment,
                Carbon::parse(
                    sprintf(
                        '2026-08-%02d 12:00:00',
                        $i
                    )
                )
            );
        }

        $septemberTenant =
            $this->createTenant(
                'Tenant Septiembre'
            );

        $septemberReferral =
            $this->createReferralFor(
                $referrer,
                $septemberTenant
            );

        $septemberPayment =
            $this->createPayment(
                $septemberTenant,
                Payment::STATUS_SUCCEEDED
            );

        $qualified =
            app(
                QualifyReferralFromSuccessfulPayment::class
            )->execute(
                $septemberPayment,
                Carbon::parse(
                    '2026-09-01 12:00:00'
                )
            );

        $this->assertSame(
            Referral::REWARD_GRANTED,
            $qualified->reward_status
        );

        $this->assertSame(
            '2026-09-01',
            $qualified
                ->reward_month
                ->toDateString()
        );

        $this->assertSame(
            6,
            PromotionalCredit::withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $referrer->id
                )
                ->count()
        );

        $this->assertTrue(
            PromotionalCredit::withoutGlobalScopes()
                ->where(
                    'referral_id',
                    $septemberReferral->id
                )
                ->exists()
        );
    }

    private function createReferral(): array
    {
        $referrer =
            $this->createTenant(
                'Tenant Referidor'
            );

        $referred =
            $this->createTenant(
                'Tenant Referido'
            );

        $referral =
            $this->createReferralFor(
                $referrer,
                $referred
            );

        return [
            $referrer,
            $referred,
            $referral,
        ];
    }

    private function createReferralFor(
        Tenant $referrer,
        Tenant $referred,
    ): Referral {
        return Referral::create([
            'referrer_tenant_id' =>
            $referrer->id,

            'referred_tenant_id' =>
            $referred->id,

            'referral_code' =>
            $referrer->referral_code,

            'status' =>
            Referral::STATUS_PENDING,
        ]);
    }

    private function createPayment(
        Tenant $tenant,
        string $status,
    ): Payment {
        app(TenantContext::class)->set(
            $tenant
        );

        $startsAt =
            Carbon::parse(
                '2026-08-01 12:00:00'
            );

        $periodEndsAt =
            Carbon::parse(
                '2026-09-01 12:00:00'
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

                'billing_amount' =>
                60000,

                'billing_currency' =>
                'MXN',

                'retry_count' =>
                0,

                'cancel_at_period_end' =>
                false,
            ]);

        return Payment::create([
            'subscription_id' =>
            $subscription->id,

            'billing_cycle' =>
            $subscription->billing_cycle,

            'gross_amount' =>
            60000,

            'referral_discount_amount' =>
            5000,

            'promotional_credit_amount' =>
            0,

            'amount' =>
            55000,

            'currency' =>
            'MXN',

            'status' =>
            $status,

            'attempted_at' =>
            Carbon::parse(
                '2026-08-15 12:00:00'
            ),

            'paid_at' =>
            $status ===
                Payment::STATUS_SUCCEEDED
                ? Carbon::parse(
                    '2026-08-15 12:00:00'
                )
                : null,

            'failed_at' =>
            $status ===
                Payment::STATUS_FAILED
                ? Carbon::parse(
                    '2026-08-15 12:00:00'
                )
                : null,

            'idempotency_key' =>
            'referral-payment-'
                . uniqid(),
        ]);
    }

    private function createTenant(
        string $name
    ): Tenant {
        return Tenant::create([
            'name' => $name,

            'slug' =>
            strtolower(
                str_replace(
                    ' ',
                    '-',
                    $name
                )
            )
                . '-'
                . uniqid(),

            'status' =>
            'active',

            'timezone' =>
            'America/Mexico_City',

            'currency' =>
            'MXN',

            'onboarding_completed_at' =>
            now(),
        ]);
    }
}
