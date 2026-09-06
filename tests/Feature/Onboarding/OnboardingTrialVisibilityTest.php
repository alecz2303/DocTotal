<?php

namespace Tests\Feature\Onboarding;

use App\Models\DoctorProfile;
use App\Models\PracticeProfile;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OnboardingTrialVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_trial_is_visible_during_onboarding(): void
    {
        $this->travelTo(Carbon::parse('2026-09-06 09:00:00'));

        $tenant = Tenant::create([
            'name' => 'Consultorio Trial',
            'slug' => 'consultorio-trial',
            'status' => 'trial',
            'trial_started_at' => now(),
            'trial_ends_at' => now()->addDays(10),
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);

        DoctorProfile::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'first_name' => 'Doctora',
            'last_name' => 'Prueba',
        ]);

        PracticeProfile::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'public_name' => 'Consultorio Trial',
        ]);

        app(TenantContext::class)->set($tenant);

        $this->actingAs($user)
            ->get(route('onboarding'))
            ->assertOk()
            ->assertSee('Periodo de prueba activo.')
            ->assertSee('Tienes 10 días de prueba disponibles.');
    }

    public function test_non_trial_tenant_does_not_see_trial_banner_during_onboarding(): void
    {
        $tenant = Tenant::create([
            'name' => 'Consultorio Activo',
            'slug' => 'consultorio-activo',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);

        DoctorProfile::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'first_name' => 'Doctor',
            'last_name' => 'Activo',
        ]);

        PracticeProfile::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'public_name' => 'Consultorio Activo',
        ]);

        app(TenantContext::class)->set($tenant);

        $this->actingAs($user)
            ->get(route('onboarding'))
            ->assertOk()
            ->assertDontSee('Periodo de prueba activo.');
    }
}
