<?php

namespace Tests\Feature\Appointments;

use App\Models\Appointment;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\Schedule;
use App\Models\Specialty;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AppointmentCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_open_create_appointment_page(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $this->actingAs($user)
            ->get(route('appointments.create'))
            ->assertOk()
            ->assertSee('Nueva cita')
            ->assertSee('Buscar paciente')
            ->assertSee('Horario disponible');
    }

    public function test_user_can_search_patient_by_name(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        Patient::create([
            'first_name' => 'Alejandro',
            'last_name' => 'Fedle',
            'second_last_name' => 'Rueda',
        ]);

        Patient::create([
            'first_name' => 'María',
            'last_name' => 'López',
        ]);

        Livewire::actingAs($user)
            ->test('pages::appointments.create')
            ->set('patientSearch', 'Alejandro')
            ->assertSee('Alejandro')
            ->assertDontSee('María');
    }

    public function test_user_can_select_existing_patient(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Alejandro',
            'last_name' => 'Fedle',
            'second_last_name' => 'Rueda',
        ]);

        Livewire::actingAs($user)
            ->test('pages::appointments.create')
            ->call('selectPatient', $patient->id)
            ->assertSet('patientId', $patient->id)
            ->assertSet(
                'patientSearch',
                'Alejandro Fedle Rueda'
            )
            ->assertSee('Alejandro')
            ->assertSee('Fedle');
    }

    public function test_user_can_clear_selected_patient(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Alejandro',
            'last_name' => 'Fedle',
        ]);

        Livewire::actingAs($user)
            ->test('pages::appointments.create')
            ->call('selectPatient', $patient->id)
            ->call('clearPatient')
            ->assertSet('patientId', null)
            ->assertSet('patientSearch', '');
    }

    public function test_user_can_open_and_close_quick_patient_modal(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::appointments.create')
            ->assertSet('showQuickPatientModal', false)
            ->call('openQuickPatientModal')
            ->assertSet('showQuickPatientModal', true)
            ->assertSee('Crear paciente')
            ->call('closeQuickPatientModal')
            ->assertSet('showQuickPatientModal', false);
    }

    public function test_user_can_create_quick_patient_and_it_is_selected(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $component = Livewire::actingAs($user)
            ->test('pages::appointments.create')
            ->call('openQuickPatientModal')
            ->set('quick_first_name', 'Paciente')
            ->set('quick_last_name', 'Nuevo')
            ->set('quick_second_last_name', 'Agenda')
            ->set('quick_phone', '9611234567')
            ->set('quick_email', 'nuevo@example.com')
            ->set('quick_birth_date', '1990-01-15')
            ->call('createQuickPatient');

        $patient = Patient::query()
            ->where('email', 'nuevo@example.com')
            ->firstOrFail();

        $component
            ->assertSet('showQuickPatientModal', false)
            ->assertSet('patientId', $patient->id)
            ->assertSet(
                'patientSearch',
                'Paciente Nuevo Agenda'
            );

        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'tenant_id' => $tenant->id,
            'first_name' => 'Paciente',
            'last_name' => 'Nuevo',
            'phone' => '9611234567',
            'email' => 'nuevo@example.com',
        ]);
    }

    public function test_quick_patient_requires_first_and_last_name(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::appointments.create')
            ->call('openQuickPatientModal')
            ->call('createQuickPatient')
            ->assertHasErrors([
                'quick_first_name',
                'quick_last_name',
            ]);

        $this->assertDatabaseCount(
            'patients',
            0
        );
    }

    public function test_quick_patient_rejects_invalid_email(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::appointments.create')
            ->call('openQuickPatientModal')
            ->set('quick_first_name', 'Paciente')
            ->set('quick_last_name', 'Nuevo')
            ->set('quick_email', 'correo-invalido')
            ->call('createQuickPatient')
            ->assertHasErrors([
                'quick_email',
            ]);
    }

    public function test_quick_patient_rejects_future_birth_date(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::appointments.create')
            ->call('openQuickPatientModal')
            ->set('quick_first_name', 'Paciente')
            ->set('quick_last_name', 'Nuevo')
            ->set(
                'quick_birth_date',
                now()->addDay()->format('Y-m-d')
            )
            ->call('createQuickPatient')
            ->assertHasErrors([
                'quick_birth_date',
            ]);
    }

    public function test_available_slots_are_displayed_from_doctor_schedule(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $this->createSchedule(
            $doctor,
            dayOfWeek: 1,
            startTime: '09:00',
            endTime: '11:00',
            duration: 30
        );

        Livewire::actingAs($user)
            ->test('pages::appointments.create')
            ->set('date', '2026-08-24')
            ->set('duration', 30)
            ->assertSee('09:00')
            ->assertSee('09:30')
            ->assertSee('10:00')
            ->assertSee('10:30');
    }

    public function test_user_can_create_appointment_with_available_slot(): void
    {
        $this->travelTo(
            \Illuminate\Support\Carbon::parse(
                '2026-08-23 12:00:00'
            )
        );

        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext(
            tenantName: 'Consultorio Appointment Create',
            tenantSlug: 'appointment-create-test',
            email: 'appointment-create@example.com'
        );

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Alejandro',
            'last_name' => 'Fedle',
        ]);

        $this->createSchedule(
            $doctor,
            dayOfWeek: 1,
            startTime: '09:00',
            endTime: '14:00',
            duration: 30
        );

        $component = Livewire::actingAs($user)
            ->test('pages::appointments.create')
            ->call('selectPatient', $patient->id)
            ->set('date', '2026-08-24')
            ->set('duration', 30)
            ->set('time', '10:00')
            ->set('reason', 'Consulta general')
            ->set('notes', 'Primera visita')
            ->call('saveAppointment')
            ->assertHasNoErrors();

        $appointment = Appointment::query()
            ->firstOrFail();

        $this->assertSame(
            $patient->id,
            $appointment->patient_id
        );

        $this->assertSame(
            $doctor->id,
            $appointment->doctor_profile_id
        );

        $this->assertSame(
            'scheduled',
            $appointment->status
        );

        $this->assertSame(
            '2026-08-24 10:00',
            $appointment->starts_at->format('Y-m-d H:i')
        );

        $this->assertSame(
            '2026-08-24 10:30',
            $appointment->ends_at->format('Y-m-d H:i')
        );

        $this->assertSame(
            'Consulta general',
            $appointment->reason
        );

        $this->assertSame(
            'Primera visita',
            $appointment->notes
        );

        $component->assertRedirect(
            route('appointments.show', [
                'uuid' => $appointment->uuid,
            ])
        );
    }

    public function test_appointment_requires_patient(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $this->createSchedule(
            $doctor,
            dayOfWeek: 1,
            startTime: '09:00',
            endTime: '14:00'
        );

        Livewire::actingAs($user)
            ->test('pages::appointments.create')
            ->set('date', '2026-08-24')
            ->set('time', '10:00')
            ->call('saveAppointment')
            ->assertHasErrors([
                'patientId',
            ]);

        $this->assertDatabaseCount(
            'appointments',
            0
        );
    }

    public function test_appointment_requires_available_time(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Test',
        ]);

        $this->createSchedule(
            $doctor,
            dayOfWeek: 1,
            startTime: '09:00',
            endTime: '14:00'
        );

        Livewire::actingAs($user)
            ->test('pages::appointments.create')
            ->call('selectPatient', $patient->id)
            ->set('date', '2026-08-24')
            ->set('time', '')
            ->call('saveAppointment')
            ->assertHasErrors([
                'time',
            ]);

        $this->assertDatabaseCount(
            'appointments',
            0
        );
    }

    public function test_user_cannot_create_appointment_outside_schedule(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Test',
        ]);

        $this->createSchedule(
            $doctor,
            dayOfWeek: 1,
            startTime: '09:00',
            endTime: '14:00'
        );

        Livewire::actingAs($user)
            ->test('pages::appointments.create')
            ->call('selectPatient', $patient->id)
            ->set('date', '2026-08-24')
            ->set('time', '08:00')
            ->set('duration', 30)
            ->call('saveAppointment')
            ->assertHasErrors([
                'time',
            ]);

        $this->assertDatabaseCount(
            'appointments',
            0
        );
    }

    public function test_user_cannot_create_overlapping_appointment(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $patientA = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Uno',
        ]);

        $patientB = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Dos',
        ]);

        $this->createSchedule(
            $doctor,
            dayOfWeek: 1,
            startTime: '09:00',
            endTime: '14:00'
        );

        Appointment::create([
            'patient_id' => $patientA->id,
            'doctor_profile_id' => $doctor->id,
            'starts_at' => '2026-08-24 10:00:00',
            'ends_at' => '2026-08-24 10:30:00',
            'status' => 'scheduled',
        ]);

        Livewire::actingAs($user)
            ->test('pages::appointments.create')
            ->call('selectPatient', $patientB->id)
            ->set('date', '2026-08-24')
            ->set('time', '10:00')
            ->set('duration', 30)
            ->call('saveAppointment')
            ->assertHasErrors([
                'time',
            ]);

        $this->assertDatabaseCount(
            'appointments',
            1
        );
    }

    public function test_user_cannot_select_patient_from_another_tenant(): void
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

        [
            $tenantB,
            $userB,
            $doctorB,
        ] = $this->createContext(
            tenantName: 'Tenant B',
            tenantSlug: 'tenant-b',
            email: 'b@example.com'
        );

        app(TenantContext::class)->set($tenantB);

        $patientB = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Tenant B',
        ]);

        app(TenantContext::class)->set($tenantA);

        $this->expectException(
            \Illuminate\Database\Eloquent\ModelNotFoundException::class
        );

        Livewire::actingAs($userA)
            ->test('pages::appointments.create')
            ->call(
                'selectPatient',
                $patientB->id
            );
    }

    public function test_quick_patient_is_created_in_current_tenant(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::appointments.create')
            ->call('openQuickPatientModal')
            ->set('quick_first_name', 'Nuevo')
            ->set('quick_last_name', 'Paciente')
            ->call('createQuickPatient');

        $patient = Patient::query()
            ->where('first_name', 'Nuevo')
            ->firstOrFail();

        $this->assertSame(
            $tenant->id,
            $patient->tenant_id
        );
    }

    public function test_user_cannot_open_appointment_from_another_tenant(): void
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

        [
            $tenantB,
            $userB,
            $doctorB,
        ] = $this->createContext(
            tenantName: 'Tenant B',
            tenantSlug: 'tenant-b',
            email: 'b@example.com'
        );

        app(TenantContext::class)->set($tenantB);

        $patientB = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'B',
        ]);

        $appointmentB = Appointment::create([
            'patient_id' => $patientB->id,
            'doctor_profile_id' => $doctorB->id,
            'starts_at' => '2026-08-24 10:00:00',
            'ends_at' => '2026-08-24 10:30:00',
            'status' => 'scheduled',
        ]);

        app(TenantContext::class)->set($tenantA);

        $this->actingAs($userA)
            ->get(
                route('appointments.show', [
                    'uuid' => $appointmentB->uuid,
                ])
            )
            ->assertNotFound();
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
        ]);

        return [
            $tenant,
            $user,
            $doctor,
        ];
    }

    private function createSchedule(
        DoctorProfile $doctor,
        int $dayOfWeek,
        string $startTime,
        string $endTime,
        int $duration = 30,
    ): Schedule {
        return Schedule::create([
            'doctor_profile_id' => $doctor->id,
            'day_of_week' => $dayOfWeek,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'appointment_duration' => $duration,
            'buffer_before' => 0,
            'buffer_after' => 0,
            'active' => true,
        ]);
    }
}
