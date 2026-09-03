<?php

namespace Tests\Feature\Internal;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalBillingAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_internal_billing(): void
    {
        $this->get(route('internal.billing.index'))
            ->assertRedirect(route('login'));
    }

    public function test_tenant_user_cannot_access_internal_billing(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Tenant clínico',
            'slug' => 'tenant-clinico',
            'status' => 'trial',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => User::ROLE_OWNER,
        ]);

        $this->actingAs($user)
            ->get(route('internal.billing.index'))
            ->assertForbidden();
    }

    public function test_internal_admin_can_access_internal_billing(): void
    {
        $user = User::factory()->create([
            'tenant_id' => null,
            'role' => User::ROLE_INTERNAL_ADMIN,
        ]);

        $this->actingAs($user)
            ->get(route('internal.billing.index'))
            ->assertOk()
            ->assertViewIs('internal.billing.index')
            ->assertViewHasAll([
                'summary',
                'failedPayments',
                'pastDueSubscriptions',
            ]);
    }
}
