<?php

namespace Tests\Feature\Internal;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalCommunicationAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_internal_communications(): void
    {
        $this->get(route('internal.communications.index'))
            ->assertRedirect(route('login'));
    }

    public function test_tenant_user_cannot_access_internal_communications(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Tenant Test',
            'slug' => 'tenant-test',
            'status' => 'trial',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => User::ROLE_OWNER,
        ]);

        $this->actingAs($user)
            ->get(route('internal.communications.index'))
            ->assertForbidden();
    }

    public function test_internal_admin_can_access_internal_communications(): void
    {
        $user = User::factory()->create([
            'tenant_id' => null,
            'role' => User::ROLE_INTERNAL_ADMIN,
        ]);

        $this->actingAs($user)
            ->get(route('internal.communications.index'))
            ->assertOk()
            ->assertViewIs('internal.communications.index')
            ->assertViewHasAll([
                'summary',
                'failedCommunications',
                'pendingCommunications',
                'cancelledCommunications',
            ]);
    }
}
