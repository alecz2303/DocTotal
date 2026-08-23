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
use App\Services\AppointmentAvailabilityService;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_time_inside_regular_schedule_is_available(): void
    {
        [
            $tenant,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $this->createSchedule(
            $doctor,
            dayOfWeek: 1,
            startTime: '09:00',
            endTime: '14:00'
        );

        $this->assertTrue(
            $this->service()->isAvailable(
                $doctor,
                '2026-08-24 10:00:00',
                30
            )
        );
    }

    public function test_time_before_regular_schedule_is_not_available(): void
    {
        [
            $tenant,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $this->createSchedule(
            $doctor,
            dayOfWeek: 1,
            startTime: '09:00',
            endTime: '14:00'
        );

        $this->assertFalse(
            $this->service()->isAvailable(
                $doctor,
                '2026-08-24 08:30:00',
                30
            )
        );
    }

    public function test_appointment_must_end_inside_regular_schedule(): void
    {
        [
            $tenant,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $this->createSchedule(
            $doctor,
            dayOfWeek: 1,
            startTime: '09:00',
            endTime: '14:00'
        );

        $this->assertFalse(
            $this->service()->isAvailable(
                $doctor,
                '2026-08-24 13:45:00',
                30
            )
        );
    }

    public function test_day_without_schedule_is_not_available(): void
    {
        [
            $tenant,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $this->createSchedule(
            $doctor,
            dayOfWeek: 1,
            startTime: '09:00',
            endTime: '14:00'
        );

        /*
         * 25 de agosto de 2026 es martes.
         */
        $this->assertFalse(
            $this->service()->isAvailable(
                $doctor,
                '2026-08-25 10:00:00',
                30
            )
        );
    }

    public function test_full_day_block_exception_makes_day_unavailable(): void
    {
        [
            $tenant,
            $doctor,
            $patient,
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

        $this->assertFalse(
            $this->service()->isAvailable(
                $doctor,
                '2026-08-24 10:00:00',
                30
            )
        );
    }

    public function test_partial_block_exception_blocks_only_that_period(): void
    {
        [
            $tenant,
            $doctor,
            $patient,
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

        $service = $this->service();

        $this->assertFalse(
            $service->isAvailable(
                $doctor,
                '2026-08-24 11:00:00',
                30
            )
        );

        $this->assertTrue(
            $service->isAvailable(
                $doctor,
                '2026-08-24 12:00:00',
                30
            )
        );
    }

    public function test_available_exception_can_open_extraordinary_schedule(): void
    {
        [
            $tenant,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        /*
         * Sábado sin horario semanal.
         */
        ScheduleException::create([
            'doctor_profile_id' => $doctor->id,
            'date' => '2026-08-29',
            'type' => 'available',
            'start_time' => '09:00',
            'end_time' => '12:00',
            'reason' => 'Horario extraordinario',
        ]);

        $this->assertTrue(
            $this->service()->isAvailable(
                $doctor,
                '2026-08-29 10:00:00',
                30
            )
        );
    }

    public function test_existing_appointment_blocks_overlapping_time(): void
    {
        [
            $tenant,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $this->createSchedule(
            $doctor,
            dayOfWeek: 1,
            startTime: '09:00',
            endTime: '14:00'
        );

        $this->createAppointment(
            $doctor,
            $patient,
            '2026-08-24 10:00:00',
            '2026-08-24 10:30:00'
        );

        $service = $this->service();

        $this->assertFalse(
            $service->isAvailable(
                $doctor,
                '2026-08-24 10:15:00',
                30
            )
        );

        $this->assertTrue(
            $service->isAvailable(
                $doctor,
                '2026-08-24 10:30:00',
                30
            )
        );
    }

    public function test_cancelled_appointment_does_not_block_availability(): void
    {
        [
            $tenant,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $this->createSchedule(
            $doctor,
            dayOfWeek: 1,
            startTime: '09:00',
            endTime: '14:00'
        );

        $appointment = $this->createAppointment(
            $doctor,
            $patient,
            '2026-08-24 10:00:00',
            '2026-08-24 10:30:00'
        );

        $appointment->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        $this->assertTrue(
            $this->service()->isAvailable(
                $doctor,
                '2026-08-24 10:00:00',
                30
            )
        );
    }

    public function test_no_show_appointment_does_not_block_availability(): void
    {
        [
            $tenant,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $this->createSchedule(
            $doctor,
            dayOfWeek: 1,
            startTime: '09:00',
            endTime: '14:00'
        );

        $appointment = $this->createAppointment(
            $doctor,
            $patient,
            '2026-08-24 10:00:00',
            '2026-08-24 10:30:00'
        );

        $appointment->update([
            'status' => 'no_show',
            'no_show_at' => now(),
        ]);

        $this->assertTrue(
            $this->service()->isAvailable(
                $doctor,
                '2026-08-24 10:00:00',
                30
            )
        );
    }

    public function test_schedule_buffers_block_adjacent_appointments(): void
    {
        [
            $tenant,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $this->createSchedule(
            $doctor,
            dayOfWeek: 1,
            startTime: '09:00',
            endTime: '14:00',
            bufferBefore: 10,
            bufferAfter: 10
        );

        $this->createAppointment(
            $doctor,
            $patient,
            '2026-08-24 10:00:00',
            '2026-08-24 10:30:00'
        );

        /*
         * Aunque la cita anterior termina a 10:30,
         * el buffer impide iniciar inmediatamente.
         */
        $this->assertFalse(
            $this->service()->isAvailable(
                $doctor,
                '2026-08-24 10:30:00',
                30
            )
        );

        $this->assertTrue(
            $this->service()->isAvailable(
                $doctor,
                '2026-08-24 10:40:00',
                30
            )
        );
    }

    public function test_appointment_can_be_ignored_when_checking_reschedule(): void
    {
        [
            $tenant,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $this->createSchedule(
            $doctor,
            dayOfWeek: 1,
            startTime: '09:00',
            endTime: '14:00'
        );

        $appointment = $this->createAppointment(
            $doctor,
            $patient,
            '2026-08-24 10:00:00',
            '2026-08-24 10:30:00'
        );

        $this->assertTrue(
            $this->service()->isAvailable(
                $doctor,
                '2026-08-24 10:00:00',
                30,
                $appointment
            )
        );
    }

    private function service(): AppointmentAvailabilityService
    {
        return app(AppointmentAvailabilityService::class);
    }

    private function createContext(): array
    {
        $tenant = Tenant::create([
            'name' => 'Consultorio Test',
            'slug' => 'consultorio-test',
            'onboarding_completed_at' => now(),
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Dr. Test',
            'email' => 'doctor@example.com',
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

        $patient = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Test',
            'birth_date' => '1990-01-15',
        ]);

        return [
            $tenant,
            $doctor,
            $patient,
        ];
    }

    private function createSchedule(
        DoctorProfile $doctor,
        int $dayOfWeek,
        string $startTime,
        string $endTime,
        int $duration = 30,
        int $bufferBefore = 0,
        int $bufferAfter = 0,
    ): Schedule {
        return Schedule::create([
            'doctor_profile_id' => $doctor->id,
            'day_of_week' => $dayOfWeek,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'appointment_duration' => $duration,
            'buffer_before' => $bufferBefore,
            'buffer_after' => $bufferAfter,
            'active' => true,
        ]);
    }

    private function createAppointment(
        DoctorProfile $doctor,
        Patient $patient,
        string $startsAt,
        string $endsAt,
    ): Appointment {
        return Appointment::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => 'scheduled',
            'reason' => 'Consulta general',
        ]);
    }
}
