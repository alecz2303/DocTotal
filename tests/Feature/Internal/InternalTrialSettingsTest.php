<?php

namespace Tests\Feature\Internal;

use App\Models\AuditEvent;
use App\Models\GlobalSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Commercial\TrialSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class InternalTrialSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_internal_admin_can_view_trial_settings_with_environment_fallback(): void
    {
        config(['doctotal.trial_days' => 6]);
        $admin = $this->internalAdmin();

        $this->actingAs($admin)
            ->get(route('internal.settings.trial'))
            ->assertOk()
            ->assertSee('Periodo de prueba')
            ->assertSee('value="6"', false);
    }

    public function test_internal_admin_can_update_trial_days_and_change_is_audited_globally(): void
    {
        $admin = $this->internalAdmin();

        $this->actingAs($admin)
            ->put(route('internal.settings.trial.update'), [
                'trial_days' => 14,
            ])
            ->assertRedirect(route('internal.settings.trial'))
            ->assertSessionHas('status', 'Duración del periodo de prueba actualizada.');

        $this->assertDatabaseHas('global_settings', [
            'key' => TrialSettings::KEY,
            'value' => '14',
            'updated_by_user_id' => $admin->id,
        ]);

        $event = AuditEvent::query()
            ->withoutGlobalScopes()
            ->where('action', 'internal.settings.trial_days.updated')
            ->first();

        $this->assertNotNull($event);
        $this->assertNull($event->tenant_id);
        $this->assertSame($admin->id, $event->user_id);
        $this->assertSame(14, $event->metadata['new_days']);
    }

    public function test_trial_settings_success_flash_is_rendered_through_sweetalert(): void
    {
        $admin = $this->internalAdmin();

        $this->actingAs($admin)
            ->withSession(['status' => 'Duración del periodo de prueba actualizada.'])
            ->get(route('internal.settings.trial'))
            ->assertOk()
            ->assertSee('window.Swal.fire', false)
            ->assertSee('Configuración actualizada')
            ->assertSee('Duración del periodo de prueba actualizada.')
            ->assertDontSee('border-emerald-200', false);
    }

    public function test_trial_days_validation_rejects_out_of_range_values(): void
    {
        $admin = $this->internalAdmin();

        $this->actingAs($admin)
            ->from(route('internal.settings.trial'))
            ->put(route('internal.settings.trial.update'), [
                'trial_days' => 91,
            ])
            ->assertRedirect(route('internal.settings.trial'))
            ->assertSessionHasErrors('trial_days');

        $this->assertDatabaseMissing('global_settings', [
            'key' => TrialSettings::KEY,
        ]);
    }

    public function test_tenant_user_cannot_access_internal_trial_settings(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Tenant test',
            'slug' => 'tenant-test',
            'status' => 'trial',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => User::ROLE_OWNER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('internal.settings.trial'))
            ->assertForbidden();
    }

    public function test_register_page_displays_persisted_trial_duration(): void
    {
        GlobalSetting::query()->create([
            'key' => TrialSettings::KEY,
            'value' => '12',
        ]);

        $this->get('/register')
            ->assertOk()
            ->assertSee('12 días gratis');
    }

    public function test_new_registration_uses_persisted_trial_duration(): void
    {
        Carbon::setTestNow('2026-09-06 10:00:00');

        GlobalSetting::query()->create([
            'key' => TrialSettings::KEY,
            'value' => '10',
        ]);

        $this->post('/register', [
            'practice_name' => 'Consultorio Nuevo',
            'first_name' => 'María',
            'last_name' => 'Prueba',
            'email' => 'maria@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $tenant = Tenant::query()
            ->withoutGlobalScopes()
            ->where('name', 'Consultorio Nuevo')
            ->firstOrFail();

        $this->assertSame(
            '2026-09-16',
            $tenant->trial_ends_at->toDateString()
        );
    }

    public function test_updating_default_does_not_change_existing_tenant_trial_dates(): void
    {
        $existingEnd = now()->addDays(4)->startOfSecond();
        $tenant = Tenant::query()->create([
            'name' => 'Existing tenant',
            'slug' => 'existing-tenant',
            'status' => 'trial',
            'trial_started_at' => now(),
            'trial_ends_at' => $existingEnd,
        ]);
        $admin = $this->internalAdmin();

        $this->actingAs($admin)
            ->put(route('internal.settings.trial.update'), [
                'trial_days' => 20,
            ])
            ->assertRedirect(route('internal.settings.trial'));

        $this->assertTrue(
            $tenant->fresh()->trial_ends_at->equalTo($existingEnd)
        );
    }

    private function internalAdmin(): User
    {
        return User::factory()->create([
            'tenant_id' => null,
            'role' => User::ROLE_INTERNAL_ADMIN,
            'email_verified_at' => now(),
        ]);
    }
}
