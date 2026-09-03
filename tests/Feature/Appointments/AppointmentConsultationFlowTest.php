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

    public function test_starting_appointment_creates_draft_consultation(): void
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

        $consultation = Consultation::query()
            ->where(
                'appointment_id',
                $appointment->id
            )
            ->firstOrFail();

        $this->assertSame(
            Consultation::STATUS_DRAFT,
            $consultation->status
        );

        $this->assertSame(
            $patient->id,
            $consultation->patient_id
        );

        $this->assertSame(
            $doctor->id,
            $consultation->doctor_profile_id
        );

        $this->assertSame(
            $appointment->reason,
            $consultation->reason
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
            ->assertSee(
                'Consulta iniciada desde una cita'
            )
            ->assertSee('En atención');
    }

    public function test_leaving_consultation_saves_draft_and_keeps_appointment_in_progress(): void
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
            ->set(
                'subjective',
                'Dolor abdominal desde ayer'
            )
            ->call('leaveConsultation')
            ->assertRedirect(
                route(
                    'appointments.show',
                    [
                        'uuid' => $appointment->uuid,
                    ]
                )
            );

        $consultation = Consultation::query()
            ->where(
                'appointment_id',
                $appointment->id
            )
            ->firstOrFail();

        $appointment->refresh();

        $this->assertSame(
            $appointment->id,
            $consultation->appointment_id
        );

        $this->assertSame(
            Consultation::STATUS_DRAFT,
            $consultation->status
        );

        $this->assertNull(
            $consultation->completed_at
        );

        $this->assertSame(
            'Dolor abdominal desde ayer',
            $consultation->subjective
        );

        $this->assertSame(
            Appointment::STATUS_IN_PROGRESS,
            $appointment->status
        );

        $this->assertNull(
            $appointment->completed_at
        );
    }

    public function test_continuing_consultation_loads_existing_draft_data(): void
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
            'reason' => 'Consulta general',
            'subjective' => 'Dolor abdominal desde ayer',
            'status' => Consultation::STATUS_DRAFT,
        ]);

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
                'consultation.id',
                $consultation->id
            )
            ->assertSet(
                'reason',
                'Consulta general'
            )
            ->assertSet(
                'subjective',
                'Dolor abdominal desde ayer'
            );
    }

    public function test_completing_consultation_completes_consultation_and_appointment(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-24 10:20:00')
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
            ->set(
                'assessment',
                'Cuadro clínico estable'
            )
            ->set(
                'plan',
                'Seguimiento en una semana'
            )
            ->call('completeConsultation');

        $consultation = Consultation::query()
            ->where(
                'appointment_id',
                $appointment->id
            )
            ->firstOrFail();

        $appointment->refresh();

        $this->assertSame(
            Consultation::STATUS_COMPLETED,
            $consultation->status
        );

        $this->assertNotNull(
            $consultation->completed_at
        );

        $this->assertTrue(
            $consultation
                ->completed_at
                ->equalTo(now())
        );

        $this->assertSame(
            'Cuadro clínico estable',
            $consultation->assessment
        );

        $this->assertSame(
            'Seguimiento en una semana',
            $consultation->plan
        );

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

    public function test_consultation_created_without_appointment_can_be_left_as_draft(): void
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
            ->set(
                'subjective',
                'Consulta sin cita previa'
            )
            ->call('leaveConsultation')
            ->assertRedirect(
                route(
                    'patients.show',
                    [
                        'uuid' => $patient->uuid,
                    ]
                )
            );

        $consultation = Consultation::query()
            ->firstOrFail();

        $this->assertNull(
            $consultation->appointment_id
        );

        $this->assertSame(
            Consultation::STATUS_DRAFT,
            $consultation->status
        );

        $this->assertNull(
            $consultation->completed_at
        );
    }

    public function test_consultation_created_without_appointment_can_be_completed(): void
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
            ->call('completeConsultation');

        $consultation = Consultation::query()
            ->firstOrFail();

        $this->assertNull(
            $consultation->appointment_id
        );

        $this->assertSame(
            Consultation::STATUS_COMPLETED,
            $consultation->status
        );

        $this->assertNotNull(
            $consultation->completed_at
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
            'status' => Consultation::STATUS_DRAFT,
        ]);

        $this->assertTrue(
            $appointment
                ->consultation
                ->is($consultation)
        );
    }

    public function test_appointment_cannot_have_two_consultations(): void
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

        Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'appointment_id' => $appointment->id,
            'consultation_at' => now(),
            'status' => Consultation::STATUS_DRAFT,
        ]);

        $this->expectException(
            \Illuminate\Database\QueryException::class
        );

        Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'appointment_id' => $appointment->id,
            'consultation_at' => now(),
            'status' => Consultation::STATUS_DRAFT,
        ]);
    }

    private function createContext(): array
    {
        $tenant = Tenant::create([
            'name' => 'Consultorio Test',
            'slug' =>
            'consultorio-' . str()->random(10),
            'trial_started_at' => now(),
            'trial_ends_at' => now()->addDays(30),
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
            'reason' => 'Consulta general',
        ]);
    }
}
