<?php

namespace Tests\Feature\Dashboard;

use App\Models\DoctorProfile;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardCommercialStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_trial_notice_is_visible_after_clinical_priority_and_links_to_billing(): void
    {
        Carbon::setTestNow('2026-09-06 10:00:00');

        [$tenant, $user] = $this->createContext([
            'status' => 'trial',
            'trial_started_at' => now()->subDays(5),
            'trial_ends_at' => now()->addDays(5),
        ]);

        $response = $this->actingAs($user)
            ->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSeeInOrder([
                'Prioridad ahora',
                'Periodo de prueba',
            ])
            ->assertSee('5 días')
            ->assertSee(route('settings.billing'), false);
    }

    public function test_active_subscription_does_not_add_commercial_notice_to_dashboard(): void
    {
        Carbon::setTestNow('2026-09-06 10:00:00');

        [$tenant, $user] = $this->createContext([
            'status' => 'active',
        ]);

        $this->createSubscription($tenant, [
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Estado de suscripción')
            ->assertDontSee('Periodo de prueba');
    }

    public function test_past_due_notice_uses_existing_recovery_destination(): void
    {
        Carbon::setTestNow('2026-09-06 10:00:00');

        [$tenant, $user] = $this->createContext([
            'status' => 'active',
        ]);

        $this->createSubscription($tenant, [
            'status' => Subscription::STATUS_PAST_DUE,
            'past_due_since' => now()->subDay(),
            'grace_ends_at' => now()->addDays(6),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pago pendiente')
            ->assertSee('Resolver pago')
            ->assertSee(route('settings.billing'), false);
    }

    private function createContext(array $tenantAttributes = []): array
    {
        $tenant = Tenant::create(array_merge([
            'name' => 'Consultorio Dashboard Comercial',
            'slug' => 'dashboard-comercial-' . uniqid(),
            'status' => 'active',
            'onboarding_completed_at' => now(),
        ], $tenantAttributes));

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);

        DoctorProfile::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'first_name' => 'Doctor',
            'last_name' => 'Comercial',
        ]);

        app(TenantContext::class)->set($tenant);

        return [$tenant, $user];
    }

    private function createSubscription(
        Tenant $tenant,
        array $attributes = []
    ): Subscription {
        app(TenantContext::class)->set($tenant);

        $startsAt = now()->subDay();
        $periodEndsAt = now()->addMonth();

        return Subscription::create(array_merge([
            'billing_cycle' => Subscription::BILLING_CYCLE_MONTHLY,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => $startsAt,
            'current_period_starts_at' => $startsAt,
            'current_period_ends_at' => $periodEndsAt,
            'next_billing_at' => $periodEndsAt,
            'past_due_since' => null,
            'grace_ends_at' => null,
            'next_retry_at' => null,
            'retry_count' => 0,
            'cancel_at_period_end' => false,
            'cancelled_at' => null,
        ], $attributes));
    }
}
