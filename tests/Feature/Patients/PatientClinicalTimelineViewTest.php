<?php

namespace Tests\Feature\Patients;

use App\Models\Consultation;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Tenant;
use App\Models\User;
use App\Models\PatientProblem;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use App\Models\AuditEvent;

class PatientClinicalTimelineViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_show_displays_completed_consultation_in_clinical_timeline(): void
    {
        [$tenant, $user, $doctor, $patient] =
            $this->createContext();

        app(TenantContext::class)->set($tenant);

        Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now(),
            'reason' => 'Dolor abdominal',
            'status' => Consultation::STATUS_COMPLETED,
        ]);

        Livewire::actingAs($user)
            ->test('pages::patients.show', [
                'uuid' => $patient->uuid,
            ])
            ->assertSee('Historia clínica')
            ->assertSee('Dolor abdominal')
            ->assertSee('Consulta');
    }

    public function test_patient_show_displays_standalone_prescription_in_clinical_timeline(): void
    {
        [$tenant, $user, $doctor, $patient] =
            $this->createContext();

        app(TenantContext::class)->set($tenant);

        $prescription = Prescription::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_id' => null,
            'prescribed_at' => now(),
            'status' => 'active',
        ]);

        $prescription->items()->create([
            'medication_name' => 'Paracetamol',
            'dose' => '500 mg',
            'frequency' => 'Cada 8 horas',
        ]);

        Livewire::actingAs($user)
            ->test('pages::patients.show', [
                'uuid' => $patient->uuid,
            ])
            ->assertSee('Historia clínica')
            ->assertSee('Receta')
            ->assertSee('Paracetamol')
            ->assertSee('500 mg')
            ->assertSee('Cada 8 horas');
    }

    public function test_patient_show_keeps_draft_consultation_out_of_clinical_timeline(): void
    {
        [$tenant, $user, $doctor, $patient] =
            $this->createContext();

        app(TenantContext::class)->set($tenant);

        Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now(),
            'reason' => 'Consulta todavía en borrador',
            'status' => Consultation::STATUS_DRAFT,
        ]);

        Livewire::actingAs($user)
            ->test('pages::patients.show', [
                'uuid' => $patient->uuid,
            ])
            ->assertSee('Consulta en progreso')
            ->assertSee('Consulta todavía en borrador')
            ->assertSee('Esta consulta todavía no forma')
            ->assertSee('parte del historial clínico.')
            ->assertSee('Historia clínica')
            ->assertSee('Sin actividad clínica registrada');
    }

    public function test_patient_show_displays_diagnosis_and_treatment_inside_completed_consultation_event(): void
    {
        [$tenant, $user, $doctor, $patient] =
            $this->createContext();

        app(TenantContext::class)->set($tenant);

        $consultation = Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now(),
            'reason' => 'Dolor de garganta',
            'status' => Consultation::STATUS_COMPLETED,
        ]);

        $consultation->diagnoses()->create([
            'code' => 'J02.9',
            'description' => 'Faringitis aguda',
            'is_primary' => true,
        ]);

        $prescription = $consultation->prescriptions()->create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'prescribed_at' => now(),
            'status' => 'active',
        ]);

        $prescription->items()->create([
            'medication_name' => 'Amoxicilina',
            'dose' => '500 mg',
            'frequency' => 'Cada 8 horas',
            'duration' => '7 días',
        ]);

        Livewire::actingAs($user)
            ->test('pages::patients.show', [
                'uuid' => $patient->uuid,
            ])
            ->assertSee('Dolor de garganta')
            ->assertSee('Diagnósticos')
            ->assertSee('J02.9')
            ->assertSee('Faringitis aguda')
            ->assertSee('Principal')
            ->assertSee('Tratamiento')
            ->assertSee('Amoxicilina')
            ->assertSee('500 mg')
            ->assertSee('Cada 8 horas')
            ->assertSee('7 días')
            ->assertSee('Ver receta');
    }

    public function test_clinical_timeline_keeps_links_to_original_consultation_and_prescription(): void
    {
        [$tenant, $user, $doctor, $patient] =
            $this->createContext();

        app(TenantContext::class)->set($tenant);

        $consultation = Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now(),
            'reason' => 'Consulta con tratamiento',
            'status' => Consultation::STATUS_COMPLETED,
        ]);

        $linkedPrescription = $consultation->prescriptions()->create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'prescribed_at' => now(),
            'status' => 'active',
        ]);

        $standalonePrescription = Prescription::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_id' => null,
            'prescribed_at' => now()->subDay(),
            'status' => 'active',
        ]);

        Livewire::actingAs($user)
            ->test('pages::patients.show', [
                'uuid' => $patient->uuid,
            ])
            ->assertSee(
                route('consultations.show', [
                    'uuid' => $consultation->uuid,
                ]),
                escape: false
            )
            ->assertSee(
                route('prescriptions.show', [
                    'uuid' => $linkedPrescription->uuid,
                ]),
                escape: false
            )
            ->assertSee(
                route('prescriptions.show', [
                    'uuid' => $standalonePrescription->uuid,
                ]),
                escape: false
            );
    }

    public function test_patient_show_displays_existing_medical_history_as_clinical_summary(): void
    {
        [$tenant, $user, $doctor, $patient] =
            $this->createContext();

        app(TenantContext::class)->set($tenant);

        $patient->medicalHistory()->create([
            'allergies_text' => 'Penicilina',
            'current_medications_text' => 'Losartán 50 mg',
            'chronic_conditions_text' => 'Hipertensión arterial',
            'surgeries_text' => 'Apendicectomía en 2018',
        ]);

        Livewire::actingAs($user)
            ->test('pages::patients.show', [
                'uuid' => $patient->uuid,
            ])
            ->assertSee('Resumen clínico')
            ->assertSee('Penicilina')
            ->assertSee('Losartán 50 mg')
            ->assertSee('Hipertensión arterial')
            ->assertSee('Apendicectomía en 2018');
    }

    public function test_patient_show_cannot_open_patient_from_another_tenant(): void
    {
        [$tenantA, $userA, $doctorA, $patientA] =
            $this->createContext();

        $tenantB = Tenant::create([
            'name' => 'Consultorio B',
            'slug' => 'consultorio-b',
            'onboarding_completed_at' => now(),
        ]);

        $userB = User::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Dr. B',
            'email' => 'doctor-b@example.com',
            'password' => 'password123',
            'role' => 'owner',
        ]);

        app(TenantContext::class)->set($tenantB);

        $patientB = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Otro Tenant',
        ]);

        app(TenantContext::class)->set($tenantA);

        $this->expectException(
            \Illuminate\Database\Eloquent\ModelNotFoundException::class
        );

        Livewire::actingAs($userA)
            ->test('pages::patients.show', [
                'uuid' => $patientB->uuid,
            ]);
    }

    public function test_patient_show_displays_historical_diagnoses_in_clinical_summary(): void
    {
        [$tenant, $user, $doctor, $patient] =
            $this->createContext();

        app(TenantContext::class)->set($tenant);

        $olderConsultation = Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now()->subMonths(3),
            'reason' => 'Consulta anterior',
            'status' => Consultation::STATUS_COMPLETED,
        ]);

        $olderConsultation->diagnoses()->create([
            'code' => 'I10',
            'description' => 'Hipertensión esencial',
            'is_primary' => true,
        ]);

        $newerConsultation = Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now()->subMonth(),
            'reason' => 'Consulta reciente',
            'status' => Consultation::STATUS_COMPLETED,
        ]);

        $newerConsultation->diagnoses()->create([
            'code' => 'E11.9',
            'description' => 'Diabetes mellitus tipo 2',
            'is_primary' => true,
        ]);

        Livewire::actingAs($user)
            ->test('pages::patients.show', [
                'uuid' => $patient->uuid,
            ])
            ->assertSee('Diagnósticos históricos')
            ->assertSee('I10')
            ->assertSee('Hipertensión esencial')
            ->assertSee('E11.9')
            ->assertSee('Diabetes mellitus tipo 2');
    }

    public function test_patient_show_consolidates_repeated_historical_diagnoses(): void
    {
        [$tenant, $user, $doctor, $patient] =
            $this->createContext();

        app(TenantContext::class)->set($tenant);

        $olderConsultation = Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now()->subMonths(2),
            'reason' => 'Consulta anterior',
            'status' => Consultation::STATUS_COMPLETED,
        ]);

        $olderConsultation->diagnoses()->create([
            'code' => 'R51X',
            'description' => 'Cefalea',
            'is_primary' => true,
        ]);

        $newerConsultation = Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now()->subMonth(),
            'reason' => 'Consulta reciente',
            'status' => Consultation::STATUS_COMPLETED,
        ]);

        $newerConsultation->diagnoses()->create([
            'code' => 'R51X',
            'description' => 'Cefalea',
            'is_primary' => true,
        ]);

        Livewire::actingAs($user)
            ->test('pages::patients.show', [
                'uuid' => $patient->uuid,
            ])
            ->assertSee('Diagnósticos históricos')
            ->assertSee('R51X')
            ->assertSee('Cefalea')
            ->assertSee('2')
            ->assertSee('registros')
            ->assertSee(
                $newerConsultation
                    ->consultation_at
                    ->format('d/m/Y')
            )
            ->assertSeeInOrder([
                'R51X',
                'Cefalea',
                '2',
                'registros',
            ]);
    }

    public function test_patient_show_displays_historical_treatments_in_clinical_summary(): void
    {
        [$tenant, $user, $doctor, $patient] =
            $this->createContext();

        app(TenantContext::class)->set($tenant);

        $consultation = Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now()->subMonth(),
            'reason' => 'Consulta con tratamiento',
            'status' => Consultation::STATUS_COMPLETED,
        ]);

        $prescription = Prescription::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_id' => $consultation->id,
            'prescribed_at' => $consultation->consultation_at,
            'status' => 'active',
        ]);

        $prescription->items()->create([
            'medication_name' => 'Paracetamol',
            'dose' => '500 mg',
            'frequency' => 'Cada 8 horas',
            'duration' => '5 días',
            'sort_order' => 1,
        ]);

        Livewire::actingAs($user)
            ->test('pages::patients.show', [
                'uuid' => $patient->uuid,
            ])
            ->assertSee('Tratamientos históricos')
            ->assertSee('Paracetamol')
            ->assertSee('500 mg')
            ->assertSee('Cada 8 horas')
            ->assertSee('5 días');
    }

    public function test_patient_show_displays_historical_treatments_without_diagnoses(): void
    {
        [$tenant, $user, $doctor, $patient] =
            $this->createContext();

        app(TenantContext::class)->set($tenant);

        $consultation = Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now()->subDays(10),
            'reason' => 'Consulta sin diagnóstico',
            'status' => Consultation::STATUS_COMPLETED,
        ]);

        $prescription = Prescription::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_id' => $consultation->id,
            'prescribed_at' => $consultation->consultation_at,
            'status' => 'active',
        ]);

        $prescription->items()->create([
            'medication_name' => 'Ibuprofeno',
            'dose' => '400 mg',
            'frequency' => 'Cada 8 horas',
            'duration' => '3 días',
            'sort_order' => 1,
        ]);

        Livewire::actingAs($user)
            ->test('pages::patients.show', [
                'uuid' => $patient->uuid,
            ])
            ->assertDontSee('Diagnósticos históricos')
            ->assertSee('Tratamientos históricos')
            ->assertSee('Ibuprofeno')
            ->assertSee('400 mg')
            ->assertSee('Cada 8 horas')
            ->assertSee('3 días');
    }

    public function test_patient_show_displays_active_and_resolved_clinical_problems(): void
    {
        [$tenant, $user, $doctor, $patient] =
            $this->createContext();

        app(TenantContext::class)->set($tenant);

        PatientProblem::create([
            'patient_id' => $patient->id,
            'code' => 'I10',
            'description' => 'Hipertensión esencial activa',
            'status' => PatientProblem::STATUS_ACTIVE,
            'started_at' => '2026-01-15',
            'notes' => 'Paciente en seguimiento.',
        ]);

        PatientProblem::create([
            'patient_id' => $patient->id,
            'code' => 'J18',
            'description' => 'Neumonía adquirida en la comunidad resuelta',
            'status' => PatientProblem::STATUS_RESOLVED,
            'started_at' => '2025-11-01',
            'resolved_at' => '2025-11-20',
            'notes' => 'Evolución favorable.',
        ]);

        Livewire::actingAs($user)
            ->test('pages::patients.show', [
                'uuid' => $patient->uuid,
            ])
            ->assertSee('Problemas clínicos')
            ->assertSee('I10')
            ->assertSee('Hipertensión esencial activa')
            ->assertSee('Paciente en seguimiento.')
            ->assertSee('J18')
            ->assertSee('Neumonía adquirida en la comunidad resuelta');
    }

    public function test_patient_show_paginates_audit_activity_history(): void
    {
        [$tenant, $user, $doctor, $patient] =
            $this->createContext();

        app(TenantContext::class)->set($tenant);

        for ($i = 1; $i <= 6; $i++) {
            AuditEvent::create([
                'user_id' => $user->id,
                'action' => 'patient.updated',
                'auditable_type' => $patient->getMorphClass(),
                'auditable_id' => $patient->id,
                'description' => 'Evento de auditoría ' . $i,
                'metadata' => [],
                'created_at' => now()->addSeconds($i),
                'updated_at' => now()->addSeconds($i),
            ]);
        }

        Livewire::actingAs($user)
            ->test('pages::patients.show', [
                'uuid' => $patient->uuid,
            ])
            ->assertSee('Historial de actividad')
            ->assertSet('auditEventsPage', 1)
            ->assertSee('Evento de auditoría 6')
            ->assertSee('Evento de auditoría 5')
            ->assertSee('Evento de auditoría 4')
            ->assertSee('Evento de auditoría 3')
            ->assertSee('Evento de auditoría 2')
            ->assertDontSee('Evento de auditoría 1')

            ->call('nextAuditEventsPage')

            ->assertSet('auditEventsPage', 2)
            ->assertSee('Evento de auditoría 1')
            ->assertDontSee('Evento de auditoría 6')

            ->call('previousAuditEventsPage')

            ->assertSet('auditEventsPage', 1)
            ->assertSee('Evento de auditoría 6')
            ->assertDontSee('Evento de auditoría 1');
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

        $doctor = DoctorProfile::create([
            'user_id' => $user->id,
            'first_name' => 'Doctor',
            'last_name' => 'Test',
        ]);

        $patient = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Test',
        ]);

        return [
            $tenant,
            $user,
            $doctor,
            $patient,
        ];
    }
}
