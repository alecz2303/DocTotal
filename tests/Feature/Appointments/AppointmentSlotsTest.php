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

class AppointmentSlotsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(
            \Illuminate\Support\Carbon::parse(
                '2026-08-23 08:00:00'
            )
        );
    }

    public function test_slots_are_generated_from_regular_schedule(): void
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
            endTime: '11:00',
            duration: 30
        );

        $slots = $this->service()
            ->slotsForDate(
                $doctor,
                '2026-08-24'
            );

        $this->assertSame(
            [
                '09:00',
                '09:30',
                '10:00',
                '10:30',
            ],
            $slots
                ->map(fn($slot) => $slot->format('H:i'))
                ->all()
        );
    }

    public function test_slots_use_schedule_appointment_duration(): void
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
            endTime: '11:00',
            duration: 60
        );

        $slots = $this->service()
            ->slotsForDate(
                $doctor,
                '2026-08-24'
            );

        $this->assertSame(
            [
                '09:00',
                '10:00',
            ],
            $slots
                ->map(fn($slot) => $slot->format('H:i'))
                ->all()
        );
    }

    public function test_slots_respect_custom_duration(): void
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
            endTime: '10:00',
            duration: 30
        );

        $slots = $this->service()
            ->slotsForDate(
                $doctor,
                '2026-08-24',
                15
            );

        $this->assertSame(
            [
                '09:00',
                '09:15',
                '09:30',
                '09:45',
            ],
            $slots
                ->map(fn($slot) => $slot->format('H:i'))
                ->all()
        );
    }

    public function test_slots_do_not_include_existing_appointment(): void
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
            endTime: '11:00',
            duration: 30
        );

        $this->createAppointment(
            $doctor,
            $patient,
            '2026-08-24 09:30:00',
            '2026-08-24 10:00:00'
        );

        $slots = $this->service()
            ->slotsForDate(
                $doctor,
                '2026-08-24'
            );

        $this->assertSame(
            [
                '09:00',
                '10:00',
                '10:30',
            ],
            $slots
                ->map(fn($slot) => $slot->format('H:i'))
                ->all()
        );
    }

    public function test_cancelled_appointment_does_not_remove_slot(): void
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
            endTime: '10:00',
            duration: 30
        );

        $appointment = $this->createAppointment(
            $doctor,
            $patient,
            '2026-08-24 09:30:00',
            '2026-08-24 10:00:00'
        );

        $appointment->update([
            'status' => 'cancelled',
        ]);

        $slots = $this->service()
            ->slotsForDate(
                $doctor,
                '2026-08-24'
            );

        $this->assertSame(
            [
                '09:00',
                '09:30',
            ],
            $slots
                ->map(fn($slot) => $slot->format('H:i'))
                ->all()
        );
    }

    public function test_partial_block_removes_affected_slots(): void
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
            endTime: '12:00',
            duration: 30
        );

        ScheduleException::create([
            'doctor_profile_id' => $doctor->id,
            'date' => '2026-08-24',
            'type' => 'blocked',
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]);

        $slots = $this->service()
            ->slotsForDate(
                $doctor,
                '2026-08-24'
            );

        $this->assertSame(
            [
                '09:00',
                '09:30',
                '11:00',
                '11:30',
            ],
            $slots
                ->map(fn($slot) => $slot->format('H:i'))
                ->all()
        );
    }

    public function test_full_day_block_returns_no_slots(): void
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
            endTime: '12:00'
        );

        ScheduleException::create([
            'doctor_profile_id' => $doctor->id,
            'date' => '2026-08-24',
            'type' => 'blocked',
        ]);

        $slots = $this->service()
            ->slotsForDate(
                $doctor,
                '2026-08-24'
            );

        $this->assertCount(
            0,
            $slots
        );
    }

    public function test_available_exception_generates_extraordinary_slots(): void
    {
        [
            $tenant,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        ScheduleException::create([
            'doctor_profile_id' => $doctor->id,
            'date' => '2026-08-29',
            'type' => 'available',
            'start_time' => '09:00',
            'end_time' => '11:00',
        ]);

        $slots = $this->service()
            ->slotsForDate(
                $doctor,
                '2026-08-29',
                30
            );

        $this->assertSame(
            [
                '09:00',
                '09:30',
                '10:00',
                '10:30',
            ],
            $slots
                ->map(fn($slot) => $slot->format('H:i'))
                ->all()
        );
    }

    public function test_schedule_buffers_reduce_available_edges(): void
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
            endTime: '11:00',
            duration: 30,
            bufferBefore: 10,
            bufferAfter: 10
        );

        $slots = $this->service()
            ->slotsForDate(
                $doctor,
                '2026-08-24'
            );

        $this->assertSame(
            [
                '09:10',
                '09:40',
                '10:10',
            ],
            $slots
                ->map(fn($slot) => $slot->format('H:i'))
                ->all()
        );
    }

    public function test_slots_are_returned_in_chronological_order(): void
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
            startTime: '14:00',
            endTime: '15:00'
        );

        $this->createSchedule(
            $doctor,
            dayOfWeek: 1,
            startTime: '09:00',
            endTime: '10:00'
        );

        $slots = $this->service()
            ->slotsForDate(
                $doctor,
                '2026-08-24'
            );

        $this->assertSame(
            [
                '09:00',
                '09:30',
                '14:00',
                '14:30',
            ],
            $slots
                ->map(fn($slot) => $slot->format('H:i'))
                ->all()
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
        ]);
    }

    public function test_past_slots_are_not_returned_for_today(): void
    {
        $this->travelTo(
            \Illuminate\Support\Carbon::parse(
                '2026-08-24 10:15:00'
            )
        );

        [
            $tenant,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(\App\Support\TenantContext::class)
            ->set($tenant);

        $this->createSchedule(
            $doctor,
            dayOfWeek: 1,
            startTime: '09:00',
            endTime: '12:00',
            duration: 30
        );

        $slots = $this->service()
            ->slotsForDate(
                $doctor,
                '2026-08-24'
            );

        $this->assertSame(
            [
                '10:30',
                '11:00',
                '11:30',
            ],
            $slots
                ->map(fn($slot) => $slot->format('H:i'))
                ->all()
        );
    }
}
