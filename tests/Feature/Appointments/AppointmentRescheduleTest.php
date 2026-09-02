<?php

namespace Tests\Feature\Appointments;

use App\Models\Appointment;
use App\Models\AuditEvent;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\Schedule;
use App\Models\Specialty;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class AppointmentRescheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_open_reschedule_page_for_scheduled_appointment(): void
    {
        [
            $tenant,
            $user,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $appointment = $this->createAppointment(
            $doctor,
            $patient
        );

        $this->actingAs($user)
            ->get(
                route(
                    'appointments.reschedule',
                    [
                        'uuid' => $appointment->uuid,
                    ]
                )
            )
            ->assertOk()
            ->assertSee('Reprogramar cita')
            ->assertSee('Horario actual');
    }

    public function test_user_can_open_reschedule_page_for_confirmed_appointment(): void
    {
        [
            $tenant,
            $user,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $appointment = $this->createAppointment(
            $doctor,
            $patient
        );

        $appointment->confirm();

        $this->actingAs($user)
            ->get(
                route(
                    'appointments.reschedule',
                    [
                        'uuid' => $appointment->uuid,
                    ]
                )
            )
            ->assertOk()
            ->assertSee('Reprogramar cita');
    }

    public function test_completed_appointment_cannot_open_reschedule_page(): void
    {
        [
            $tenant,
            $user,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $appointment = $this->createAppointment(
            $doctor,
            $patient
        );

        $appointment->start();
        $appointment->complete();

        $this->actingAs($user)
            ->get(
                route(
                    'appointments.reschedule',
                    [
                        'uuid' => $appointment->uuid,
                    ]
                )
            )
            ->assertNotFound();
    }

    public function test_cancelled_appointment_cannot_open_reschedule_page(): void
    {
        [
            $tenant,
            $user,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $appointment = $this->createAppointment(
            $doctor,
            $patient
        );

        $appointment->cancel();

        $this->actingAs($user)
            ->get(
                route(
                    'appointments.reschedule',
                    [
                        'uuid' => $appointment->uuid,
                    ]
                )
            )
            ->assertNotFound();
    }

    public function test_reschedule_page_loads_available_slots(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-24 08:00:00')
        );

        [
            $tenant,
            $user,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $this->createSchedule(
            $doctor,
            2,
            '09:00',
            '11:00'
        );

        $appointment = $this->createAppointment(
            $doctor,
            $patient,
            '2026-08-25 09:00:00',
            '2026-08-25 09:30:00'
        );

        Livewire::actingAs($user)
            ->test(
                'pages::appointments.reschedule',
                [
                    'uuid' => $appointment->uuid,
                ]
            )
            ->assertSet(
                'date',
                '2026-08-25'
            )
            ->assertSet(
                'time',
                '09:00'
            )
            ->assertSet(
                'availableSlots',
                [
                    '09:00',
                    '09:30',
                    '10:00',
                    '10:30',
                ]
            );
    }

    public function test_current_appointment_does_not_block_its_own_slot(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-24 08:00:00')
        );

        [
            $tenant,
            $user,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $this->createSchedule(
            $doctor,
            2,
            '09:00',
            '10:00'
        );

        $appointment = $this->createAppointment(
            $doctor,
            $patient,
            '2026-08-25 09:00:00',
            '2026-08-25 09:30:00'
        );

        Livewire::actingAs($user)
            ->test(
                'pages::appointments.reschedule',
                [
                    'uuid' => $appointment->uuid,
                ]
            )
            ->assertSet(
                'availableSlots',
                [
                    '09:00',
                    '09:30',
                ]
            );
    }

    public function test_changing_date_refreshes_available_slots(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-24 08:00:00')
        );

        [
            $tenant,
            $user,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        /*
         * Tuesday.
         */
        $this->createSchedule(
            $doctor,
            2,
            '09:00',
            '10:00'
        );

        /*
         * Wednesday.
         */
        $this->createSchedule(
            $doctor,
            3,
            '14:00',
            '15:00'
        );

        $appointment = $this->createAppointment(
            $doctor,
            $patient,
            '2026-08-25 09:00:00',
            '2026-08-25 09:30:00'
        );

        Livewire::actingAs($user)
            ->test(
                'pages::appointments.reschedule',
                [
                    'uuid' => $appointment->uuid,
                ]
            )
            ->set(
                'date',
                '2026-08-26'
            )
            ->assertSet(
                'time',
                ''
            )
            ->assertSet(
                'availableSlots',
                [
                    '14:00',
                    '14:30',
                ]
            );
    }

    public function test_user_can_reschedule_appointment_to_available_slot(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-24 08:00:00')
        );

        [
            $tenant,
            $user,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $this->createSchedule(
            $doctor,
            3,
            '14:00',
            '16:00'
        );

        $appointment = $this->createAppointment(
            $doctor,
            $patient,
            '2026-08-25 09:00:00',
            '2026-08-25 09:30:00'
        );

        Livewire::actingAs($user)
            ->test(
                'pages::appointments.reschedule',
                [
                    'uuid' => $appointment->uuid,
                ]
            )
            ->set(
                'date',
                '2026-08-26'
            )
            ->set(
                'time',
                '14:30'
            )
            ->call(
                'rescheduleAppointment'
            )
            ->assertHasNoErrors()
            ->assertRedirect(
                route(
                    'appointments.show',
                    [
                        'uuid' => $appointment->uuid,
                    ]
                )
            );

        $appointment->refresh();

        $this->assertSame(
            '2026-08-26 14:30:00',
            $appointment->starts_at->format(
                'Y-m-d H:i:s'
            )
        );

        $this->assertSame(
            '2026-08-26 15:00:00',
            $appointment->ends_at->format(
                'Y-m-d H:i:s'
            )
        );

        $this->assertSame(
            Appointment::STATUS_SCHEDULED,
            $appointment->status
        );
    }

    public function test_rescheduling_appointment_creates_audit_event(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-24 08:00:00')
        );

        [
            $tenant,
            $user,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $this->createSchedule(
            $doctor,
            3,
            '14:00',
            '16:00'
        );

        $appointment = $this->createAppointment(
            $doctor,
            $patient,
            '2026-08-25 09:00:00',
            '2026-08-25 09:30:00'
        );

        Livewire::actingAs($user)
            ->test(
                'pages::appointments.reschedule',
                [
                    'uuid' => $appointment->uuid,
                ]
            )
            ->set(
                'date',
                '2026-08-26'
            )
            ->set(
                'time',
                '14:30'
            )
            ->call(
                'rescheduleAppointment'
            )
            ->assertHasNoErrors();

        $event = AuditEvent::query()
            ->where(
                'action',
                'appointment.rescheduled'
            )
            ->firstOrFail();

        $this->assertSame(
            $tenant->id,
            $event->tenant_id
        );

        $this->assertSame(
            $user->id,
            $event->user_id
        );

        $this->assertSame(
            Appointment::class,
            $event->auditable_type
        );

        $this->assertSame(
            $appointment->id,
            $event->auditable_id
        );

        $this->assertTrue(
            Carbon::parse(
                $event->metadata['previous_starts_at']
            )->equalTo(
                Carbon::parse('2026-08-25 09:00:00')
            )
        );

        $this->assertTrue(
            Carbon::parse(
                $event->metadata['previous_ends_at']
            )->equalTo(
                Carbon::parse('2026-08-25 09:30:00')
            )
        );

        $this->assertTrue(
            Carbon::parse(
                $event->metadata['new_starts_at']
            )->equalTo(
                Carbon::parse('2026-08-26 14:30:00')
            )
        );

        $this->assertTrue(
            Carbon::parse(
                $event->metadata['new_ends_at']
            )->equalTo(
                Carbon::parse('2026-08-26 15:00:00')
            )
        );

        $this->assertArrayNotHasKey(
            'patient_id',
            $event->metadata
        );

        $this->assertArrayNotHasKey(
            'reason',
            $event->metadata
        );
    }

    public function test_confirmed_appointment_becomes_scheduled_after_rescheduling(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-24 08:00:00')
        );

        [
            $tenant,
            $user,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $this->createSchedule(
            $doctor,
            3,
            '14:00',
            '16:00'
        );

        $appointment = $this->createAppointment(
            $doctor,
            $patient,
            '2026-08-25 09:00:00',
            '2026-08-25 09:30:00'
        );

        $appointment->confirm();

        $this->assertNotNull(
            $appointment->fresh()->confirmed_at
        );

        Livewire::actingAs($user)
            ->test(
                'pages::appointments.reschedule',
                [
                    'uuid' => $appointment->uuid,
                ]
            )
            ->set(
                'date',
                '2026-08-26'
            )
            ->set(
                'time',
                '14:00'
            )
            ->call(
                'rescheduleAppointment'
            )
            ->assertHasNoErrors();

        $appointment->refresh();

        $this->assertSame(
            Appointment::STATUS_SCHEDULED,
            $appointment->status
        );

        $this->assertNull(
            $appointment->confirmed_at
        );
    }

    public function test_user_cannot_reschedule_to_occupied_slot(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-24 08:00:00')
        );

        [
            $tenant,
            $user,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $this->createSchedule(
            $doctor,
            3,
            '14:00',
            '16:00'
        );

        $appointment = $this->createAppointment(
            $doctor,
            $patient,
            '2026-08-25 09:00:00',
            '2026-08-25 09:30:00'
        );

        $otherPatient = Patient::create([
            'first_name' => 'Otro',
            'last_name' => 'Paciente',
            'birth_date' => '1992-04-10',
        ]);

        $this->createAppointment(
            $doctor,
            $otherPatient,
            '2026-08-26 14:30:00',
            '2026-08-26 15:00:00'
        );

        Livewire::actingAs($user)
            ->test(
                'pages::appointments.reschedule',
                [
                    'uuid' => $appointment->uuid,
                ]
            )
            ->set(
                'date',
                '2026-08-26'
            )
            ->set(
                'time',
                '14:30'
            )
            ->call(
                'rescheduleAppointment'
            )
            ->assertHasErrors('time');

        $appointment->refresh();

        $this->assertSame(
            '2026-08-25 09:00:00',
            $appointment->starts_at->format(
                'Y-m-d H:i:s'
            )
        );
    }

    public function test_slot_is_revalidated_when_saving(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-24 08:00:00')
        );

        [
            $tenant,
            $user,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $this->createSchedule(
            $doctor,
            3,
            '14:00',
            '16:00'
        );

        $appointment = $this->createAppointment(
            $doctor,
            $patient,
            '2026-08-25 09:00:00',
            '2026-08-25 09:30:00'
        );

        $component = Livewire::actingAs($user)
            ->test(
                'pages::appointments.reschedule',
                [
                    'uuid' => $appointment->uuid,
                ]
            )
            ->set(
                'date',
                '2026-08-26'
            )
            ->set(
                'time',
                '14:30'
            );

        /*
         * El horario estaba disponible cuando el usuario
         * abrió la pantalla, pero otra cita lo ocupa antes
         * de guardar.
         */
        $otherPatient = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Concurrente',
            'birth_date' => '1985-03-20',
        ]);

        $this->createAppointment(
            $doctor,
            $otherPatient,
            '2026-08-26 14:30:00',
            '2026-08-26 15:00:00'
        );

        $component
            ->call(
                'rescheduleAppointment'
            )
            ->assertHasErrors('time');

        $appointment->refresh();

        $this->assertSame(
            '2026-08-25 09:00:00',
            $appointment->starts_at->format(
                'Y-m-d H:i:s'
            )
        );
    }

    public function test_user_cannot_reschedule_appointment_from_another_tenant(): void
    {
        [
            $tenantA,
            $userA,
            $doctorA,
            $patientA,
        ] = $this->createContext();

        app(TenantContext::class)->set(
            $tenantA
        );

        $appointment = $this->createAppointment(
            $doctorA,
            $patientA
        );

        [
            $tenantB,
            $userB,
        ] = $this->createContext();

        app(TenantContext::class)->set(
            $tenantB
        );

        $this->actingAs($userB)
            ->get(
                route(
                    'appointments.reschedule',
                    [
                        'uuid' => $appointment->uuid,
                    ]
                )
            )
            ->assertNotFound();
    }

    private function createContext(): array
    {
        $tenant = Tenant::create([
            'name' => 'Consultorio Test',
            'slug' =>
            'consultorio-' . str()->random(10),
            'onboarding_completed_at' => now(),
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Dr. Test',
            'email' =>
            str()->random(10) . '@example.com',
            'password' => 'password123',
            'role' => 'owner',
        ]);

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
            $user,
            $doctor,
            $patient,
        ];
    }

    private function createSchedule(
        DoctorProfile $doctor,
        int $dayOfWeek,
        string $startTime,
        string $endTime,
    ): Schedule {
        return Schedule::create([
            'doctor_profile_id' => $doctor->id,
            'day_of_week' => $dayOfWeek,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'appointment_duration' => 30,
            'buffer_before' => 0,
            'buffer_after' => 0,
            'active' => true,
        ]);
    }

    private function createAppointment(
        DoctorProfile $doctor,
        Patient $patient,
        string $startsAt = '2026-08-25 09:00:00',
        string $endsAt = '2026-08-25 09:30:00',
    ): Appointment {
        return Appointment::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' =>
            Appointment::STATUS_SCHEDULED,
            'reason' => 'Consulta general',
        ]);
    }
}
