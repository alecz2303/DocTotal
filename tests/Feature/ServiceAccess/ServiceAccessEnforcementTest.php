<?php

namespace Tests\Feature\ServiceAccess;

use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceAccessEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_suspended_tenant_is_redirected_away_from_clinical_dashboard(): void
    {
        $tenant = $this->createTenant([
            'status' => 'suspended',
            'suspended_at' => now(),
            'onboarding_completed_at' => now(),
        ]);

        $user = $this->createTenantUser($tenant);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('service.suspended'));
    }

    public function test_expired_trial_without_subscription_is_blocked(): void
    {
        $tenant = $this->createTenant([
            'status' => 'trial',
            'trial_started_at' => now()->subDays(4),
            'trial_ends_at' => now()->subDay(),
            'onboarding_completed_at' => now(),
        ]);

        $user = $this->createTenantUser($tenant);

        $this->actingAs($user)
            ->get(route('patients.index'))
            ->assertRedirect(route('service.suspended'));
    }

    public function test_active_trial_can_access_clinical_routes(): void
    {
        $tenant = $this->createTenant([
            'status' => 'trial',
            'trial_started_at' => now()->subDay(),
            'trial_ends_at' => now()->addDays(2),
            'onboarding_completed_at' => now(),
        ]);

        $user = $this->createTenantUser($tenant);

        $response = $this->actingAs($user)
            ->get(route('patients.index'));

        $this->assertNotSame(302, $response->getStatusCode());
    }

    public function test_active_subscription_can_access_after_trial(): void
    {
        $tenant = $this->createTenant([
            'status' => 'active',
            'trial_started_at' => now()->subMonth(),
            'trial_ends_at' => now()->subWeeks(3),
            'onboarding_completed_at' => now(),
        ]);

        $this->createSubscription(
            $tenant,
            Subscription::STATUS_ACTIVE
        );

        $user = $this->createTenantUser($tenant);

        $response = $this->actingAs($user)
            ->get(route('patients.index'));

        $this->assertNotSame(302, $response->getStatusCode());
    }

    public function test_past_due_subscription_in_grace_period_can_access(): void
    {
        $tenant = $this->createTenant([
            'status' => 'active',
            'onboarding_completed_at' => now(),
        ]);

        $this->createSubscription(
            $tenant,
            Subscription::STATUS_PAST_DUE,
            [
                'grace_ends_at' => now()->addDay(),
            ]
        );

        $user = $this->createTenantUser($tenant);

        $response = $this->actingAs($user)
            ->get(route('patients.index'));

        $this->assertNotSame(302, $response->getStatusCode());
    }

    public function test_billing_and_suspended_page_remain_available_without_service_access(): void
    {
        $tenant = $this->createTenant([
            'status' => 'suspended',
            'suspended_at' => now(),
            'onboarding_completed_at' => now(),
        ]);

        $user = $this->createTenantUser($tenant);

        $this->actingAs($user)
            ->get(route('service.suspended'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('settings.billing'))
            ->assertOk();
    }

    private function createTenant(array $overrides = []): Tenant
    {
        return Tenant::query()->create(array_merge([
            'name' => 'Tenant Test',
            'slug' => 'tenant-test-'.uniqid(),
            'status' => 'trial',
        ], $overrides));
    }

    private function createTenantUser(Tenant $tenant): User
    {
        return User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => User::ROLE_OWNER,
        ]);
    }

    private function createSubscription(
        Tenant $tenant,
        string $status,
        array $overrides = []
    ): Subscription {
        return Subscription::query()
            ->withoutGlobalScopes()
            ->create(array_merge([
                'tenant_id' => $tenant->id,
                'billing_cycle' =>
                    Subscription::BILLING_CYCLE_MONTHLY,
                'status' => $status,
                'starts_at' => now()->subMonth(),
                'current_period_starts_at' => now()->subDay(),
                'current_period_ends_at' => now()->addMonth(),
                'billing_amount' => 10000,
                'billing_currency' => 'MXN',
            ], $overrides));
    }
}
