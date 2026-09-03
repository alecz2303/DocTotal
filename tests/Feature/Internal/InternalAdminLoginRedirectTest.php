<?php

namespace Tests\Feature\Internal;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InternalAdminLoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_admin_is_redirected_to_internal_dashboard_after_login(): void
    {
        $user = User::factory()->create([
            'tenant_id' => null,
            'role' => User::ROLE_INTERNAL_ADMIN,
            'email' => 'internal-admin@doctotal.test',
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        $response->assertRedirect(route('internal.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_tenant_user_keeps_standard_dashboard_redirect_after_login(): void
    {
        $tenant = Tenant::create([
            'name' => 'Clínica Login',
            'slug' => 'clinica-login',
            'status' => 'trial',
            'trial_started_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => User::ROLE_OWNER,
            'email' => 'owner-login@doctotal.test',
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        $response->assertRedirect(config('fortify.home'));

        $this->assertAuthenticatedAs($user);
    }
}
