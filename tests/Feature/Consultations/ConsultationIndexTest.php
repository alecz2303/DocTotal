<?php

namespace Tests\Feature\Consultations;

use App\Models\Consultation;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\Specialty;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ConsultationIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_consultations_index(): void
    {
        [$tenant, $user] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $this->actingAs($user)
            ->get(route('consultations.index'))
            ->assertOk()
            ->assertSee('Consultas')
            ->assertSee('Historial general de consultas médicas.');
    }

    public function test_index_displays_consultations(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $patient = $this->createPatient(
            'Alejandro',
            'Fedle',
            'Rueda'
        );

        $this->createConsultation(
            $doctor,
            $patient,
            '2026-08-23 10:00:00',
            'Dolor de cabeza'
        );

        $this->actingAs($user)
            ->get(route('consultations.index'))
            ->assertOk()
            ->assertSee('Alejandro')
            ->assertSee('Fedle')
            ->assertSee('Dolor de cabeza')
            ->assertSee('Completada');
    }

    public function test_user_can_search_consultations_by_patient_first_name(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $alejandro = $this->createPatient(
            'Alejandro',
            'Fedle',
            'Rueda'
        );

        $maria = $this->createPatient(
            'María',
            'López',
            'García'
        );

        $this->createConsultation(
            $doctor,
            $alejandro,
            '2026-08-23 10:00:00',
            'Consulta Alejandro'
        );

        $this->createConsultation(
            $doctor,
            $maria,
            '2026-08-23 11:00:00',
            'Consulta María'
        );

        Livewire::actingAs($user)
            ->test('pages::consultations.index')
            ->set('search', 'Alejandro')
            ->assertSee('Consulta Alejandro')
            ->assertDontSee('Consulta María');
    }

    public function test_user_can_search_consultations_by_patient_last_name(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $patientA = $this->createPatient(
            'Alejandro',
            'Fedle',
            'Rueda'
        );

        $patientB = $this->createPatient(
            'Pedro',
            'Martínez',
            'Pérez'
        );

        $this->createConsultation(
            $doctor,
            $patientA,
            '2026-08-23 10:00:00',
            'Consulta Fedle'
        );

        $this->createConsultation(
            $doctor,
            $patientB,
            '2026-08-23 11:00:00',
            'Consulta Martínez'
        );

        Livewire::actingAs($user)
            ->test('pages::consultations.index')
            ->set('search', 'Fedle')
            ->assertSee('Consulta Fedle')
            ->assertDontSee('Consulta Martínez');
    }

    public function test_user_can_search_consultations_by_second_last_name(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $patientA = $this->createPatient(
            'Alejandro',
            'Fedle',
            'Rueda'
        );

        $patientB = $this->createPatient(
            'Pedro',
            'Martínez',
            'Pérez'
        );

        $this->createConsultation(
            $doctor,
            $patientA,
            '2026-08-23 10:00:00',
            'Consulta Rueda'
        );

        $this->createConsultation(
            $doctor,
            $patientB,
            '2026-08-23 11:00:00',
            'Consulta Pérez'
        );

        Livewire::actingAs($user)
            ->test('pages::consultations.index')
            ->set('search', 'Rueda')
            ->assertSee('Consulta Rueda')
            ->assertDontSee('Consulta Pérez');
    }

    public function test_user_can_filter_consultations_from_date(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $patient = $this->createPatient();

        $this->createConsultation(
            $doctor,
            $patient,
            '2026-08-10 10:00:00',
            'Consulta antigua'
        );

        $this->createConsultation(
            $doctor,
            $patient,
            '2026-08-20 10:00:00',
            'Consulta reciente'
        );

        Livewire::actingAs($user)
            ->test('pages::consultations.index')
            ->set('dateFrom', '2026-08-15')
            ->assertSee('Consulta reciente')
            ->assertDontSee('Consulta antigua');
    }

    public function test_user_can_filter_consultations_until_date(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $patient = $this->createPatient();

        $this->createConsultation(
            $doctor,
            $patient,
            '2026-08-10 10:00:00',
            'Consulta anterior'
        );

        $this->createConsultation(
            $doctor,
            $patient,
            '2026-08-20 10:00:00',
            'Consulta posterior'
        );

        Livewire::actingAs($user)
            ->test('pages::consultations.index')
            ->set('dateTo', '2026-08-15')
            ->assertSee('Consulta anterior')
            ->assertDontSee('Consulta posterior');
    }

    public function test_user_can_filter_consultations_by_date_range(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $patient = $this->createPatient();

        $this->createConsultation(
            $doctor,
            $patient,
            '2026-08-05 10:00:00',
            'Fuera antes'
        );

        $this->createConsultation(
            $doctor,
            $patient,
            '2026-08-15 10:00:00',
            'Dentro del rango'
        );

        $this->createConsultation(
            $doctor,
            $patient,
            '2026-08-25 10:00:00',
            'Fuera después'
        );

        Livewire::actingAs($user)
            ->test('pages::consultations.index')
            ->set('dateFrom', '2026-08-10')
            ->set('dateTo', '2026-08-20')
            ->assertSee('Dentro del rango')
            ->assertDontSee('Fuera antes')
            ->assertDontSee('Fuera después');
    }

    public function test_user_can_filter_consultations_by_status(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $patient = $this->createPatient();

        $completed = $this->createConsultation(
            $doctor,
            $patient,
            '2026-08-23 10:00:00',
            'Consulta completada'
        );

        $cancelled = $this->createConsultation(
            $doctor,
            $patient,
            '2026-08-23 11:00:00',
            'Consulta cancelada'
        );

        $cancelled->update([
            'status' => 'cancelled',
        ]);

        Livewire::actingAs($user)
            ->test('pages::consultations.index')
            ->set('status', 'completed')
            ->assertSee('Consulta completada')
            ->assertDontSee('Consulta cancelada');

        Livewire::actingAs($user)
            ->test('pages::consultations.index')
            ->set('status', 'cancelled')
            ->assertSee('Consulta cancelada')
            ->assertDontSee('Consulta completada');
    }

    public function test_filters_can_be_combined(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $alejandro = $this->createPatient(
            'Alejandro',
            'Fedle',
            'Rueda'
        );

        $maria = $this->createPatient(
            'María',
            'López',
            'García'
        );

        $matching = $this->createConsultation(
            $doctor,
            $alejandro,
            '2026-08-15 10:00:00',
            'Debe aparecer'
        );

        $cancelled = $this->createConsultation(
            $doctor,
            $alejandro,
            '2026-08-16 10:00:00',
            'Estado incorrecto'
        );

        $cancelled->update([
            'status' => 'cancelled',
        ]);

        $this->createConsultation(
            $doctor,
            $alejandro,
            '2026-07-01 10:00:00',
            'Fecha incorrecta'
        );

        $this->createConsultation(
            $doctor,
            $maria,
            '2026-08-15 10:00:00',
            'Paciente incorrecto'
        );

        Livewire::actingAs($user)
            ->test('pages::consultations.index')
            ->set('search', 'Alejandro')
            ->set('dateFrom', '2026-08-01')
            ->set('dateTo', '2026-08-31')
            ->set('status', 'completed')
            ->assertSee('Debe aparecer')
            ->assertDontSee('Estado incorrecto')
            ->assertDontSee('Fecha incorrecta')
            ->assertDontSee('Paciente incorrecto');
    }

    public function test_user_can_clear_consultation_filters(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $patient = $this->createPatient();

        $this->createConsultation(
            $doctor,
            $patient,
            '2026-08-23 10:00:00',
            'Consulta visible'
        );

        Livewire::actingAs($user)
            ->test('pages::consultations.index')
            ->set('search', 'Paciente')
            ->set('dateFrom', '2026-08-01')
            ->set('dateTo', '2026-08-31')
            ->set('status', 'completed')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('dateFrom', '')
            ->assertSet('dateTo', '')
            ->assertSet('status', '')
            ->assertSee('Consulta visible');
    }

    public function test_consultations_are_ordered_by_most_recent_first(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $patient = $this->createPatient();

        $this->createConsultation(
            $doctor,
            $patient,
            '2026-08-10 10:00:00',
            'Consulta antigua'
        );

        $this->createConsultation(
            $doctor,
            $patient,
            '2026-08-20 10:00:00',
            'Consulta reciente'
        );

        Livewire::actingAs($user)
            ->test('pages::consultations.index')
            ->assertSeeInOrder([
                'Consulta reciente',
                'Consulta antigua',
            ]);
    }

    public function test_index_does_not_show_consultations_from_another_tenant(): void
    {
        [
            $tenantA,
            $userA,
            $doctorA,
        ] = $this->createContext(
            tenantName: 'Tenant A',
            tenantSlug: 'tenant-a',
            email: 'a@example.com'
        );

        app(TenantContext::class)->set($tenantA);

        $patientA = $this->createPatient(
            'Paciente',
            'TenantA',
            'Test'
        );

        $this->createConsultation(
            $doctorA,
            $patientA,
            '2026-08-23 10:00:00',
            'Consulta Tenant A'
        );

        [
            $tenantB,,
            $doctorB,
        ] = $this->createContext(
            tenantName: 'Tenant B',
            tenantSlug: 'tenant-b',
            email: 'b@example.com'
        );

        app(TenantContext::class)->set($tenantB);

        $patientB = $this->createPatient(
            'Paciente',
            'TenantB',
            'Test'
        );

        $this->createConsultation(
            $doctorB,
            $patientB,
            '2026-08-23 11:00:00',
            'Consulta Tenant B'
        );

        app(TenantContext::class)->set($tenantA);

        Livewire::actingAs($userA)
            ->test('pages::consultations.index')
            ->assertSee('Consulta Tenant A')
            ->assertDontSee('Consulta Tenant B');
    }

    private function createContext(
        string $tenantName = 'Consultorio Test',
        string $tenantSlug = 'consultorio-test',
        string $email = 'doctor@example.com',
    ): array {
        $tenant = Tenant::create([
            'name' => $tenantName,
            'slug' => $tenantSlug,
            'onboarding_completed_at' => now(),
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Dr. Test',
            'email' => $email,
            'password' => 'password123',
            'role' => 'owner',
        ]);

        app(TenantContext::class)->set($tenant);

        $specialty = Specialty::firstOrCreate(
            [
                'slug' => 'medicina-general',
            ],
            [
                'name' => 'Medicina General',
            ]
        );

        $doctor = DoctorProfile::create([
            'user_id' => $user->id,
            'specialty_id' => $specialty->id,
            'first_name' => 'Doctor',
            'last_name' => 'Test',
            'professional_license' => '12345678',
        ]);

        return [
            $tenant,
            $user,
            $doctor,
        ];
    }

    private function createPatient(
        string $firstName = 'Paciente',
        string $lastName = 'Prueba',
        string $secondLastName = 'Test',
    ): Patient {
        return Patient::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'second_last_name' => $secondLastName,
            'birth_date' => '1990-01-15',
        ]);
    }

    private function createConsultation(
        DoctorProfile $doctor,
        Patient $patient,
        string $consultationAt,
        string $reason,
    ): Consultation {
        return Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => $consultationAt,
            'reason' => $reason,
            'status' => 'completed',
        ]);
    }

    public function test_index_has_link_to_consultation_detail(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $patient = $this->createPatient();

        $consultation = $this->createConsultation(
            $doctor,
            $patient,
            '2026-08-23 10:00:00',
            'Consulta para detalle'
        );

        Livewire::actingAs($user)
            ->test('pages::consultations.index')
            ->assertSee(
                route('consultations.show', [
                    'uuid' => $consultation->uuid,
                ]),
                false
            );
    }

    public function test_index_has_link_to_create_prescription(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $patient = $this->createPatient();

        $consultation = $this->createConsultation(
            $doctor,
            $patient,
            '2026-08-23 10:00:00',
            'Consulta para receta'
        );

        Livewire::actingAs($user)
            ->test('pages::consultations.index')
            ->assertSee(
                route('prescriptions.create', [
                    'uuid' => $consultation->uuid,
                ]),
                false
            );
    }

    public function test_index_has_link_to_patient_record(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $patient = $this->createPatient();

        $this->createConsultation(
            $doctor,
            $patient,
            '2026-08-23 10:00:00',
            'Consulta para expediente'
        );

        Livewire::actingAs($user)
            ->test('pages::consultations.index')
            ->assertSee(
                route('patients.show', [
                    'uuid' => $patient->uuid,
                ]),
                false
            );
    }
}
