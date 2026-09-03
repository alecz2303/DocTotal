<?php

namespace Tests\Feature\Internal;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_internal_area(): void
    {
        $this->get('/internal')->assertRedirect('/login');
    }

    public function test_owner_cannot_access_internal_area(): void
    {
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
        ]);

        $this->actingAs($owner)
            ->get('/internal')
            ->assertForbidden();
    }

    public function test_internal_admin_with_tenant_cannot_access_internal_area(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant de prueba',
            'slug' => 'tenant-prueba',
            'status' => 'trial',
            'trial_started_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);

        $tenantUser = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => User::ROLE_INTERNAL_ADMIN,
        ]);

        $this->actingAs($tenantUser)
            ->get('/internal')
            ->assertForbidden();
    }

    public function test_internal_admin_without_tenant_can_access_internal_area(): void
    {
        $internalAdmin = User::factory()->create([
            'tenant_id' => null,
            'role' => User::ROLE_INTERNAL_ADMIN,
        ]);

        $this->actingAs($internalAdmin)
            ->get('/internal')
            ->assertOk()
            ->assertSee('DocTotal Internal');
    }

    public function test_internal_admin_can_access_tenant_list(): void
    {
        Tenant::create([
            'name' => 'Clínica de prueba',
            'slug' => 'clinica-prueba',
            'status' => 'trial',
            'trial_started_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);

        $internalAdmin = User::factory()->create([
            'tenant_id' => null,
            'role' => User::ROLE_INTERNAL_ADMIN,
        ]);

        $this->actingAs($internalAdmin)
            ->get('/internal/tenants')
            ->assertOk()
            ->assertSee('Tenants')
            ->assertSee('Clínica de prueba');
    }

    public function test_owner_cannot_access_tenant_list(): void
    {
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
        ]);

        $this->actingAs($owner)
            ->get('/internal/tenants')
            ->assertForbidden();
    }
}
