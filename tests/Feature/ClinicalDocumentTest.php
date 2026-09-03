<?php

namespace Tests\Feature;

use App\Models\ClinicalDocument;
use App\Models\Patient;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Actions\Patients\StoreClinicalDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\Consultation;
use App\Models\DoctorProfile;
use App\Models\User;
use App\Actions\Patients\DeleteClinicalDocument;

class ClinicalDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_clinical_document_generates_uuid_and_gets_current_tenant(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Juan',
            'last_name' => 'Perez',
        ]);

        $document = ClinicalDocument::create([
            'patient_id' => $patient->id,
            'title' => 'Estudio de laboratorio',
            'original_name' => 'laboratorio.pdf',
            'disk' => 'local',
            'path' => 'clinical-documents/example.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
        ]);

        $this->assertSame(
            $tenant->id,
            $document->tenant_id
        );

        $this->assertNotNull(
            $document->uuid
        );

        $this->assertSame(
            ClinicalDocument::CATEGORY_GENERAL,
            $document->category
        );
    }

    public function test_patient_can_have_clinical_documents(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Juan',
            'last_name' => 'Perez',
        ]);

        $document = ClinicalDocument::create([
            'patient_id' => $patient->id,
            'category' => ClinicalDocument::CATEGORY_LABORATORY,
            'title' => 'Biometria hematica',
            'original_name' => 'biometria.pdf',
            'disk' => 'local',
            'path' => 'clinical-documents/biometria.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 2048,
        ]);

        $this->assertTrue(
            $patient
                ->clinicalDocuments()
                ->get()
                ->contains($document)
        );

        $this->assertTrue(
            $document
                ->patient
                ->is($patient)
        );

        $this->assertSame(
            ClinicalDocument::CATEGORY_LABORATORY,
            $document->category
        );
    }

    public function test_clinical_documents_are_isolated_between_tenants(): void
    {
        $tenantA = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        $tenantB = Tenant::create([
            'name' => 'Tenant B',
            'slug' => 'tenant-b',
        ]);

        app(TenantContext::class)->set($tenantA);

        $patientA = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'A',
        ]);

        ClinicalDocument::create([
            'patient_id' => $patientA->id,
            'title' => 'Documento A',
            'original_name' => 'documento-a.pdf',
            'disk' => 'local',
            'path' => 'clinical-documents/documento-a.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1000,
        ]);

        app(TenantContext::class)->set($tenantB);

        $patientB = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'B',
        ]);

        ClinicalDocument::create([
            'patient_id' => $patientB->id,
            'title' => 'Documento B',
            'original_name' => 'documento-b.pdf',
            'disk' => 'local',
            'path' => 'clinical-documents/documento-b.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 2000,
        ]);

        app(TenantContext::class)->set($tenantA);

        $documents = ClinicalDocument::all();

        $this->assertCount(
            1,
            $documents
        );

        $this->assertSame(
            'Documento A',
            $documents->first()->title
        );

        $this->assertSame(
            $tenantA->id,
            $documents->first()->tenant_id
        );
    }

    public function test_clinical_document_cannot_be_created_without_tenant_context(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Juan',
            'last_name' => 'Perez',
        ]);

        app(TenantContext::class)->clear();

        $this->expectException(
            \RuntimeException::class
        );

        $this->expectExceptionMessage(
            'No tenant has been resolved for the current request.'
        );

        ClinicalDocument::create([
            'patient_id' => $patient->id,
            'title' => 'Documento sin tenant',
            'original_name' => 'documento.pdf',
            'disk' => 'local',
            'path' => 'clinical-documents/documento.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1000,
        ]);
    }

    public function test_clinical_document_exposes_supported_categories(): void
    {
        $this->assertSame(
            [
                ClinicalDocument::CATEGORY_GENERAL,
                ClinicalDocument::CATEGORY_LABORATORY,
                ClinicalDocument::CATEGORY_IMAGING,
                ClinicalDocument::CATEGORY_OTHER,
            ],
            ClinicalDocument::categories()
        );
    }

    public function test_clinical_document_can_be_stored_in_private_storage(): void
    {
        Storage::fake('local');

        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Juan',
            'last_name' => 'Perez',
        ]);

        $file = UploadedFile::fake()->create(
            'laboratorio.pdf',
            512,
            'application/pdf'
        );

        $document = app(StoreClinicalDocument::class)->handle(
            patient: $patient,
            file: $file,
            title: 'Estudio de laboratorio',
            category: ClinicalDocument::CATEGORY_LABORATORY
        );

        $this->assertDatabaseHas(
            'clinical_documents',
            [
                'id' => $document->id,
                'patient_id' => $patient->id,
                'tenant_id' => $tenant->id,
                'disk' => 'local',
                'original_name' => 'laboratorio.pdf',
                'category' =>
                ClinicalDocument::CATEGORY_LABORATORY,
            ]
        );

        Storage::disk('local')->assertExists(
            $document->path
        );
    }

    public function test_clinical_document_rejects_invalid_category(): void
    {
        Storage::fake('local');

        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Juan',
            'last_name' => 'Perez',
        ]);

        $file = UploadedFile::fake()->create(
            'documento.pdf',
            100,
            'application/pdf'
        );

        $this->expectException(
            \InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'Invalid clinical document category.'
        );

        app(StoreClinicalDocument::class)->handle(
            patient: $patient,
            file: $file,
            title: 'Documento',
            category: 'invalid'
        );
    }

    public function test_clinical_document_rejects_consultation_from_another_patient(): void
    {
        Storage::fake('local');

        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
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

        $patientA = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'A',
        ]);

        $patientB = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'B',
        ]);

        $consultation = Consultation::create([
            'patient_id' => $patientB->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now(),
            'reason' => 'Consulta general',
        ]);

        $file = UploadedFile::fake()->create(
            'documento.pdf',
            100,
            'application/pdf'
        );

        $this->expectException(
            \InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'The consultation does not belong to the patient.'
        );

        app(StoreClinicalDocument::class)->handle(
            patient: $patientA,
            file: $file,
            title: 'Documento',
            consultation: $consultation
        );
    }

    public function test_clinical_document_can_be_linked_to_consultation_from_same_patient(): void
    {
        Storage::fake('local');

        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
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
            'last_name' => 'A',
        ]);

        $consultation = Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now(),
            'reason' => 'Consulta general',
        ]);

        $file = UploadedFile::fake()->create(
            'resultado.pdf',
            100,
            'application/pdf'
        );

        $document = app(StoreClinicalDocument::class)->handle(
            patient: $patient,
            file: $file,
            title: 'Resultado de estudio',
            consultation: $consultation,
            uploadedBy: $user
        );

        $this->assertSame(
            $consultation->id,
            $document->consultation_id
        );

        $this->assertSame(
            $user->id,
            $document->uploaded_by
        );

        $this->assertTrue(
            $document->consultation->is($consultation)
        );

        $this->assertTrue(
            $consultation
                ->clinicalDocuments()
                ->get()
                ->contains($document)
        );

        Storage::disk('local')->assertExists(
            $document->path
        );
    }

    public function test_tenant_cannot_download_clinical_document_from_another_tenant(): void
    {
        Storage::fake('local');

        $tenantA = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        $tenantB = Tenant::create([
            'name' => 'Tenant B',
            'slug' => 'tenant-b',
        ]);

        app(TenantContext::class)->set($tenantA);

        $patientA = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'A',
        ]);

        $file = UploadedFile::fake()->create(
            'resultado.pdf',
            100,
            'application/pdf'
        );

        $document = app(StoreClinicalDocument::class)->handle(
            patient: $patientA,
            file: $file,
            title: 'Resultado privado'
        );

        $userB = User::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Usuario B',
            'email' => 'user-b@example.com',
            'password' => 'password123',
            'role' => 'owner',
        ]);

        app(TenantContext::class)->set($tenantB);

        $this->actingAs($userB);

        $response = $this->get(
            route(
                'clinical-documents.download',
                $document
            )
        );

        $response->assertNotFound();
    }

    public function test_tenant_can_download_its_own_clinical_document(): void
    {
        Storage::fake('local');

        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'trial_started_at' => now(),
            'trial_ends_at' => now()->addDays(30),
            'onboarding_completed_at' => now(),
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Usuario A',
            'email' => 'user-a@example.com',
            'password' => 'password123',
            'role' => 'owner',
        ]);

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'A',
        ]);

        $file = UploadedFile::fake()->create(
            'resultado-laboratorio.pdf',
            100,
            'application/pdf'
        );

        $document = app(StoreClinicalDocument::class)->handle(
            patient: $patient,
            file: $file,
            title: 'Resultado de laboratorio',
            uploadedBy: $user
        );

        $this->actingAs($user);

        $response = $this->get(
            route(
                'clinical-documents.download',
                $document
            )
        );

        $response->assertOk();

        $response->assertDownload(
            'resultado-laboratorio.pdf'
        );
    }

    public function test_clinical_document_download_returns_not_found_when_file_is_missing(): void
    {
        Storage::fake('local');

        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'trial_started_at' => now(),
            'trial_ends_at' => now()->addDays(30),
            'onboarding_completed_at' => now(),
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Usuario A',
            'email' => 'user-a@example.com',
            'password' => 'password123',
            'role' => 'owner',
        ]);

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'A',
        ]);

        $document = ClinicalDocument::create([
            'patient_id' => $patient->id,
            'title' => 'Documento faltante',
            'original_name' => 'faltante.pdf',
            'disk' => 'local',
            'path' => 'clinical-documents/missing/faltante.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 100,
        ]);

        $this->actingAs($user);

        $response = $this->get(
            route(
                'clinical-documents.download',
                $document
            )
        );

        $response->assertNotFound();
    }

    public function test_clinical_document_can_be_deleted_from_database_and_storage(): void
    {
        Storage::fake('local');

        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'A',
        ]);

        $file = UploadedFile::fake()->create(
            'resultado.pdf',
            100,
            'application/pdf'
        );

        $document = app(StoreClinicalDocument::class)->handle(
            patient: $patient,
            file: $file,
            title: 'Resultado'
        );

        Storage::disk('local')->assertExists(
            $document->path
        );

        app(DeleteClinicalDocument::class)->handle(
            $document
        );

        Storage::disk('local')->assertMissing(
            $document->path
        );

        $this->assertDatabaseMissing(
            'clinical_documents',
            [
                'id' => $document->id,
            ]
        );
    }

    public function test_clinical_document_record_can_be_deleted_when_physical_file_is_already_missing(): void
    {
        Storage::fake('local');

        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'A',
        ]);

        $document = ClinicalDocument::create([
            'patient_id' => $patient->id,
            'title' => 'Documento faltante',
            'original_name' => 'faltante.pdf',
            'disk' => 'local',
            'path' => 'clinical-documents/missing/faltante.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 100,
        ]);

        app(DeleteClinicalDocument::class)->handle(
            $document
        );

        $this->assertDatabaseMissing(
            'clinical_documents',
            [
                'id' => $document->id,
            ]
        );
    }

    public function test_tenant_can_delete_its_own_clinical_document(): void
    {
        Storage::fake('local');

        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'trial_started_at' => now(),
            'trial_ends_at' => now()->addDays(30),
            'onboarding_completed_at' => now(),
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Usuario A',
            'email' => 'delete-a@example.com',
            'password' => 'password123',
            'role' => 'owner',
        ]);

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'A',
        ]);

        $file = UploadedFile::fake()->create(
            'resultado.pdf',
            100,
            'application/pdf'
        );

        $document = app(StoreClinicalDocument::class)->handle(
            patient: $patient,
            file: $file,
            title: 'Resultado',
            uploadedBy: $user
        );

        $this->actingAs($user);

        $response = $this->delete(
            route(
                'clinical-documents.destroy',
                $document
            )
        );

        $response->assertRedirect(
            route(
                'patients.show',
                $patient
            )
        );

        Storage::disk('local')->assertMissing(
            $document->path
        );

        $this->assertDatabaseMissing(
            'clinical_documents',
            [
                'id' => $document->id,
            ]
        );
    }

    public function test_tenant_cannot_delete_clinical_document_from_another_tenant(): void
    {
        Storage::fake('local');

        $tenantA = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        $tenantB = Tenant::create([
            'name' => 'Tenant B',
            'slug' => 'tenant-b',
            'onboarding_completed_at' => now(),
        ]);

        app(TenantContext::class)->set($tenantA);

        $patientA = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'A',
        ]);

        $file = UploadedFile::fake()->create(
            'privado.pdf',
            100,
            'application/pdf'
        );

        $document = app(StoreClinicalDocument::class)->handle(
            patient: $patientA,
            file: $file,
            title: 'Documento privado'
        );

        $userB = User::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Usuario B',
            'email' => 'delete-b@example.com',
            'password' => 'password123',
            'role' => 'owner',
        ]);

        app(TenantContext::class)->set($tenantB);

        $this->actingAs($userB);

        $response = $this->delete(
            route(
                'clinical-documents.destroy',
                $document
            )
        );

        $response->assertNotFound();

        Storage::disk('local')->assertExists(
            $document->path
        );

        $this->assertDatabaseHas(
            'clinical_documents',
            [
                'id' => $document->id,
                'tenant_id' => $tenantA->id,
            ]
        );
    }

    public function test_tenant_can_view_its_own_clinical_document_inline(): void
    {
        Storage::fake('local');

        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'trial_started_at' => now(),
            'trial_ends_at' => now()->addDays(30),
            'onboarding_completed_at' => now(),
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Usuario A',
            'email' => 'view-a@example.com',
            'password' => 'password123',
            'role' => 'owner',
        ]);

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'A',
        ]);

        $file = UploadedFile::fake()->create(
            'estudio.pdf',
            100,
            'application/pdf'
        );

        $document = app(StoreClinicalDocument::class)->handle(
            patient: $patient,
            file: $file,
            title: 'Estudio',
            uploadedBy: $user
        );

        $this->actingAs($user);

        $response = $this->get(
            route(
                'clinical-documents.view',
                $document
            )
        );

        $response->assertOk();

        $response->assertHeader(
            'content-type',
            'application/pdf'
        );

        $response->assertHeader(
            'content-disposition'
        );
    }

    public function test_tenant_cannot_view_clinical_document_from_another_tenant(): void
    {
        Storage::fake('local');

        $tenantA = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        $tenantB = Tenant::create([
            'name' => 'Tenant B',
            'slug' => 'tenant-b',
            'onboarding_completed_at' => now(),
        ]);

        app(TenantContext::class)->set($tenantA);

        $patientA = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'A',
        ]);

        $file = UploadedFile::fake()->create(
            'privado.pdf',
            100,
            'application/pdf'
        );

        $document = app(StoreClinicalDocument::class)->handle(
            patient: $patientA,
            file: $file,
            title: 'Documento privado'
        );

        $userB = User::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Usuario B',
            'email' => 'view-b@example.com',
            'password' => 'password123',
            'role' => 'owner',
        ]);

        app(TenantContext::class)->set($tenantB);

        $this->actingAs($userB);

        $response = $this->get(
            route(
                'clinical-documents.view',
                $document
            )
        );

        $response->assertNotFound();
    }

    public function test_clinical_document_view_returns_not_found_when_file_is_missing(): void
    {
        Storage::fake('local');

        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'trial_started_at' => now(),
            'trial_ends_at' => now()->addDays(30),
            'onboarding_completed_at' => now(),
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Usuario A',
            'email' => 'view-missing@example.com',
            'password' => 'password123',
            'role' => 'owner',
        ]);

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'A',
        ]);

        $document = ClinicalDocument::create([
            'patient_id' => $patient->id,
            'uploaded_by' => $user->id,
            'category' => ClinicalDocument::CATEGORY_GENERAL,
            'title' => 'Documento faltante',
            'original_name' => 'faltante.pdf',
            'disk' => 'local',
            'path' => 'clinical-documents/faltante.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 100,
        ]);

        $this->actingAs($user);

        $response = $this->get(
            route(
                'clinical-documents.view',
                $document
            )
        );

        $response->assertNotFound();
    }

    public function test_store_clinical_document_rejects_patient_from_another_tenant(): void
    {
        Storage::fake('local');

        $tenantA = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        $tenantB = Tenant::create([
            'name' => 'Tenant B',
            'slug' => 'tenant-b',
        ]);

        app(TenantContext::class)->set($tenantA);

        $patientA = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'A',
        ]);

        app(TenantContext::class)->set($tenantB);

        $file = UploadedFile::fake()->create(
            'documento.pdf',
            100,
            'application/pdf'
        );

        try {
            app(StoreClinicalDocument::class)->handle(
                patient: $patientA,
                file: $file,
                title: 'Documento privado'
            );

            $this->fail(
                'Expected InvalidArgumentException was not thrown.'
            );
        } catch (\InvalidArgumentException $exception) {
            $this->assertSame(
                'The patient does not belong to the current tenant.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount(
            'clinical_documents',
            0
        );

        $this->assertSame(
            [],
            Storage::disk('local')->allFiles()
        );
    }

    public function test_store_clinical_document_rejects_uploader_from_another_tenant(): void
    {
        Storage::fake('local');

        $tenantA = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        $tenantB = Tenant::create([
            'name' => 'Tenant B',
            'slug' => 'tenant-b',
        ]);

        app(TenantContext::class)->set($tenantA);

        $patientA = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'A',
        ]);

        $userB = User::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Usuario B',
            'email' => 'foreign-uploader@example.com',
            'password' => 'password123',
            'role' => 'owner',
        ]);

        $file = UploadedFile::fake()->create(
            'documento.pdf',
            100,
            'application/pdf'
        );

        try {
            app(StoreClinicalDocument::class)->handle(
                patient: $patientA,
                file: $file,
                title: 'Documento privado',
                uploadedBy: $userB
            );

            $this->fail(
                'Expected InvalidArgumentException was not thrown.'
            );
        } catch (\InvalidArgumentException $exception) {
            $this->assertSame(
                'The uploader does not belong to the current tenant.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount(
            'clinical_documents',
            0
        );

        $this->assertSame(
            [],
            Storage::disk('local')->allFiles()
        );
    }

    public function test_store_clinical_document_rejects_unsupported_file_type(): void
    {
        Storage::fake('local');

        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'A',
        ]);

        $file = UploadedFile::fake()->create(
            'archivo.exe',
            100,
            'application/octet-stream'
        );

        try {
            app(StoreClinicalDocument::class)->handle(
                patient: $patient,
                file: $file,
                title: 'Archivo no permitido'
            );

            $this->fail(
                'Expected validation exception was not thrown.'
            );
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey(
                'file',
                $exception->errors()
            );
        }

        $this->assertDatabaseCount(
            'clinical_documents',
            0
        );

        $this->assertSame(
            [],
            Storage::disk('local')->allFiles()
        );
    }

    public function test_store_clinical_document_rejects_file_larger_than_ten_megabytes(): void
    {
        Storage::fake('local');

        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'A',
        ]);

        $file = UploadedFile::fake()->create(
            'archivo-grande.pdf',
            10241,
            'application/pdf'
        );

        try {
            app(StoreClinicalDocument::class)->handle(
                patient: $patient,
                file: $file,
                title: 'Archivo demasiado grande'
            );

            $this->fail(
                'Expected validation exception was not thrown.'
            );
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey(
                'file',
                $exception->errors()
            );
        }

        $this->assertDatabaseCount(
            'clinical_documents',
            0
        );

        $this->assertSame(
            [],
            Storage::disk('local')->allFiles()
        );
    }
}
