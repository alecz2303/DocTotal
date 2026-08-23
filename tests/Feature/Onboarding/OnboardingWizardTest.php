<?php

namespace Tests\Feature\Onboarding;

use App\Models\DoctorProfile;
use App\Models\PracticeProfile;
use App\Models\Schedule;
use App\Models\Specialty;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class OnboardingWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_incomplete_onboarding_can_see_wizard(): void
    {
        [$tenant, $user] = $this->createUserWithProfile();

        $this->actingAs($user)
            ->get('/onboarding')
            ->assertOk()
            ->assertSee('Configura tu consultorio');
    }

    public function test_wizard_loads_existing_doctor_and_practice_data(): void
    {
        [$tenant, $user, $doctor, $practice] = $this->createUserWithProfile();

        $specialty = Specialty::create([
            'name' => 'Medicina General',
            'slug' => 'medicina-general',
            'active' => true,
        ]);

        $doctor->update([
            'specialty_id' => $specialty->id,
            'professional_license' => 'ABC123',
            'phone' => '9611111111',
            'whatsapp' => '9612222222',
        ]);

        $practice->update([
            'public_name' => 'Consultorio Central',
            'phone' => '9613333333',
            'city' => 'Tuxtla Gutiérrez',
            'state' => 'Chiapas',
        ]);

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::onboarding.wizard')
            ->assertSet('first_name', 'Juan')
            ->assertSet('last_name', 'Pérez')
            ->assertSet('specialty_id', $specialty->id)
            ->assertSet('professional_license', 'ABC123')
            ->assertSet('doctor_phone', '9611111111')
            ->assertSet('doctor_whatsapp', '9612222222')
            ->assertSet('public_name', 'Consultorio Central')
            ->assertSet('practice_phone', '9613333333')
            ->assertSet('city', 'Tuxtla Gutiérrez')
            ->assertSet('state', 'Chiapas');
    }

    public function test_step_one_requires_professional_data(): void
    {
        [$tenant, $user] = $this->createUserWithProfile();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::onboarding.wizard')
            ->set('first_name', '')
            ->set('last_name', '')
            ->set('specialty_id', null)
            ->call('nextStep')
            ->assertHasErrors([
                'first_name',
                'last_name',
                'specialty_id',
            ])
            ->assertSet('step', 1);
    }

    public function test_user_can_advance_through_wizard_steps(): void
    {
        [$tenant, $user] = $this->createUserWithProfile();

        $specialty = Specialty::create([
            'name' => 'Medicina General',
            'slug' => 'medicina-general',
            'active' => true,
        ]);

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::onboarding.wizard')

            ->set('specialty_id', $specialty->id)
            ->call('nextStep')
            ->assertSet('step', 2)

            ->set('public_name', 'Consultorio Test')
            ->call('nextStep')
            ->assertSet('step', 3)

            ->call('nextStep')
            ->assertSet('step', 4);
    }

    public function test_finish_updates_doctor_practice_and_creates_schedules(): void
    {
        [$tenant, $user, $doctor, $practice] = $this->createUserWithProfile();

        $specialty = Specialty::create([
            'name' => 'Medicina Interna',
            'slug' => 'medicina-interna',
            'active' => true,
        ]);

        app(TenantContext::class)->set($tenant);

        config([
            'services.sepomex.base_url' => 'https://sepomex.test/api/v1',
        ]);

        Cache::flush();

        Http::fake([
            'https://sepomex.test/api/v1/zip_codes*' => Http::response([
                [
                    'd_codigo' => '29025',
                    'd_asenta' => 'Centenario Tuchtlán',
                    'D_mnpio' => 'Tuxtla Gutiérrez',
                    'd_estado' => 'Chiapas',
                    'd_ciudad' => 'Tuxtla Gutiérrez',
                ],
                [
                    'd_codigo' => '29025',
                    'd_asenta' => 'Las Palmas',
                    'D_mnpio' => 'Tuxtla Gutiérrez',
                    'd_estado' => 'Chiapas',
                    'd_ciudad' => 'Tuxtla Gutiérrez',
                ],
            ], 200),
        ]);

        Livewire::actingAs($user)
            ->test('pages::onboarding.wizard')

            ->set('first_name', 'Alejandro')
            ->set('last_name', 'Rueda')
            ->set('specialty_id', $specialty->id)
            ->set('professional_license', 'CED12345')
            ->set('doctor_phone', '9611120913')
            ->set('doctor_whatsapp', '9611120913')

            ->set('public_name', 'Consultorio Alejandro Rueda')
            ->set('practice_phone', '9611120913')
            ->set('practice_whatsapp', '9611120913')

            /*
            * Primero ponemos el CP.
            *
            * Esto dispara updatedPostalCode() y carga
            * estado, ciudad y colonias desde SEPOMEX.
            */
            ->set('postal_code', '29025')

            ->assertSet('state', 'Chiapas')
            ->assertSet('city', 'Tuxtla Gutiérrez')
            ->assertSet('neighborhoodOptions', [
                'Centenario Tuchtlán',
                'Las Palmas',
            ])

            /*
            * Después seleccionamos una colonia
            * proveniente de las opciones encontradas.
            */
            ->set('neighborhood', 'Centenario Tuchtlán')

            ->set('address_line_1', 'Ixtacomitán 326')
            ->set('address_line_2', '')

            ->set('appointment_duration', 30)

            ->set('days.1.enabled', true)
            ->set('days.1.start_time', '09:00')
            ->set('days.1.end_time', '18:00')

            ->set('days.2.enabled', true)
            ->set('days.2.start_time', '10:00')
            ->set('days.2.end_time', '17:00')

            ->set('days.3.enabled', false)
            ->set('days.4.enabled', false)
            ->set('days.5.enabled', false)
            ->set('days.6.enabled', false)
            ->set('days.0.enabled', false)

            ->call('finish')
            ->assertRedirect('/dashboard');

        $doctor->refresh();
        $practice->refresh();
        $tenant->refresh();

        // Médico
        $this->assertSame('Alejandro', $doctor->first_name);
        $this->assertSame('Rueda', $doctor->last_name);
        $this->assertSame($specialty->id, $doctor->specialty_id);
        $this->assertSame('CED12345', $doctor->professional_license);
        $this->assertSame('9611120913', $doctor->phone);
        $this->assertSame('9611120913', $doctor->whatsapp);

        // Consultorio
        $this->assertSame(
            'Consultorio Alejandro Rueda',
            $practice->public_name
        );

        $this->assertSame('9611120913', $practice->phone);
        $this->assertSame('9611120913', $practice->whatsapp);

        // Dirección
        $this->assertSame(
            'Ixtacomitán 326',
            $practice->address_line_1
        );

        $this->assertNull($practice->address_line_2);

        $this->assertSame(
            'Centenario Tuchtlán',
            $practice->neighborhood
        );

        $this->assertSame(
            'Tuxtla Gutiérrez',
            $practice->city
        );

        $this->assertSame(
            'Chiapas',
            $practice->state
        );

        $this->assertSame(
            '29025',
            $practice->postal_code
        );

        // Onboarding
        $this->assertNotNull(
            $tenant->onboarding_completed_at
        );

        // Horarios
        $this->assertDatabaseCount('schedules', 2);

        $this->assertDatabaseHas('schedules', [
            'tenant_id' => $tenant->id,
            'doctor_profile_id' => $doctor->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '18:00',
            'appointment_duration' => 30,
            'active' => true,
        ]);

        $this->assertDatabaseHas('schedules', [
            'tenant_id' => $tenant->id,
            'doctor_profile_id' => $doctor->id,
            'day_of_week' => 2,
            'start_time' => '10:00',
            'end_time' => '17:00',
            'appointment_duration' => 30,
            'active' => true,
        ]);

        Http::assertSentCount(1);
    }

    public function test_completed_onboarding_user_is_redirected_to_dashboard(): void
    {
        [$tenant, $user] = $this->createUserWithProfile();

        $tenant->update([
            'onboarding_completed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/onboarding')
            ->assertRedirect('/dashboard');
    }

    private function createUserWithProfile(): array
    {
        $tenant = Tenant::create([
            'name' => 'Consultorio Test',
            'slug' => 'consultorio-test',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'password' => 'password123',
            'role' => 'owner',
        ]);

        $doctor = DoctorProfile::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
        ]);

        $practice = PracticeProfile::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'public_name' => 'Consultorio Test',
        ]);

        return [
            $tenant,
            $user,
            $doctor,
            $practice,
        ];
    }

    public function test_postal_code_autocompletes_address_data(): void
    {
        [$tenant, $user] = $this->createUserWithProfile();

        app(TenantContext::class)->set($tenant);

        config([
            'services.sepomex.base_url' => 'https://sepomex.test/api/v1',
        ]);

        Cache::flush();

        Http::fake([
            'https://sepomex.test/api/v1/zip_codes*' => Http::response([
                [
                    'd_codigo' => '29025',
                    'd_asenta' => 'Centenario Tuchtlán',
                    'D_mnpio' => 'Tuxtla Gutiérrez',
                    'd_estado' => 'Chiapas',
                    'd_ciudad' => 'Tuxtla Gutiérrez',
                ],
                [
                    'd_codigo' => '29025',
                    'd_asenta' => 'Las Palmas',
                    'D_mnpio' => 'Tuxtla Gutiérrez',
                    'd_estado' => 'Chiapas',
                    'd_ciudad' => 'Tuxtla Gutiérrez',
                ],
            ], 200),
        ]);

        Livewire::actingAs($user)
            ->test('pages::onboarding.wizard')
            ->set('postal_code', '29025')
            ->assertSet('state', 'Chiapas')
            ->assertSet('city', 'Tuxtla Gutiérrez')
            ->assertSet('neighborhoodOptions', [
                'Centenario Tuchtlán',
                'Las Palmas',
            ])
            ->assertSet('postalCodeError', null);

        Http::assertSentCount(1);
    }

    public function test_postal_code_failure_does_not_block_manual_address_capture(): void
    {
        [$tenant, $user] = $this->createUserWithProfile();

        app(TenantContext::class)->set($tenant);

        config([
            'services.sepomex.base_url' => 'https://sepomex.test/api/v1',
        ]);

        Cache::flush();

        Http::fake([
            'https://sepomex.test/api/v1/zip_codes*' => Http::response([], 500),
        ]);

        Livewire::actingAs($user)
            ->test('pages::onboarding.wizard')
            ->set('postal_code', '29025')
            ->assertSet(
                'postalCodeError',
                'No fue posible consultar el código postal.'
            )
            ->assertSet('neighborhoodOptions', [])
            ->assertSet('postalCodeLoading', false);
    }
}
