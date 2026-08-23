<?php

namespace Tests\Feature\Consultations;

use App\Models\Consultation;
use App\Models\ConsultationDiagnosis;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsultationDiagnosisTest extends TestCase
{
    use RefreshDatabase;

    public function test_diagnosis_belongs_to_consultation_and_tenant(): void
    {
        [$tenant,, $consultation] = $this->createConsultationContext();

        app(TenantContext::class)->set($tenant);

        $diagnosis = ConsultationDiagnosis::create([
            'consultation_id' => $consultation->id,
            'code' => 'R51.9',
            'description' => 'Cefalea no especificada',
            'is_primary' => true,
        ]);

        $this->assertSame(
            $tenant->id,
            $diagnosis->tenant_id
        );

        $this->assertSame(
            $consultation->id,
            $diagnosis->consultation_id
        );

        $this->assertTrue(
            $diagnosis->consultation->is($consultation)
        );
    }

    public function test_consultation_can_have_multiple_diagnoses(): void
    {
        [$tenant,, $consultation] = $this->createConsultationContext();

        app(TenantContext::class)->set($tenant);

        ConsultationDiagnosis::create([
            'consultation_id' => $consultation->id,
            'code' => 'R51.9',
            'description' => 'Cefalea no especificada',
            'is_primary' => true,
        ]);

        ConsultationDiagnosis::create([
            'consultation_id' => $consultation->id,
            'code' => 'R42',
            'description' => 'Mareo',
            'is_primary' => false,
        ]);

        $this->assertCount(
            2,
            $consultation->diagnoses
        );
    }

    public function test_diagnoses_are_isolated_between_tenants(): void
    {
        [$tenantA,, $consultationA] = $this->createConsultationContext(
            'Tenant A',
            'tenant-a',
            'a@example.com'
        );

        [$tenantB,, $consultationB] = $this->createConsultationContext(
            'Tenant B',
            'tenant-b',
            'b@example.com'
        );

        app(TenantContext::class)->set($tenantA);

        ConsultationDiagnosis::create([
            'consultation_id' => $consultationA->id,
            'description' => 'Diagnóstico A',
        ]);

        app(TenantContext::class)->set($tenantB);

        ConsultationDiagnosis::create([
            'consultation_id' => $consultationB->id,
            'description' => 'Diagnóstico B',
        ]);

        app(TenantContext::class)->set($tenantA);

        $diagnoses = ConsultationDiagnosis::all();

        $this->assertCount(1, $diagnoses);

        $this->assertSame(
            'Diagnóstico A',
            $diagnoses->first()->description
        );
    }

    private function createConsultationContext(
        string $tenantName = 'Consultorio Test',
        string $tenantSlug = 'consultorio-test',
        string $email = 'doctor@example.com',
    ): array {
        $tenant = Tenant::create([
            'name' => $tenantName,
            'slug' => $tenantSlug,
            'onboarding_completed_at' => now(),
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Dr. Test',
            'email' => $email,
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

        $consultation = Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now(),
        ]);

        return [
            $tenant,
            $user,
            $consultation,
        ];
    }
}
