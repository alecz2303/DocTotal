<?php

namespace Tests\Feature\Appointments;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\Specialty;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class AppointmentShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_open_appointment_show(): void
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
                    'appointments.show',
                    [
                        'uuid' => $appointment->uuid,
                    ]
                )
            )
            ->assertOk()
            ->assertSee('Cita')
            ->assertSee('Programada')
            ->assertSee('Consulta general');
    }

    public function test_starting_appointment_moves_it_to_in_progress(): void
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
                'pages::appointments.show',
                [
                    'uuid' => $appointment->uuid,
                ]
            )
            ->call('startAppointment');

        $appointment->refresh();

        $this->assertSame(
            Appointment::STATUS_IN_PROGRESS,
            $appointment->status
        );

        $this->assertNotNull(
            $appointment->started_at
        );
    }

    public function test_starting_appointment_redirects_to_consultation_create(): void
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
                'pages::appointments.show',
                [
                    'uuid' => $appointment->uuid,
                ]
            )
            ->call('startAppointment')
            ->assertRedirect(
                route(
                    'consultations.create',
                    [
                        'uuid' => $patient->uuid,
                        'appointment' => $appointment->uuid,
                    ]
                )
            );
    }

    public function test_in_progress_appointment_shows_continue_consultation_action(): void
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
                    'appointments.show',
                    [
                        'uuid' => $appointment->uuid,
                    ]
                )
            )
            ->assertOk()
            ->assertSee('En atención')
            ->assertSee('Continuar consulta')
            ->assertDontSee('Iniciar consulta');
    }

    public function test_continue_consultation_without_existing_consultation_returns_to_create_form(): void
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

        Livewire::actingAs($user)
            ->test(
                'pages::appointments.show',
                [
                    'uuid' => $appointment->uuid,
                ]
            )
            ->call('continueConsultation')
            ->assertRedirect(
                route(
                    'consultations.create',
                    [
                        'uuid' => $patient->uuid,
                        'appointment' => $appointment->uuid,
                    ]
                )
            );
    }

    public function test_continue_consultation_with_existing_consultation_opens_consultation_show(): void
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

        $consultation = Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'appointment_id' => $appointment->id,
            'consultation_at' => now(),
            'reason' => 'Consulta asociada',
            'status' => 'completed',
        ]);

        Livewire::actingAs($user)
            ->test(
                'pages::appointments.show',
                [
                    'uuid' => $appointment->uuid,
                ]
            )
            ->call('continueConsultation')
            ->assertRedirect(
                route(
                    'consultations.show',
                    [
                        'uuid' => $consultation->uuid,
                    ]
                )
            );
    }

    public function test_consultation_form_precloads_appointment_reason(): void
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

        Livewire::withQueryParams([
            'appointment' => $appointment->uuid,
        ])
            ->actingAs($user)
            ->test(
                'pages::consultations.create',
                [
                    'uuid' => $patient->uuid,
                ]
            )
            ->assertSet(
                'reason',
                'Consulta general'
            )
            ->assertSet(
                'appointment.id',
                $appointment->id
            );
    }

    public function test_saving_consultation_links_it_to_appointment_and_completes_appointment(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-24 10:10:00')
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

        $appointment->start();

        Livewire::withQueryParams([
            'appointment' => $appointment->uuid,
        ])
            ->actingAs($user)
            ->test(
                'pages::consultations.create',
                [
                    'uuid' => $patient->uuid,
                ]
            )
            ->set(
                'consultation_at',
                '2026-08-24T10:10'
            )
            ->set(
                'subjective',
                'Dolor desde ayer'
            )
            ->call('saveConsultation')
            ->assertHasNoErrors();

        $consultation = Consultation::query()
            ->firstOrFail();

        $appointment->refresh();

        $this->assertSame(
            $appointment->id,
            $consultation->appointment_id
        );

        $this->assertSame(
            Appointment::STATUS_COMPLETED,
            $appointment->status
        );

        $this->assertNotNull(
            $appointment->completed_at
        );
    }

    public function test_completed_appointment_does_not_show_continue_consultation_action(): void
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
                    'appointments.show',
                    [
                        'uuid' => $appointment->uuid,
                    ]
                )
            )
            ->assertOk()
            ->assertSee('Completada')
            ->assertDontSee('Continuar consulta')
            ->assertDontSee('Iniciar consulta');
    }

    public function test_user_cannot_open_appointment_show_from_another_tenant(): void
    {
        [
            $tenantA,
            $userA,
            $doctorA,
            $patientA,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenantA);

        $appointment = $this->createAppointment(
            $doctorA,
            $patientA
        );

        [
            $tenantB,
            $userB,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenantB);

        $this->actingAs($userB)
            ->get(
                route(
                    'appointments.show',
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

        app(TenantContext::class)->set($tenant);

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
        ]);
    }
}
