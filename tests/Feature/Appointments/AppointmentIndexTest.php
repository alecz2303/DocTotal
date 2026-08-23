<?php

namespace Tests\Feature\Appointments;

use App\Models\Appointment;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\Schedule;
use App\Models\ScheduleException;
use App\Models\Specialty;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class AppointmentIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_open_appointments_index(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $this->actingAs($user)
            ->get(route('appointments.index'))
            ->assertOk()
            ->assertSee('Agenda')
            ->assertSee('Mes')
            ->assertSee('Semana')
            ->assertSee('Día');
    }

    public function test_month_view_is_default(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::appointments.index')
            ->assertSet('viewMode', 'month');
    }

    public function test_user_can_switch_between_month_week_and_day_views(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::appointments.index')
            ->call('setViewMode', 'week')
            ->assertSet('viewMode', 'week')
            ->call('setViewMode', 'day')
            ->assertSet('viewMode', 'day')
            ->call('setViewMode', 'month')
            ->assertSet('viewMode', 'month');
    }

    public function test_invalid_view_mode_is_ignored(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::appointments.index')
            ->assertSet('viewMode', 'month')
            ->call('setViewMode', 'invalid')
            ->assertSet('viewMode', 'month');
    }

    public function test_selecting_day_changes_to_day_view(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::appointments.index')
            ->call('selectDay', '2026-08-24')
            ->assertSet('date', '2026-08-24')
            ->assertSet('viewMode', 'day');
    }

    public function test_previous_and_next_period_work_in_month_view(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-24 10:00:00')
        );

        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::appointments.index')
            ->set('date', '2026-08-24')
            ->set('viewMode', 'month')
            ->call('previousPeriod')
            ->assertSet('date', '2026-07-24')
            ->call('nextPeriod')
            ->assertSet('date', '2026-08-24');
    }

    public function test_previous_and_next_period_work_in_week_view(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::appointments.index')
            ->set('date', '2026-08-24')
            ->set('viewMode', 'week')
            ->call('previousPeriod')
            ->assertSet('date', '2026-08-17')
            ->call('nextPeriod')
            ->assertSet('date', '2026-08-24');
    }

    public function test_previous_and_next_period_work_in_day_view(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::appointments.index')
            ->set('date', '2026-08-24')
            ->set('viewMode', 'day')
            ->call('previousPeriod')
            ->assertSet('date', '2026-08-23')
            ->call('nextPeriod')
            ->assertSet('date', '2026-08-24');
    }

    public function test_today_action_returns_to_current_date(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-24 10:00:00')
        );

        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::appointments.index')
            ->set('date', '2026-09-15')
            ->call('today')
            ->assertSet('date', '2026-08-24');
    }

    public function test_month_view_displays_appointments(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $patient = $this->createPatient(
            'Alejandro',
            'Fedle'
        );

        $this->createAppointment(
            $doctor,
            $patient,
            '2026-08-24 10:00:00',
            '2026-08-24 10:30:00',
            'Consulta mensual'
        );

        Livewire::actingAs($user)
            ->test('pages::appointments.index')
            ->set('date', '2026-08-24')
            ->set('viewMode', 'month')
            ->assertSee('Alejandro')
            ->assertSee('10:00');
    }

    public function test_week_view_displays_appointments(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $patient = $this->createPatient(
            'María',
            'López'
        );

        $this->createAppointment(
            $doctor,
            $patient,
            '2026-08-26 11:00:00',
            '2026-08-26 11:30:00',
            'Consulta semanal'
        );

        Livewire::actingAs($user)
            ->test('pages::appointments.index')
            ->set('date', '2026-08-24')
            ->set('viewMode', 'week')
            ->assertSee('María')
            ->assertSee('11:00')
            ->assertSee('Consulta semanal');
    }

    public function test_day_view_displays_appointments(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $patient = $this->createPatient(
            'Pedro',
            'Martínez'
        );

        $this->createAppointment(
            $doctor,
            $patient,
            '2026-08-24 12:00:00',
            '2026-08-24 12:30:00',
            'Consulta diaria'
        );

        Livewire::actingAs($user)
            ->test('pages::appointments.index')
            ->set('date', '2026-08-24')
            ->set('viewMode', 'day')
            ->assertSee('Pedro')
            ->assertSee('Consulta diaria')
            ->assertSee('12:00');
    }

    public function test_user_can_search_appointments_by_patient_name(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $patientA = $this->createPatient(
            'Alejandro',
            'Fedle'
        );

        $patientB = $this->createPatient(
            'María',
            'López'
        );

        $this->createAppointment(
            $doctor,
            $patientA,
            '2026-08-24 10:00:00',
            '2026-08-24 10:30:00',
            'Consulta Alejandro'
        );

        $this->createAppointment(
            $doctor,
            $patientB,
            '2026-08-24 11:00:00',
            '2026-08-24 11:30:00',
            'Consulta María'
        );

        Livewire::actingAs($user)
            ->test('pages::appointments.index')
            ->set('date', '2026-08-24')
            ->set('viewMode', 'day')
            ->set('search', 'Alejandro')
            ->assertSee('Consulta Alejandro')
            ->assertDontSee('Consulta María');
    }

    public function test_user_can_filter_appointments_by_status(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $patient = $this->createPatient();

        $scheduled = $this->createAppointment(
            $doctor,
            $patient,
            '2026-08-24 10:00:00',
            '2026-08-24 10:30:00',
            'Cita programada'
        );

        $cancelled = $this->createAppointment(
            $doctor,
            $patient,
            '2026-08-24 11:00:00',
            '2026-08-24 11:30:00',
            'Cita cancelada'
        );

        $cancelled->update([
            'status' => 'cancelled',
        ]);

        Livewire::actingAs($user)
            ->test('pages::appointments.index')
            ->set('date', '2026-08-24')
            ->set('viewMode', 'day')
            ->set('status', 'scheduled')
            ->assertSee('Cita programada')
            ->assertDontSee('Cita cancelada');

        Livewire::actingAs($user)
            ->test('pages::appointments.index')
            ->set('date', '2026-08-24')
            ->set('viewMode', 'day')
            ->set('status', 'cancelled')
            ->assertSee('Cita cancelada')
            ->assertDontSee('Cita programada');
    }

    public function test_user_can_clear_filters(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::appointments.index')
            ->set('search', 'Alejandro')
            ->set('status', 'scheduled')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('status', '');
    }

    public function test_day_without_schedule_is_displayed_as_day_off(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        /*
         * Solo lunes.
         */
        $this->createSchedule(
            $doctor,
            dayOfWeek: 1,
            startTime: '09:00',
            endTime: '14:00'
        );

        Livewire::actingAs($user)
            ->test('pages::appointments.index')
            ->set('date', '2026-08-25')
            ->set('viewMode', 'day')
            ->assertSee('Día libre')
            ->assertSee('No hay atención este día.');
    }

    public function test_full_day_block_displays_exception_reason(): void
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

        ScheduleException::create([
            'doctor_profile_id' => $doctor->id,
            'date' => '2026-08-24',
            'type' => 'blocked',
            'start_time' => null,
            'end_time' => null,
            'reason' => 'Vacaciones',
        ]);

        Livewire::actingAs($user)
            ->test('pages::appointments.index')
            ->set('date', '2026-08-24')
            ->set('viewMode', 'day')
            ->assertSee('Vacaciones')
            ->assertSee('Día bloqueado');
    }

    public function test_partial_block_is_displayed_in_day_view(): void
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

        ScheduleException::create([
            'doctor_profile_id' => $doctor->id,
            'date' => '2026-08-24',
            'type' => 'blocked',
            'start_time' => '11:00',
            'end_time' => '12:00',
            'reason' => 'Compromiso personal',
        ]);

        Livewire::actingAs($user)
            ->test('pages::appointments.index')
            ->set('date', '2026-08-24')
            ->set('viewMode', 'day')
            ->assertSee('Horario bloqueado')
            ->assertSee('11:00')
            ->assertSee('12:00')
            ->assertSee('Compromiso personal');
    }

    public function test_extraordinary_availability_marks_day_as_working_day(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        ScheduleException::create([
            'doctor_profile_id' => $doctor->id,
            'date' => '2026-08-29',
            'type' => 'available',
            'start_time' => '09:00',
            'end_time' => '12:00',
            'reason' => 'Horario especial',
        ]);

        Livewire::actingAs($user)
            ->test('pages::appointments.index')
            ->set('date', '2026-08-29')
            ->set('viewMode', 'day')
            ->assertSee('Día de atención')
            ->assertSee('Horario extraordinario')
            ->assertSee('Horario especial');
    }

    public function test_day_view_orders_appointments_chronologically(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $patient = $this->createPatient();

        $this->createAppointment(
            $doctor,
            $patient,
            '2026-08-24 12:00:00',
            '2026-08-24 12:30:00',
            'Consulta tarde'
        );

        $this->createAppointment(
            $doctor,
            $patient,
            '2026-08-24 09:00:00',
            '2026-08-24 09:30:00',
            'Consulta temprano'
        );

        Livewire::actingAs($user)
            ->test('pages::appointments.index')
            ->set('date', '2026-08-24')
            ->set('viewMode', 'day')
            ->assertSeeInOrder([
                'Consulta temprano',
                'Consulta tarde',
            ]);
    }

    public function test_appointment_index_is_isolated_by_tenant(): void
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
            'Tenant A'
        );

        $this->createAppointment(
            $doctorA,
            $patientA,
            '2026-08-24 10:00:00',
            '2026-08-24 10:30:00',
            'Cita Tenant A'
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

        $patientB = $this->createPatient(
            'Paciente',
            'Tenant B'
        );

        $this->createAppointment(
            $doctorB,
            $patientB,
            '2026-08-24 11:00:00',
            '2026-08-24 11:30:00',
            'Cita Tenant B'
        );

        app(TenantContext::class)->set($tenantA);

        Livewire::actingAs($userA)
            ->test('pages::appointments.index')
            ->set('date', '2026-08-24')
            ->set('viewMode', 'day')
            ->assertSee('Cita Tenant A')
            ->assertDontSee('Cita Tenant B');
    }

    public function test_index_has_links_to_appointment_and_patient(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $patient = $this->createPatient();

        $appointment = $this->createAppointment(
            $doctor,
            $patient,
            '2026-08-24 10:00:00',
            '2026-08-24 10:30:00',
            'Consulta'
        );

        Livewire::actingAs($user)
            ->test('pages::appointments.index')
            ->set('date', '2026-08-24')
            ->set('viewMode', 'day')
            ->assertSee(
                route('appointments.show', [
                    'uuid' => $appointment->uuid,
                ]),
                false
            )
            ->assertSee(
                route('patients.show', [
                    'uuid' => $patient->uuid,
                ]),
                false
            );
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

    private function createPatient(
        string $firstName = 'Paciente',
        string $lastName = 'Test',
    ): Patient {
        return Patient::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'birth_date' => '1990-01-15',
        ]);
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

    private function createAppointment(
        DoctorProfile $doctor,
        Patient $patient,
        string $startsAt,
        string $endsAt,
        string $reason,
    ): Appointment {
        return Appointment::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => 'scheduled',
            'reason' => $reason,
        ]);
    }
}
