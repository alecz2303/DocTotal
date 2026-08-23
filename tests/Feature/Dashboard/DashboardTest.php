<?php

namespace Tests\Feature\Dashboard;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Schedule;
use App\Models\ScheduleException;
use App\Models\Specialty;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_dashboard(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Bienvenido')
            ->assertSee('Citas de hoy')
            ->assertSee('Pacientes')
            ->assertSee('Próxima cita');
    }

    public function test_dashboard_displays_patient_count(): void
    {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Uno',
        ]);

        Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Dos',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('2');
    }

    public function test_dashboard_displays_today_appointment_count(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-24 08:00:00')
        );

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
            '2026-08-24 10:00:00',
            '2026-08-24 10:30:00'
        );

        $this->createAppointment(
            $doctor,
            $patient,
            '2026-08-24 11:00:00',
            '2026-08-24 11:30:00'
        );

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('2');
    }

    public function test_cancelled_appointment_is_not_counted_as_today_active_appointment(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-24 08:00:00')
        );

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
            '2026-08-24 10:00:00',
            '2026-08-24 10:30:00'
        );

        $cancelled = $this->createAppointment(
            $doctor,
            $patient,
            '2026-08-24 11:00:00',
            '2026-08-24 11:30:00'
        );

        $cancelled->update([
            'status' => 'cancelled',
        ]);

        $response = $this->actingAs($user)
            ->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSee('1');
    }

    public function test_dashboard_displays_pending_appointments_count(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-24 08:00:00')
        );

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
            '2026-08-24 09:00:00',
            '2026-08-24 09:30:00',
            'scheduled'
        );

        $this->createAppointment(
            $doctor,
            $patient,
            '2026-08-24 10:00:00',
            '2026-08-24 10:30:00',
            'confirmed'
        );

        $this->createAppointment(
            $doctor,
            $patient,
            '2026-08-24 11:00:00',
            '2026-08-24 11:30:00',
            'checked_in'
        );

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('3');
    }

    public function test_dashboard_displays_next_appointment(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-24 08:00:00')
        );

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

        $appointment = $this->createAppointment(
            $doctor,
            $patient,
            '2026-08-24 10:00:00',
            '2026-08-24 10:30:00'
        );

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('10:00')
            ->assertSee('Alejandro')
            ->assertSee(
                route('appointments.show', [
                    'uuid' => $appointment->uuid,
                ]),
                false
            );
    }

    public function test_dashboard_ignores_cancelled_appointment_when_finding_next_appointment(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-24 08:00:00')
        );

        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $patient = $this->createPatient();

        $cancelled = $this->createAppointment(
            $doctor,
            $patient,
            '2026-08-24 09:00:00',
            '2026-08-24 09:30:00'
        );

        $cancelled->update([
            'status' => 'cancelled',
        ]);

        $next = $this->createAppointment(
            $doctor,
            $patient,
            '2026-08-24 10:00:00',
            '2026-08-24 10:30:00'
        );

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('10:00')
            ->assertSee(
                route('appointments.show', [
                    'uuid' => $next->uuid,
                ]),
                false
            );
    }

    public function test_dashboard_displays_today_consultations_count(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-24 12:00:00')
        );

        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $patient = $this->createPatient();

        Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => '2026-08-24 10:00:00',
            'status' => 'completed',
            'reason' => 'Consulta del día',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Actividad de hoy')
            ->assertSee('Consultas')
            ->assertSee('1');
    }

    public function test_dashboard_displays_today_prescriptions_count(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-24 12:00:00')
        );

        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $patient = $this->createPatient();

        Prescription::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'prescribed_at' => '2026-08-24 10:00:00',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Recetas')
            ->assertSee('1');
    }

    public function test_dashboard_displays_regular_day_off(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-25 08:00:00')
        );

        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        /*
         * Solo lunes.
         * 25/08/2026 es martes.
         */
        $this->createSchedule(
            $doctor,
            dayOfWeek: 1,
            startTime: '09:00',
            endTime: '14:00'
        );

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Día libre')
            ->assertSee('Hoy no hay horario de atención');
    }

    public function test_dashboard_displays_full_day_exception_reason(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-24 08:00:00')
        );

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

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Vacaciones')
            ->assertSee('Hoy no hay horario de atención');
    }

    public function test_dashboard_displays_partial_schedule_exception(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-24 08:00:00')
        );

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

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Excepciones de hoy')
            ->assertSee('Horario bloqueado')
            ->assertSee('Compromiso personal');
    }

    public function test_dashboard_recognizes_extraordinary_availability(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-29 08:00:00')
        );

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

        $response = $this->actingAs($user)
            ->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertDontSee('Hoy no hay horario de atención')
            ->assertSee('Horario extraordinario')
            ->assertSee('Horario especial');
    }

    public function test_dashboard_displays_next_seven_days_chart_data(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-24 08:00:00')
        );

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
            '2026-08-24 10:00:00',
            '2026-08-24 10:30:00'
        );

        $this->createAppointment(
            $doctor,
            $patient,
            '2026-08-25 10:00:00',
            '2026-08-25 10:30:00'
        );

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Próximos 7 días')
            ->assertSee('24/08')
            ->assertSee('25/08');
    }

    public function test_dashboard_is_isolated_by_tenant(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-24 08:00:00')
        );

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
            '2026-08-24 10:30:00'
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
            '2026-08-24 11:30:00'
        );

        app(TenantContext::class)->set($tenantA);

        $response = $this->actingAs($userA)
            ->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSee('Paciente')
            ->assertDontSee('Tenant B');
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
        string $status = 'scheduled',
    ): Appointment {
        return Appointment::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => $status,
            'reason' => 'Consulta general',
        ]);
    }
}
