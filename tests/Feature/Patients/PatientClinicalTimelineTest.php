<?php

namespace Tests\Feature\Patients;

use App\Actions\Patients\BuildPatientClinicalTimeline;
use App\Models\Consultation;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Prescription;

class PatientClinicalTimelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_timeline_includes_only_completed_consultations_in_descending_order(): void
    {
        [$tenant, $user, $doctor, $patient] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $olderConsultation = Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now()->subDays(10),
            'reason' => 'Consulta anterior',
            'status' => Consultation::STATUS_COMPLETED,
        ]);

        Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now()->subDays(5),
            'reason' => 'Consulta borrador',
            'status' => Consultation::STATUS_DRAFT,
        ]);

        $newerConsultation = Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now()->subDay(),
            'reason' => 'Consulta reciente',
            'status' => Consultation::STATUS_COMPLETED,
        ]);

        $timeline = app(
            BuildPatientClinicalTimeline::class
        )->handle($patient);

        $this->assertCount(2, $timeline);

        $this->assertSame(
            'consultation',
            $timeline[0]['type']
        );

        $this->assertSame(
            $newerConsultation->id,
            $timeline[0]['consultation']->id
        );

        $this->assertSame(
            'consultation',
            $timeline[1]['type']
        );

        $this->assertSame(
            $olderConsultation->id,
            $timeline[1]['consultation']->id
        );
    }

    public function test_consultation_events_include_diagnoses_and_prescriptions(): void
    {
        [$tenant, $user, $doctor, $patient] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $consultation = Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now()->subDay(),
            'reason' => 'Dolor de garganta',
            'status' => Consultation::STATUS_COMPLETED,
        ]);

        $diagnosis = $consultation->diagnoses()->create([
            'description' => 'Faringitis aguda',
            'is_primary' => true,
        ]);

        $prescription = $consultation->prescriptions()->create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'prescribed_at' => now()->subDay(),
            'status' => 'active',
        ]);

        $prescription->items()->create([
            'medication_name' => 'Paracetamol',
            'dose' => '500 mg',
            'frequency' => 'Cada 8 horas',
            'duration' => '3 días',
        ]);

        $timeline = app(
            BuildPatientClinicalTimeline::class
        )->handle($patient);

        $this->assertCount(1, $timeline);

        $event = $timeline->first();

        $this->assertSame(
            $consultation->id,
            $event['consultation']->id
        );

        $this->assertTrue(
            $event['consultation']
                ->relationLoaded('diagnoses')
        );

        $this->assertTrue(
            $event['consultation']
                ->relationLoaded('prescriptions')
        );

        $this->assertCount(
            1,
            $event['consultation']->diagnoses
        );

        $this->assertSame(
            $diagnosis->id,
            $event['consultation']
                ->diagnoses
                ->first()
                ->id
        );

        $this->assertCount(
            1,
            $event['consultation']->prescriptions
        );

        $loadedPrescription =
            $event['consultation']
            ->prescriptions
            ->first();

        $this->assertSame(
            $prescription->id,
            $loadedPrescription->id
        );

        $this->assertTrue(
            $loadedPrescription
                ->relationLoaded('items')
        );

        $this->assertCount(
            1,
            $loadedPrescription->items
        );

        $this->assertSame(
            'Paracetamol',
            $loadedPrescription
                ->items
                ->first()
                ->medication_name
        );
    }

    public function test_timeline_includes_standalone_prescriptions_in_chronological_order(): void
    {
        [$tenant, $user, $doctor, $patient] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $olderConsultation = Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now()->subDays(10),
            'reason' => 'Consulta anterior',
            'status' => Consultation::STATUS_COMPLETED,
        ]);

        $standalonePrescription = \App\Models\Prescription::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_id' => null,
            'prescribed_at' => now()->subDays(5),
            'status' => 'active',
        ]);

        $newerConsultation = Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now()->subDay(),
            'reason' => 'Consulta reciente',
            'status' => Consultation::STATUS_COMPLETED,
        ]);

        $timeline = app(
            BuildPatientClinicalTimeline::class
        )->handle($patient);

        $this->assertCount(3, $timeline);

        $this->assertSame(
            'consultation',
            $timeline[0]['type']
        );

        $this->assertSame(
            $newerConsultation->id,
            $timeline[0]['consultation']->id
        );

        $this->assertSame(
            'prescription',
            $timeline[1]['type']
        );

        $this->assertSame(
            $standalonePrescription->id,
            $timeline[1]['prescription']->id
        );

        $this->assertSame(
            'consultation',
            $timeline[2]['type']
        );

        $this->assertSame(
            $olderConsultation->id,
            $timeline[2]['consultation']->id
        );
    }

    public function test_prescription_linked_to_consultation_is_not_duplicated_as_standalone_event(): void
    {
        [$tenant, $user, $doctor, $patient] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $consultation = Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now()->subDay(),
            'reason' => 'Consulta con receta',
            'status' => Consultation::STATUS_COMPLETED,
        ]);

        $prescription = $consultation->prescriptions()->create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'prescribed_at' => now()->subDay(),
            'status' => 'active',
        ]);

        $prescription->items()->create([
            'medication_name' => 'Ibuprofeno',
            'dose' => '400 mg',
            'frequency' => 'Cada 8 horas',
        ]);

        $timeline = app(
            BuildPatientClinicalTimeline::class
        )->handle($patient);

        $this->assertCount(1, $timeline);

        $event = $timeline->first();

        $this->assertSame(
            'consultation',
            $event['type']
        );

        $this->assertSame(
            $consultation->id,
            $event['consultation']->id
        );

        $this->assertCount(
            1,
            $event['consultation']->prescriptions
        );

        $this->assertSame(
            $prescription->id,
            $event['consultation']
                ->prescriptions
                ->first()
                ->id
        );
    }

    public function test_timeline_only_contains_events_for_given_patient(): void
    {
        [$tenant, $user, $doctor, $patientA] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $patientB = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'B',
        ]);

        $consultationA = Consultation::create([
            'patient_id' => $patientA->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now()->subDay(),
            'reason' => 'Consulta paciente A',
            'status' => Consultation::STATUS_COMPLETED,
        ]);

        Consultation::create([
            'patient_id' => $patientB->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now(),
            'reason' => 'Consulta paciente B',
            'status' => Consultation::STATUS_COMPLETED,
        ]);

        \App\Models\Prescription::create([
            'patient_id' => $patientB->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_id' => null,
            'prescribed_at' => now(),
            'status' => 'active',
        ]);

        $timeline = app(
            BuildPatientClinicalTimeline::class
        )->handle($patientA);

        $this->assertCount(1, $timeline);

        $this->assertSame(
            'consultation',
            $timeline->first()['type']
        );

        $this->assertSame(
            $consultationA->id,
            $timeline->first()['consultation']->id
        );
    }

    public function test_historical_treatments_are_consolidated_only_when_scheme_matches(): void
    {
        [$tenant, $user, $doctor, $patient] =
            $this->createContext();

        app(TenantContext::class)->set($tenant);

        $olderPrescription = Prescription::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'prescribed_at' => now()->subDays(20),
            'status' => 'active',
        ]);

        $olderPrescription->items()->create([
            'medication_name' => 'Paracetamol',
            'dose' => '500 mg',
            'frequency' => 'Cada 8 horas',
            'duration' => '5 días',
            'sort_order' => 1,
        ]);

        $newerPrescription = Prescription::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'prescribed_at' => now()->subDays(5),
            'status' => 'active',
        ]);

        $newerPrescription->items()->create([
            'medication_name' => 'Paracetamol',
            'dose' => '500 mg',
            'frequency' => 'Cada 8 horas',
            'duration' => '5 días',
            'sort_order' => 1,
        ]);

        $differentSchemePrescription = Prescription::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'prescribed_at' => now()->subDay(),
            'status' => 'active',
        ]);

        $differentSchemePrescription->items()->create([
            'medication_name' => 'Paracetamol',
            'dose' => '500 mg',
            'frequency' => 'Cada 12 horas',
            'duration' => '3 días',
            'sort_order' => 1,
        ]);

        $treatments = app(
            BuildPatientClinicalTimeline::class
        )->treatments($patient);

        $this->assertCount(2, $treatments);

        $sameScheme = $treatments->first(
            fn(array $entry) =>
            $entry['medication_name'] === 'Paracetamol'
                && $entry['frequency'] === 'Cada 8 horas'
                && $entry['duration'] === '5 días'
        );

        $differentScheme = $treatments->first(
            fn(array $entry) =>
            $entry['medication_name'] === 'Paracetamol'
                && $entry['frequency'] === 'Cada 12 horas'
                && $entry['duration'] === '3 días'
        );

        $this->assertNotNull($sameScheme);
        $this->assertNotNull($differentScheme);

        $this->assertSame(
            2,
            $sameScheme['count']
        );

        $this->assertSame(
            1,
            $differentScheme['count']
        );

        $this->assertTrue(
            $sameScheme['last_prescribed_at']
                ->equalTo(
                    $newerPrescription->prescribed_at
                )
        );

        $this->assertSame(
            $newerPrescription->id,
            $sameScheme['latest_prescription']->id
        );
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
