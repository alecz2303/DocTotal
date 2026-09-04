<?php

namespace Tests\Feature;

use App\Models\AuditEvent;
use App\Models\Consultation;
use App\Models\DoctorProfile;
use App\Models\LaboratoryStudy;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LaboratoryStudyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_structured_laboratory_study(): void
    {
        [$tenant, $user, $patient] = $this->createContext();
        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::laboratories.index', ['uuid' => $patient->uuid])
            ->call('createStudy')
            ->set('name', 'Biometría hemática')
            ->set('study_date', '2026-09-04')
            ->set('laboratory_name', 'Laboratorio Central')
            ->set('results.0.parameter_name', 'Hemoglobina')
            ->set('results.0.value', '14.2')
            ->set('results.0.unit', 'g/dL')
            ->set('results.0.reference_range', '12-16')
            ->call('saveStudy')
            ->assertHasNoErrors();

        $study = LaboratoryStudy::with('results')->firstOrFail();

        $this->assertSame($tenant->id, $study->tenant_id);
        $this->assertSame($patient->id, $study->patient_id);
        $this->assertSame('Biometría hemática', $study->name);
        $this->assertCount(1, $study->results);
        $this->assertSame('Hemoglobina', $study->results->first()->parameter_name);
        $this->assertSame('14.2', $study->results->first()->value);

        $this->assertDatabaseHas('audit_events', [
            'action' => 'laboratory_study.created',
            'auditable_type' => LaboratoryStudy::class,
            'auditable_id' => $study->id,
        ]);
    }

    public function test_study_can_have_multiple_results(): void
    {
        [$tenant, $user, $patient] = $this->createContext();
        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::laboratories.index', ['uuid' => $patient->uuid])
            ->call('createStudy')
            ->set('name', 'Química sanguínea')
            ->set('study_date', '2026-09-04')
            ->set('results.0.parameter_name', 'Glucosa')
            ->set('results.0.value', '92')
            ->call('addResult')
            ->set('results.1.parameter_name', 'Creatinina')
            ->set('results.1.value', '0.9')
            ->call('saveStudy')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('laboratory_results', 2);
    }

    public function test_laboratory_studies_are_isolated_by_tenant(): void
    {
        [$tenantA, $userA, $patientA] = $this->createContext(
            'Consultorio A',
            'consultorio-a',
            'a@example.com'
        );
        app(TenantContext::class)->set($tenantA);

        LaboratoryStudy::create([
            'patient_id' => $patientA->id,
            'name' => 'Estudio privado A',
            'study_date' => '2026-09-04',
        ]);

        [$tenantB, $userB, $patientB] = $this->createContext(
            'Consultorio B',
            'consultorio-b',
            'b@example.com'
        );
        app(TenantContext::class)->set($tenantB);

        LaboratoryStudy::create([
            'patient_id' => $patientB->id,
            'name' => 'Estudio privado B',
            'study_date' => '2026-09-04',
        ]);

        Livewire::actingAs($userB)
            ->test('pages::laboratories.index', ['uuid' => $patientB->uuid])
            ->assertSee('Estudio privado B')
            ->assertDontSee('Estudio privado A');

        $this->assertSame(1, LaboratoryStudy::count());
    }

    public function test_study_can_be_associated_with_consultation_of_same_patient(): void
    {
        [$tenant, $user, $patient, $doctor] = $this->createContext();
        app(TenantContext::class)->set($tenant);

        $consultation = Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now(),
            'reason' => 'Control',
        ]);

        Livewire::actingAs($user)
            ->test('pages::laboratories.index', ['uuid' => $patient->uuid])
            ->call('createStudy')
            ->set('name', 'Perfil lipídico')
            ->set('study_date', '2026-09-04')
            ->set('consultation_id', $consultation->id)
            ->set('results.0.parameter_name', 'Colesterol total')
            ->set('results.0.value', '180')
            ->call('saveStudy')
            ->assertHasNoErrors();

        $this->assertSame(
            $consultation->id,
            LaboratoryStudy::firstOrFail()->consultation_id
        );
    }

    public function test_consultation_from_another_patient_cannot_be_associated(): void
    {
        [$tenant, $user, $patient, $doctor] = $this->createContext();
        app(TenantContext::class)->set($tenant);

        $otherPatient = Patient::create([
            'first_name' => 'Otro',
            'last_name' => 'Paciente',
        ]);

        $otherConsultation = Consultation::create([
            'patient_id' => $otherPatient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now(),
            'reason' => 'Consulta ajena',
        ]);

        Livewire::actingAs($user)
            ->test('pages::laboratories.index', ['uuid' => $patient->uuid])
            ->call('createStudy')
            ->set('name', 'Estudio')
            ->set('study_date', '2026-09-04')
            ->set('consultation_id', $otherConsultation->id)
            ->set('results.0.parameter_name', 'Glucosa')
            ->set('results.0.value', '100')
            ->call('saveStudy')
            ->assertHasErrors(['consultation_id']);

        $this->assertDatabaseCount('laboratory_studies', 0);
    }

    public function test_user_can_edit_study_and_replace_structured_results(): void
    {
        [$tenant, $user, $patient] = $this->createContext();
        app(TenantContext::class)->set($tenant);

        $study = LaboratoryStudy::create([
            'patient_id' => $patient->id,
            'name' => 'Estudio original',
            'study_date' => '2026-09-04',
        ]);

        $study->results()->create([
            'parameter_name' => 'Glucosa',
            'value' => '90',
            'position' => 0,
        ]);

        Livewire::actingAs($user)
            ->test('pages::laboratories.index', ['uuid' => $patient->uuid])
            ->call('editStudy', $study->id)
            ->set('name', 'Estudio actualizado')
            ->set('results.0.value', '95')
            ->call('saveStudy')
            ->assertHasNoErrors();

        $this->assertSame('Estudio actualizado', $study->fresh()->name);
        $this->assertSame('95', $study->fresh()->results()->first()->value);

        $this->assertDatabaseHas('audit_events', [
            'action' => 'laboratory_study.updated',
            'auditable_type' => LaboratoryStudy::class,
            'auditable_id' => $study->id,
        ]);
    }

    public function test_user_can_delete_study_and_results_are_removed(): void
    {
        [$tenant, $user, $patient] = $this->createContext();
        app(TenantContext::class)->set($tenant);

        $study = LaboratoryStudy::create([
            'patient_id' => $patient->id,
            'name' => 'Temporal',
            'study_date' => '2026-09-04',
        ]);

        $study->results()->create([
            'parameter_name' => 'Glucosa',
            'value' => '90',
        ]);

        Livewire::actingAs($user)
            ->test('pages::laboratories.index', ['uuid' => $patient->uuid])
            ->call('deleteStudy', $study->id);

        $this->assertSoftDeleted('laboratory_studies', ['id' => $study->id]);
        $this->assertDatabaseMissing('laboratory_results', [
            'laboratory_study_id' => $study->id,
        ]);

        $this->assertDatabaseHas('audit_events', [
            'action' => 'laboratory_study.deleted',
            'auditable_type' => LaboratoryStudy::class,
            'auditable_id' => $study->id,
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

        $doctor = DoctorProfile::create([
            'user_id' => $user->id,
            'first_name' => 'Doctor',
            'last_name' => 'Test',
        ]);

        $patient = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Test',
        ]);

        return [$tenant, $user, $patient, $doctor];
    }


    public function test_bulk_results_can_be_pasted_and_converted_into_rows(): void
    {
        [$tenant, $user, $patient] = $this->createContext();

        $this->actingAs($user);

        Livewire::test('pages::laboratories.index', ['uuid' => $patient->uuid])
            ->call('createStudy')
            ->set('bulkResults', "Leucocitos\t16.34\tmiles/µL\t4.1-12.6\nHemoglobina\t12.7\tg/dL\t12.0-16.0")
            ->call('importBulkResults')
            ->assertSet('showBulkResults', false)
            ->assertSet('results.0.parameter_name', 'Leucocitos')
            ->assertSet('results.0.value', '16.34')
            ->assertSet('results.1.parameter_name', 'Hemoglobina')
            ->assertSet('results.1.reference_range', '12.0-16.0');
    }

    public function test_bulk_results_accept_pipe_delimited_text_and_optional_columns(): void
    {
        [$tenant, $user, $patient] = $this->createContext();

        $this->actingAs($user);

        Livewire::test('pages::laboratories.index', ['uuid' => $patient->uuid])
            ->call('createStudy')
            ->set('bulkResults', "Tífico O|Negativo||\nGlucosa|115|mg/dL|55-99")
            ->call('importBulkResults')
            ->assertSet('results.0.parameter_name', 'Tífico O')
            ->assertSet('results.0.value', 'Negativo')
            ->assertSet('results.0.unit', '')
            ->assertSet('results.1.unit', 'mg/dL');
    }

    public function test_bulk_results_reject_unrecognized_text_without_destroying_existing_rows(): void
    {
        [$tenant, $user, $patient] = $this->createContext();

        $this->actingAs($user);

        Livewire::test('pages::laboratories.index', ['uuid' => $patient->uuid])
            ->call('createStudy')
            ->set('results.0.parameter_name', 'Hemoglobina')
            ->set('results.0.value', '12.7')
            ->set('bulkResults', 'texto sin columnas')
            ->call('importBulkResults')
            ->assertHasErrors('bulkResults')
            ->assertSet('results.0.parameter_name', 'Hemoglobina')
            ->assertSet('results.0.value', '12.7');
    }

}
