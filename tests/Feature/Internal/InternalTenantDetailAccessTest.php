<?php

namespace Tests\Feature\Internal;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalTenantDetailAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_admin_can_access_tenant_operational_detail(): void
    {
        $tenant = Tenant::create([
            'name' => 'Clínica Detalle',
            'slug' => 'clinica-detalle-access',
            'status' => 'trial',
            'trial_started_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);

        $internalAdmin = User::factory()->create([
            'tenant_id' => null,
            'role' => User::ROLE_INTERNAL_ADMIN,
        ]);

        $this->actingAs($internalAdmin)
            ->get(route('internal.tenants.show', $tenant))
            ->assertOk()
            ->assertSee('Clínica Detalle')
            ->assertSee('Usuarios')
            ->assertSee('Últimos pagos');
    }

    public function test_owner_cannot_access_tenant_operational_detail(): void
    {
        $tenant = Tenant::create([
            'name' => 'Clínica Protegida',
            'slug' => 'clinica-protegida',
            'status' => 'trial',
            'trial_started_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);

        $owner = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => User::ROLE_OWNER,
        ]);

        $this->actingAs($owner)
            ->get(route('internal.tenants.show', $tenant))
            ->assertForbidden();
    }
}
