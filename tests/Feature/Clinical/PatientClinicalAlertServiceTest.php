<?php

namespace Tests\Feature\Clinical;

use App\Models\Patient;
use App\Models\PatientMedicalHistory;
use App\Models\PatientProblem;
use App\Models\Tenant;
use App\Services\Clinical\PatientClinicalAlertService;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class PatientClinicalAlertServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_traceable_alerts_from_existing_structured_patient_data(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Ana',
            'last_name' => 'López',
        ]);

        PatientMedicalHistory::create([
            'patient_id' => $patient->id,
            'allergies_text' => 'Penicilina',
            'current_medications_text' => 'Losartán 50 mg',
            'chronic_conditions_text' => 'Hipertensión arterial',
        ]);

        $problem = PatientProblem::create([
            'patient_id' => $patient->id,
            'code' => 'E11.9',
            'description' => 'Diabetes mellitus tipo 2',
            'status' => PatientProblem::STATUS_ACTIVE,
        ]);

        $alerts = app(PatientClinicalAlertService::class)
            ->forPatient($patient);

        $this->assertCount(4, $alerts);

        $this->assertSame('allergies', $alerts[0]['type']);
        $this->assertSame('critical', $alerts[0]['severity']);
        $this->assertSame('Penicilina', $alerts[0]['message']);
        $this->assertSame(
            'Antecedentes médicos · Alergias',
            $alerts[0]['source_label']
        );

        $activeProblem = $alerts->firstWhere('type', 'active_problem');

        $this->assertNotNull($activeProblem);
        $this->assertSame($problem->id, $activeProblem['source_id']);
        $this->assertSame(
            'Diabetes mellitus tipo 2',
            $activeProblem['message']
        );
        $this->assertSame(
            'Problema clínico · E11.9',
            $activeProblem['source_label']
        );
    }

    public function test_it_ignores_resolved_problems_and_empty_history_fields(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Pedro',
            'last_name' => 'Ruiz',
        ]);

        PatientMedicalHistory::create([
            'patient_id' => $patient->id,
            'allergies_text' => '   ',
            'current_medications_text' => null,
            'chronic_conditions_text' => '',
        ]);

        PatientProblem::create([
            'patient_id' => $patient->id,
            'description' => 'Problema resuelto',
            'status' => PatientProblem::STATUS_RESOLVED,
            'resolved_at' => now()->toDateString(),
        ]);

        $alerts = app(PatientClinicalAlertService::class)
            ->forPatient($patient);

        $this->assertCount(0, $alerts);
    }

    public function test_it_does_not_generate_diagnoses_or_recommendations(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'María',
            'last_name' => 'Torres',
        ]);

        PatientProblem::create([
            'patient_id' => $patient->id,
            'description' => 'Asma',
        ]);

        $alert = app(PatientClinicalAlertService::class)
            ->forPatient($patient)
            ->first();

        $this->assertSame('Problema clínico activo', $alert['title']);
        $this->assertSame('Asma', $alert['message']);
        $this->assertArrayNotHasKey('diagnosis', $alert);
        $this->assertArrayNotHasKey('recommendation', $alert);
    }

    public function test_it_rejects_a_patient_from_another_tenant_even_if_the_model_was_loaded_without_scopes(): void
    {
        $tenantA = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        $tenantB = Tenant::create([
            'name' => 'Tenant B',
            'slug' => 'tenant-b',
        ]);

        app(TenantContext::class)->set($tenantB);

        $patientB = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'B',
        ]);

        app(TenantContext::class)->set($tenantA);

        $foreignPatient = Patient::withoutGlobalScopes()
            ->findOrFail($patientB->id);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'El paciente no pertenece al tenant clínico activo.'
        );

        app(PatientClinicalAlertService::class)
            ->forPatient($foreignPatient);
    }
}
