<?php

namespace Tests\Feature;

use App\Models\ClinicalDocument;
use App\Models\LaboratoryStudy;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicalFilesCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_files_center_requires_authentication(): void
    {
        $this->get('/files')->assertRedirect('/login');
    }

    public function test_files_center_lists_only_documents_from_current_tenant(): void
    {
        $tenantA = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'trial_started_at' => now(),
            'trial_ends_at' => now()->addDays(30),
            'onboarding_completed_at' => now(),
        ]);

        $tenantB = Tenant::create([
            'name' => 'Tenant B',
            'slug' => 'tenant-b',
            'trial_started_at' => now(),
            'trial_ends_at' => now()->addDays(30),
            'onboarding_completed_at' => now(),
        ]);

        app(TenantContext::class)->set($tenantA);

        $userA = User::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Dr. A',
            'email' => 'a@example.com',
            'password' => 'password123',
            'role' => 'owner',
        ]);

        $patientA = Patient::create([
            'first_name' => 'Ana',
            'last_name' => 'Alfa',
        ]);

        ClinicalDocument::create([
            'patient_id' => $patientA->id,
            'category' => ClinicalDocument::CATEGORY_LABORATORY,
            'title' => 'Biometría de Ana',
            'original_name' => 'ana.pdf',
            'disk' => 'local',
            'path' => 'clinical-documents/ana.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
        ]);

        app(TenantContext::class)->set($tenantB);

        $patientB = Patient::create([
            'first_name' => 'Beto',
            'last_name' => 'Beta',
        ]);

        ClinicalDocument::create([
            'patient_id' => $patientB->id,
            'title' => 'Documento secreto B',
            'original_name' => 'b.pdf',
            'disk' => 'local',
            'path' => 'clinical-documents/b.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 2048,
        ]);

        app(TenantContext::class)->set($tenantA);

        $this->actingAs($userA)
            ->get(route('files.index'))
            ->assertOk()
            ->assertSee('Biometría de Ana')
            ->assertSee('Ana Alfa')
            ->assertDontSee('Documento secreto B');
    }

    public function test_laboratory_study_can_reference_a_clinical_document_from_same_tenant(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Ana',
            'last_name' => 'Alfa',
        ]);

        $document = ClinicalDocument::create([
            'patient_id' => $patient->id,
            'category' => ClinicalDocument::CATEGORY_LABORATORY,
            'title' => 'Química sanguínea',
            'original_name' => 'quimica.pdf',
            'disk' => 'local',
            'path' => 'clinical-documents/quimica.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
        ]);

        $study = LaboratoryStudy::create([
            'patient_id' => $patient->id,
            'clinical_document_id' => $document->id,
            'name' => 'Química sanguínea',
            'study_date' => now()->toDateString(),
        ]);

        $this->assertTrue($study->clinicalDocument->is($document));
        $this->assertTrue($document->laboratoryStudies->contains($study));
    }
}
