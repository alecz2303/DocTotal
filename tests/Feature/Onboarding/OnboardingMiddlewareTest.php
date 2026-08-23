<?php

namespace Tests\Feature\Onboarding;

use App\Models\DoctorProfile;
use App\Models\PracticeProfile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_onboarding(): void
    {
        $this->get('/onboarding')
            ->assertRedirect('/login');
    }

    public function test_user_with_incomplete_onboarding_is_redirected_from_dashboard(): void
    {
        [$tenant, $user] = $this->createUser();

        $this->assertNull($tenant->onboarding_completed_at);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect('/onboarding');
    }

    public function test_user_with_incomplete_onboarding_can_access_onboarding(): void
    {
        [, $user] = $this->createUser();

        $this->actingAs($user)
            ->get('/onboarding')
            ->assertOk()
            ->assertSee('Configura tu consultorio');
    }

    public function test_user_with_completed_onboarding_can_access_dashboard(): void
    {
        [$tenant, $user] = $this->createUser();

        $tenant->update([
            'onboarding_completed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk();
    }

    private function createUser(): array
    {
        $tenant = Tenant::create([
            'name' => 'Consultorio Test',
            'slug' => 'consultorio-test',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Dr. Test',
            'email' => 'doctor@example.com',
            'password' => 'password123',
            'role' => 'owner',
        ]);

        DoctorProfile::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'first_name' => 'Doctor',
            'last_name' => 'Test',
        ]);

        PracticeProfile::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'public_name' => 'Consultorio Test',
        ]);

        return [$tenant, $user];
    }
}
