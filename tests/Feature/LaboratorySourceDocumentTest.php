<?php

namespace Tests\Feature;

use App\Models\ClinicalDocument;
use App\Models\DoctorProfile;
use App\Models\LaboratoryStudy;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LaboratorySourceDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_structured_laboratory_study_can_link_source_document_from_same_patient(): void
    {
        [$tenant, $user, $patient] = $this->createContext();
        app(TenantContext::class)->set($tenant);

        $document = $this->createDocument($patient, 'Biometría original');

        Livewire::actingAs($user)
            ->test('pages::laboratories.index', ['uuid' => $patient->uuid])
            ->call('createStudy')
            ->set('name', 'Biometría hemática')
            ->set('study_date', '2026-09-06')
            ->set('clinical_document_id', $document->id)
            ->set('results.0.parameter_name', 'Hemoglobina')
            ->set('results.0.value', '14.2')
            ->call('saveStudy')
            ->assertHasNoErrors();

        $this->assertSame(
            $document->id,
            LaboratoryStudy::firstOrFail()->clinical_document_id
        );
    }

    public function test_source_document_from_another_patient_is_rejected(): void
    {
        [$tenant, $user, $patient] = $this->createContext();
        app(TenantContext::class)->set($tenant);

        $otherPatient = Patient::create([
            'first_name' => 'Otro',
            'last_name' => 'Paciente',
        ]);

        $document = $this->createDocument($otherPatient, 'Documento de otro paciente');

        Livewire::actingAs($user)
            ->test('pages::laboratories.index', ['uuid' => $patient->uuid])
            ->call('createStudy')
            ->set('name', 'Estudio')
            ->set('study_date', '2026-09-06')
            ->set('clinical_document_id', $document->id)
            ->set('results.0.parameter_name', 'Glucosa')
            ->set('results.0.value', '100')
            ->call('saveStudy')
            ->assertHasErrors(['clinical_document_id']);

        $this->assertDatabaseCount('laboratory_studies', 0);
    }

    public function test_source_document_from_another_tenant_is_rejected(): void
    {
        [$tenantA, $userA, $patientA] = $this->createContext(
            'Consultorio A',
            'consultorio-a',
            'a@example.com'
        );

        $tenantB = Tenant::create([
            'name' => 'Consultorio B',
            'slug' => 'consultorio-b',
            'trial_started_at' => now(),
            'trial_ends_at' => now()->addDays(30),
            'onboarding_completed_at' => now(),
        ]);

        app(TenantContext::class)->set($tenantB);

        $patientB = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'B',
        ]);

        $documentB = $this->createDocument($patientB, 'Documento tenant B');

        app(TenantContext::class)->set($tenantA);

        Livewire::actingAs($userA)
            ->test('pages::laboratories.index', ['uuid' => $patientA->uuid])
            ->call('createStudy')
            ->set('name', 'Estudio')
            ->set('study_date', '2026-09-06')
            ->set('clinical_document_id', $documentB->id)
            ->set('results.0.parameter_name', 'Glucosa')
            ->set('results.0.value', '100')
            ->call('saveStudy')
            ->assertHasErrors(['clinical_document_id']);

        $this->assertDatabaseCount('laboratory_studies', 0);
    }

    public function test_deleting_source_document_nulls_laboratory_link(): void
    {
        [$tenant, $user, $patient] = $this->createContext();
        app(TenantContext::class)->set($tenant);

        $document = $this->createDocument($patient, 'Documento fuente');

        $study = LaboratoryStudy::create([
            'patient_id' => $patient->id,
            'clinical_document_id' => $document->id,
            'name' => 'Estudio',
            'study_date' => '2026-09-06',
        ]);

        $document->delete();

        $this->assertNull($study->fresh()->clinical_document_id);
    }

    private function createDocument(Patient $patient, string $title): ClinicalDocument
    {
        return ClinicalDocument::create([
            'patient_id' => $patient->id,
            'category' => ClinicalDocument::CATEGORY_LABORATORY,
            'title' => $title,
            'original_name' => 'laboratorio.pdf',
            'disk' => 'local',
            'path' => 'clinical-documents/laboratorio.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
        ]);
    }

    private function createContext(
        string $tenantName = 'Consultorio Test',
        string $tenantSlug = 'consultorio-test',
        string $email = 'doctor@example.com',
    ): array {
        $tenant = Tenant::create([
            'name' => $tenantName,
            'slug' => $tenantSlug,
            'trial_started_at' => now(),
            'trial_ends_at' => now()->addDays(30),
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

        DoctorProfile::create([
            'user_id' => $user->id,
            'first_name' => 'Doctor',
            'last_name' => 'Test',
        ]);

        $patient = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Test',
        ]);

        return [$tenant, $user, $patient];
    }
}
