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

class AppointmentConsultationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_starting_appointment_changes_status_to_in_progress(): void
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
            ->test('pages::appointments.show', [
                'uuid' => $appointment->uuid,
            ])
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

    public function test_starting_appointment_redirects_to_consultation_form_with_appointment_uuid(): void
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
            ->test('pages::appointments.show', [
                'uuid' => $appointment->uuid,
            ])
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

    public function test_consultation_form_loads_in_progress_appointment(): void
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
                    'consultations.create',
                    [
                        'uuid' => $patient->uuid,
                        'appointment' => $appointment->uuid,
                    ]
                )
            )
            ->assertOk()
            ->assertSee('Consulta iniciada desde una cita')
            ->assertSee('En atención');
    }

    public function test_consultation_created_from_appointment_is_linked_to_appointment(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-24 10:05:00')
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
                '2026-08-24T10:05'
            )
            ->set(
                'reason',
                'Consulta desde cita'
            )
            ->call('saveConsultation');

        $consultation = Consultation::query()
            ->firstOrFail();

        $this->assertSame(
            $appointment->id,
            $consultation->appointment_id
        );

        $this->assertTrue(
            $consultation
                ->appointment
                ->is($appointment)
        );
    }

    public function test_saving_consultation_completes_appointment_automatically(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-24 10:05:00')
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
                '2026-08-24T10:05'
            )
            ->set(
                'reason',
                'Consulta desde agenda'
            )
            ->call('saveConsultation');

        $appointment->refresh();

        $this->assertSame(
            Appointment::STATUS_COMPLETED,
            $appointment->status
        );

        $this->assertNotNull(
            $appointment->completed_at
        );

        $this->assertTrue(
            $appointment
                ->completed_at
                ->equalTo(now())
        );
    }

    public function test_consultation_created_without_appointment_still_works(): void
    {
        [
            $tenant,
            $user,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test(
                'pages::consultations.create',
                [
                    'uuid' => $patient->uuid,
                ]
            )
            ->set(
                'consultation_at',
                '2026-08-24T10:00'
            )
            ->set(
                'reason',
                'Consulta directa'
            )
            ->call('saveConsultation');

        $consultation = Consultation::query()
            ->firstOrFail();

        $this->assertNull(
            $consultation->appointment_id
        );

        $this->assertSame(
            'completed',
            $consultation->status
        );
    }

    public function test_consultation_form_rejects_appointment_from_another_patient(): void
    {
        [
            $tenant,
            $user,
            $doctor,
            $patientA,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $patientB = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'B',
            'birth_date' => '1990-01-15',
        ]);

        $appointment = $this->createAppointment(
            $doctor,
            $patientA
        );

        $appointment->start();

        $this->actingAs($user)
            ->get(
                route(
                    'consultations.create',
                    [
                        'uuid' => $patientB->uuid,
                        'appointment' => $appointment->uuid,
                    ]
                )
            )
            ->assertNotFound();
    }

    public function test_consultation_form_rejects_appointment_not_in_progress(): void
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
                    'consultations.create',
                    [
                        'uuid' => $patient->uuid,
                        'appointment' => $appointment->uuid,
                    ]
                )
            )
            ->assertNotFound();
    }

    public function test_appointment_has_one_consultation_relation(): void
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
            'status' => 'completed',
        ]);

        $this->assertTrue(
            $appointment
                ->consultation
                ->is($consultation)
        );
    }

    private function createContext(): array
    {
        $tenant = Tenant::create([
            'name' => 'Consultorio Test',
            'slug' => 'consultorio-' . str()->random(10),
            'onboarding_completed_at' => now(),
        ]);

        app(TenantContext::class)->set($tenant);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Dr. Test',
            'email' => str()->random(10) . '@example.com',
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
            'starts_at' => '2026-08-24 10:00:00',
            'ends_at' => '2026-08-24 10:30:00',
            'status' => Appointment::STATUS_SCHEDULED,
            'reason' => 'Consulta general',
        ]);
    }
}
