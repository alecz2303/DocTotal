<?php

namespace Tests\Feature\Subscription;

use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ProcessExpiredGracePeriodsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_command_suspends_tenant_with_expired_grace_period(): void
    {
        Carbon::setTestNow(
            '2026-10-03 16:37:22'
        );

        [$tenant] =
            $this->createPastDueSubscription(
                graceEndsAt: '2026-10-03 16:37:22'
            );

        $this->artisan(
            'billing:process-expired-grace-periods'
        )
            ->expectsOutput(
                'Periodos de gracia procesados: 1'
            )
            ->assertSuccessful();

        $tenant->refresh();

        $this->assertSame(
            'suspended',
            $tenant->status
        );

        $this->assertTrue(
            $tenant->suspended_at->equalTo(
                now()
            )
        );
    }

    public function test_command_does_not_suspend_before_grace_period_end(): void
    {
        Carbon::setTestNow(
            '2026-10-03 16:37:21'
        );

        [$tenant] =
            $this->createPastDueSubscription(
                graceEndsAt: '2026-10-03 16:37:22'
            );

        $this->artisan(
            'billing:process-expired-grace-periods'
        )
            ->expectsOutput(
                'Periodos de gracia procesados: 0'
            )
            ->assertSuccessful();

        $tenant->refresh();

        $this->assertSame(
            'active',
            $tenant->status
        );

        $this->assertNull(
            $tenant->suspended_at
        );
    }

    public function test_command_ignores_active_subscriptions(): void
    {
        Carbon::setTestNow(
            '2026-10-04 09:00:00'
        );

        [$tenant, $subscription] =
            $this->createPastDueSubscription(
                graceEndsAt: '2026-10-03 16:37:22'
            );

        $subscription->update([
            'status' =>
            Subscription::STATUS_ACTIVE,
        ]);

        $this->artisan(
            'billing:process-expired-grace-periods'
        )
            ->expectsOutput(
                'Periodos de gracia procesados: 0'
            )
            ->assertSuccessful();

        $tenant->refresh();

        $this->assertSame(
            'active',
            $tenant->status
        );
    }

    public function test_command_processes_multiple_tenants_without_tenant_context(): void
    {
        Carbon::setTestNow(
            '2026-10-04 09:00:00'
        );

        [$tenantA] =
            $this->createPastDueSubscription(
                graceEndsAt: '2026-10-03 16:37:22'
            );

        app(TenantContext::class)->clear();

        [$tenantB] =
            $this->createPastDueSubscription(
                graceEndsAt: '2026-10-03 18:00:00'
            );

        app(TenantContext::class)->clear();

        $this->artisan(
            'billing:process-expired-grace-periods'
        )
            ->expectsOutput(
                'Periodos de gracia procesados: 2'
            )
            ->assertSuccessful();

        $this->assertSame(
            'suspended',
            $tenantA->refresh()->status
        );

        $this->assertSame(
            'suspended',
            $tenantB->refresh()->status
        );
    }

    public function test_command_is_idempotent_after_tenant_is_already_suspended(): void
    {
        Carbon::setTestNow(
            '2026-10-04 09:00:00'
        );

        [$tenant] =
            $this->createPastDueSubscription(
                graceEndsAt: '2026-10-03 16:37:22',
                tenantStatus: 'suspended',
            );

        $originalSuspendedAt =
            Carbon::parse(
                '2026-10-03 16:37:22'
            );

        $tenant->update([
            'suspended_at' =>
            $originalSuspendedAt,
        ]);

        $this->artisan(
            'billing:process-expired-grace-periods'
        )
            ->assertSuccessful();

        $tenant->refresh();

        $this->assertTrue(
            $tenant
                ->suspended_at
                ->equalTo(
                    $originalSuspendedAt
                )
        );
    }

    private function createPastDueSubscription(
        string $graceEndsAt,
        string $tenantStatus = 'active',
    ): array {
        $tenant = Tenant::create([
            'name' =>
            'Consultorio Grace ' .
                uniqid(),

            'slug' =>
            'consultorio-grace-' .
                uniqid(),

            'status' =>
            $tenantStatus,

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
                    $graceEndsAt
                ),

                'next_retry_at' =>
                null,

                'retry_count' =>
                3,

                'cancel_at_period_end' =>
                false,

                'cancelled_at' =>
                null,
            ]);

        return [
            $tenant,
            $subscription,
        ];
    }
}
