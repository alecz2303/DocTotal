<?php

namespace Tests\Feature\Appointments;

use App\Models\Appointment;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\Specialty;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use LogicException;
use Tests\TestCase;

class AppointmentEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_open_edit_page_for_scheduled_appointment(): void
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
                    'appointments.edit',
                    [
                        'uuid' => $appointment->uuid,
                    ]
                )
            )
            ->assertOk()
            ->assertSee('Editar cita')
            ->assertSee('Consulta general')
            ->assertSee('Notas originales');
    }

    public function test_user_can_open_edit_page_for_confirmed_appointment(): void
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
                    'appointments.edit',
                    [
                        'uuid' => $appointment->uuid,
                    ]
                )
            )
            ->assertOk()
            ->assertSee('Editar cita');
    }

    public function test_edit_component_loads_current_reason_and_notes(): void
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

        Livewire::actingAs($user)
            ->test(
                'pages::appointments.edit',
                [
                    'uuid' => $appointment->uuid,
                ]
            )
            ->assertSet(
                'reason',
                'Consulta general'
            )
            ->assertSet(
                'notes',
                'Notas originales'
            );
    }

    public function test_user_can_update_appointment_reason_and_notes(): void
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

        Livewire::actingAs($user)
            ->test(
                'pages::appointments.edit',
                [
                    'uuid' => $appointment->uuid,
                ]
            )
            ->set(
                'reason',
                'Dolor abdominal'
            )
            ->set(
                'notes',
                'Paciente refiere dolor desde ayer.'
            )
            ->call('saveAppointment')
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
            'Dolor abdominal',
            $appointment->reason
        );

        $this->assertSame(
            'Paciente refiere dolor desde ayer.',
            $appointment->notes
        );
    }

    public function test_user_can_clear_reason_and_notes(): void
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

        Livewire::actingAs($user)
            ->test(
                'pages::appointments.edit',
                [
                    'uuid' => $appointment->uuid,
                ]
            )
            ->set('reason', '')
            ->set('notes', '')
            ->call('saveAppointment')
            ->assertHasNoErrors();

        $appointment->refresh();

        $this->assertNull(
            $appointment->reason
        );

        $this->assertNull(
            $appointment->notes
        );
    }

    public function test_reason_cannot_exceed_maximum_length(): void
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

        Livewire::actingAs($user)
            ->test(
                'pages::appointments.edit',
                [
                    'uuid' => $appointment->uuid,
                ]
            )
            ->set(
                'reason',
                str_repeat('A', 501)
            )
            ->call('saveAppointment')
            ->assertHasErrors([
                'reason' => 'max',
            ]);

        $appointment->refresh();

        $this->assertSame(
            'Consulta general',
            $appointment->reason
        );
    }

    public function test_notes_cannot_exceed_maximum_length(): void
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

        Livewire::actingAs($user)
            ->test(
                'pages::appointments.edit',
                [
                    'uuid' => $appointment->uuid,
                ]
            )
            ->set(
                'notes',
                str_repeat('A', 5001)
            )
            ->call('saveAppointment')
            ->assertHasErrors([
                'notes' => 'max',
            ]);

        $appointment->refresh();

        $this->assertSame(
            'Notas originales',
            $appointment->notes
        );
    }

    public function test_checked_in_appointment_cannot_be_edited(): void
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

        $appointment->checkIn();

        $this->actingAs($user)
            ->get(
                route(
                    'appointments.edit',
                    [
                        'uuid' => $appointment->uuid,
                    ]
                )
            )
            ->assertNotFound();
    }

    public function test_in_progress_appointment_cannot_be_edited(): void
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

        $this->actingAs($user)
            ->get(
                route(
                    'appointments.edit',
                    [
                        'uuid' => $appointment->uuid,
                    ]
                )
            )
            ->assertNotFound();
    }

    public function test_completed_appointment_cannot_be_edited(): void
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
                    'appointments.edit',
                    [
                        'uuid' => $appointment->uuid,
                    ]
                )
            )
            ->assertNotFound();
    }

    public function test_cancelled_appointment_cannot_be_edited(): void
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
                    'appointments.edit',
                    [
                        'uuid' => $appointment->uuid,
                    ]
                )
            )
            ->assertNotFound();
    }

    public function test_no_show_appointment_cannot_be_edited(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-24 10:45:00')
        );

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

        $appointment->markNoShow();

        $this->actingAs($user)
            ->get(
                route(
                    'appointments.edit',
                    [
                        'uuid' => $appointment->uuid,
                    ]
                )
            )
            ->assertNotFound();
    }

    public function test_model_rejects_updating_details_from_invalid_status(): void
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

        $this->expectException(
            LogicException::class
        );

        $appointment->updateDetails(
            'Nuevo motivo',
            'Nuevas notas'
        );
    }

    public function test_user_cannot_open_edit_page_for_appointment_from_another_tenant(): void
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
                    'appointments.edit',
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

    private function createAppointment(
        DoctorProfile $doctor,
        Patient $patient,
    ): Appointment {
        return Appointment::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'starts_at' =>
            '2026-08-24 10:00:00',
            'ends_at' =>
            '2026-08-24 10:30:00',
            'status' =>
            Appointment::STATUS_SCHEDULED,
            'reason' =>
            'Consulta general',
            'notes' =>
            'Notas originales',
        ]);
    }
}
