<?php

namespace Tests\Feature\Clinical;

use App\Models\Patient;
use App\Models\PatientMedicalHistory;
use App\Models\PatientProblem;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class PatientClinicalAlertsComponentTest extends TestCase
{
    use RefreshDatabase;

    public function test_component_renders_traceable_structured_alerts(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Clínico',
        ]);

        PatientMedicalHistory::create([
            'patient_id' => $patient->id,
            'allergies_text' => 'Penicilina',
            'current_medications_text' => 'Losartán 50 mg',
        ]);

        PatientProblem::create([
            'patient_id' => $patient->id,
            'code' => 'I10',
            'description' => 'Hipertensión arterial',
        ]);

        $html = Blade::render(
            '<x-clinical.patient-alerts :patient="$patient" />',
            ['patient' => $patient],
        );

        $this->assertStringContainsString('Alertas clínicas del expediente', $html);
        $this->assertStringContainsString('Penicilina', $html);
        $this->assertStringContainsString('Losartán 50 mg', $html);
        $this->assertStringContainsString('Hipertensión arterial', $html);
        $this->assertStringContainsString('Fuente: Antecedentes médicos · Alergias', $html);
        $this->assertStringContainsString('Fuente: Problema clínico · I10', $html);
        $this->assertStringContainsString('No constituye diagnóstico ni recomendación terapéutica.', $html);
    }

    public function test_component_renders_nothing_when_patient_has_no_alert_sources(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Sin Alertas',
        ]);

        $html = Blade::render(
            '<x-clinical.patient-alerts :patient="$patient" />',
            ['patient' => $patient],
        );

        $this->assertStringNotContainsString('Alertas clínicas del expediente', $html);
    }
}
