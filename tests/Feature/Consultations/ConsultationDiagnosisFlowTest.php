<?php

namespace Tests\Feature\Consultations;

use App\Models\Consultation;
use App\Models\ConsultationDiagnosis;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ConsultationDiagnosisFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_diagnosis(): void
    {
        [$tenant, $user, $consultation] = $this->createConsultationContext();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::consultations.show', [
                'uuid' => $consultation->uuid,
            ])
            ->call('openDiagnosisModal')
            ->set('diagnosis_code', 'R51.9')
            ->set(
                'diagnosis_description',
                'Cefalea no especificada'
            )
            ->set(
                'diagnosis_notes',
                'Diagnóstico principal de la consulta.'
            )
            ->set(
                'diagnosis_is_primary',
                true
            )
            ->call('saveDiagnosis')
            ->assertRedirect(
                route('consultations.show', [
                    'uuid' => $consultation->uuid,
                ])
            );

        $diagnosis = ConsultationDiagnosis::firstOrFail();

        $this->assertSame(
            $tenant->id,
            $diagnosis->tenant_id
        );

        $this->assertSame(
            $consultation->id,
            $diagnosis->consultation_id
        );

        $this->assertSame(
            'R51.9',
            $diagnosis->code
        );

        $this->assertSame(
            'Cefalea no especificada',
            $diagnosis->description
        );

        $this->assertSame(
            'Diagnóstico principal de la consulta.',
            $diagnosis->notes
        );

        $this->assertTrue(
            $diagnosis->is_primary
        );
    }

    public function test_diagnosis_description_is_required(): void
    {
        [$tenant, $user, $consultation] = $this->createConsultationContext();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::consultations.show', [
                'uuid' => $consultation->uuid,
            ])
            ->call('openDiagnosisModal')
            ->set('diagnosis_description', '')
            ->call('saveDiagnosis')
            ->assertHasErrors([
                'diagnosis_description',
            ]);

        $this->assertDatabaseCount(
            'consultation_diagnoses',
            0
        );
    }

    public function test_user_can_edit_diagnosis(): void
    {
        [$tenant, $user, $consultation] = $this->createConsultationContext();

        app(TenantContext::class)->set($tenant);

        $diagnosis = ConsultationDiagnosis::create([
            'consultation_id' => $consultation->id,
            'code' => 'R42',
            'description' => 'Mareo',
            'is_primary' => false,
            'notes' => null,
        ]);

        Livewire::actingAs($user)
            ->test('pages::consultations.show', [
                'uuid' => $consultation->uuid,
            ])
            ->call('editDiagnosis', $diagnosis->id)
            ->assertSet(
                'editingDiagnosisId',
                $diagnosis->id
            )
            ->assertSet(
                'diagnosis_code',
                'R42'
            )
            ->assertSet(
                'diagnosis_description',
                'Mareo'
            )
            ->set(
                'diagnosis_code',
                'R51.9'
            )
            ->set(
                'diagnosis_description',
                'Cefalea no especificada'
            )
            ->set(
                'diagnosis_notes',
                'Diagnóstico actualizado.'
            )
            ->set(
                'diagnosis_is_primary',
                true
            )
            ->call('saveDiagnosis')
            ->assertRedirect(
                route('consultations.show', [
                    'uuid' => $consultation->uuid,
                ])
            );

        $diagnosis->refresh();

        $this->assertSame(
            'R51.9',
            $diagnosis->code
        );

        $this->assertSame(
            'Cefalea no especificada',
            $diagnosis->description
        );

        $this->assertSame(
            'Diagnóstico actualizado.',
            $diagnosis->notes
        );

        $this->assertTrue(
            $diagnosis->is_primary
        );
    }

    public function test_only_one_diagnosis_can_be_primary_per_consultation(): void
    {
        [$tenant, $user, $consultation] = $this->createConsultationContext();

        app(TenantContext::class)->set($tenant);

        $firstDiagnosis = ConsultationDiagnosis::create([
            'consultation_id' => $consultation->id,
            'code' => 'R51.9',
            'description' => 'Cefalea',
            'is_primary' => true,
        ]);

        Livewire::actingAs($user)
            ->test('pages::consultations.show', [
                'uuid' => $consultation->uuid,
            ])
            ->set('diagnosis_code', 'R42')
            ->set(
                'diagnosis_description',
                'Mareo'
            )
            ->set(
                'diagnosis_is_primary',
                true
            )
            ->call('saveDiagnosis');

        $firstDiagnosis->refresh();

        $secondDiagnosis = ConsultationDiagnosis::query()
            ->where('consultation_id', $consultation->id)
            ->where('description', 'Mareo')
            ->firstOrFail();

        $this->assertFalse(
            $firstDiagnosis->is_primary
        );

        $this->assertTrue(
            $secondDiagnosis->is_primary
        );

        $this->assertSame(
            1,
            ConsultationDiagnosis::query()
                ->where(
                    'consultation_id',
                    $consultation->id
                )
                ->where(
                    'is_primary',
                    true
                )
                ->count()
        );
    }

    public function test_editing_diagnosis_as_primary_removes_previous_primary(): void
    {
        [$tenant, $user, $consultation] = $this->createConsultationContext();

        app(TenantContext::class)->set($tenant);

        $primary = ConsultationDiagnosis::create([
            'consultation_id' => $consultation->id,
            'description' => 'Principal original',
            'is_primary' => true,
        ]);

        $secondary = ConsultationDiagnosis::create([
            'consultation_id' => $consultation->id,
            'description' => 'Secundario',
            'is_primary' => false,
        ]);

        Livewire::actingAs($user)
            ->test('pages::consultations.show', [
                'uuid' => $consultation->uuid,
            ])
            ->call(
                'editDiagnosis',
                $secondary->id
            )
            ->set(
                'diagnosis_is_primary',
                true
            )
            ->call('saveDiagnosis');

        $primary->refresh();
        $secondary->refresh();

        $this->assertFalse(
            $primary->is_primary
        );

        $this->assertTrue(
            $secondary->is_primary
        );
    }

    public function test_primary_change_does_not_affect_other_consultations(): void
    {
        [$tenant, $user, $consultationA] = $this->createConsultationContext();

        app(TenantContext::class)->set($tenant);

        $doctor = $consultationA->doctorProfile;
        $patient = $consultationA->patient;

        $consultationB = Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now()->addHour(),
        ]);

        $diagnosisB = ConsultationDiagnosis::create([
            'consultation_id' => $consultationB->id,
            'description' => 'Diagnóstico consulta B',
            'is_primary' => true,
        ]);

        Livewire::actingAs($user)
            ->test('pages::consultations.show', [
                'uuid' => $consultationA->uuid,
            ])
            ->set(
                'diagnosis_description',
                'Diagnóstico consulta A'
            )
            ->set(
                'diagnosis_is_primary',
                true
            )
            ->call('saveDiagnosis');

        $diagnosisB->refresh();

        $this->assertTrue(
            $diagnosisB->is_primary
        );
    }

    public function test_user_can_delete_diagnosis(): void
    {
        [$tenant, $user, $consultation] = $this->createConsultationContext();

        app(TenantContext::class)->set($tenant);

        $diagnosis = ConsultationDiagnosis::create([
            'consultation_id' => $consultation->id,
            'description' => 'Diagnóstico a eliminar',
        ]);

        Livewire::actingAs($user)
            ->test('pages::consultations.show', [
                'uuid' => $consultation->uuid,
            ])
            ->call(
                'deleteDiagnosis',
                $diagnosis->id
            )
            ->assertRedirect(
                route('consultations.show', [
                    'uuid' => $consultation->uuid,
                ])
            );

        $this->assertDatabaseMissing(
            'consultation_diagnoses',
            [
                'id' => $diagnosis->id,
            ]
        );
    }

    public function test_user_cannot_edit_diagnosis_from_another_consultation(): void
    {
        [$tenant, $user, $consultationA] = $this->createConsultationContext();

        app(TenantContext::class)->set($tenant);

        $consultationB = Consultation::create([
            'patient_id' => $consultationA->patient_id,
            'doctor_profile_id' => $consultationA->doctor_profile_id,
            'consultation_at' => now()->addHour(),
        ]);

        $diagnosisB = ConsultationDiagnosis::create([
            'consultation_id' => $consultationB->id,
            'description' => 'Diagnóstico B',
        ]);

        $this->expectException(
            ModelNotFoundException::class
        );

        Livewire::actingAs($user)
            ->test('pages::consultations.show', [
                'uuid' => $consultationA->uuid,
            ])
            ->call(
                'editDiagnosis',
                $diagnosisB->id
            );
    }

    public function test_user_cannot_delete_diagnosis_from_another_consultation(): void
    {
        [$tenant, $user, $consultationA] = $this->createConsultationContext();

        app(TenantContext::class)->set($tenant);

        $consultationB = Consultation::create([
            'patient_id' => $consultationA->patient_id,
            'doctor_profile_id' => $consultationA->doctor_profile_id,
            'consultation_at' => now()->addHour(),
        ]);

        $diagnosisB = ConsultationDiagnosis::create([
            'consultation_id' => $consultationB->id,
            'description' => 'Diagnóstico B',
        ]);

        try {
            Livewire::actingAs($user)
                ->test('pages::consultations.show', [
                    'uuid' => $consultationA->uuid,
                ])
                ->call(
                    'deleteDiagnosis',
                    $diagnosisB->id
                );

            $this->fail(
                'Se esperaba ModelNotFoundException.'
            );
        } catch (ModelNotFoundException $e) {
            $this->assertTrue(true);
        }

        $this->assertDatabaseHas(
            'consultation_diagnoses',
            [
                'id' => $diagnosisB->id,
            ]
        );
    }

    public function test_diagnoses_are_isolated_between_tenants_in_ui(): void
    {
        [$tenantA, $userA] = $this->createUser(
            tenantName: 'Tenant A',
            tenantSlug: 'tenant-a',
            email: 'a@example.com'
        );

        [$tenantB,, $consultationB] = $this->createConsultationContext(
            tenantName: 'Tenant B',
            tenantSlug: 'tenant-b',
            email: 'b@example.com'
        );

        app(TenantContext::class)->set($tenantB);

        ConsultationDiagnosis::create([
            'consultation_id' => $consultationB->id,
            'description' => 'Diagnóstico privado',
        ]);

        app(TenantContext::class)->set($tenantA);

        $this->actingAs($userA)
            ->get(
                route('consultations.show', [
                    'uuid' => $consultationB->uuid,
                ])
            )
            ->assertNotFound();
    }


    public function test_diagnosis_can_be_created_from_consultation_create_while_draft(): void
    {
        [$tenant, $user, $consultation] = $this->createConsultationContext();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::consultations.create', [
                'uuid' => $consultation->patient->uuid,
            ])
            ->call('openDiagnosisModal')
            ->set('diagnosis_code', 'R51.9')
            ->set('diagnosis_description', 'Cefalea no especificada')
            ->set('diagnosis_is_primary', true)
            ->call('saveDiagnosis')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('consultation_diagnoses', [
            'consultation_id' => $consultation->id,
            'code' => 'R51.9',
            'description' => 'Cefalea no especificada',
            'is_primary' => true,
        ]);

        $consultation->refresh();

        $this->assertSame(
            Consultation::STATUS_DRAFT,
            $consultation->status
        );
    }

    public function test_completed_consultation_rejects_diagnosis_creation(): void
    {
        [$tenant, $user, $consultation] =
            $this->createConsultationContext();

        app(TenantContext::class)->set($tenant);

        $consultation->complete();

        Livewire::actingAs($user)
            ->test('pages::consultations.show', [
                'uuid' => $consultation->uuid,
            ])
            ->call('openDiagnosisModal')
            ->assertStatus(403);

        $this->assertDatabaseCount(
            'consultation_diagnoses',
            0
        );
    }

    public function test_completed_consultation_rejects_diagnosis_editing(): void
    {
        [$tenant, $user, $consultation] =
            $this->createConsultationContext();

        app(TenantContext::class)->set($tenant);

        $diagnosis = ConsultationDiagnosis::create([
            'consultation_id' => $consultation->id,
            'code' => 'R42',
            'description' => 'Mareo',
            'is_primary' => true,
        ]);

        $consultation->complete();

        Livewire::actingAs($user)
            ->test('pages::consultations.show', [
                'uuid' => $consultation->uuid,
            ])
            ->call(
                'editDiagnosis',
                $diagnosis->id
            )
            ->assertStatus(403);

        $diagnosis->refresh();

        $this->assertSame(
            'Mareo',
            $diagnosis->description
        );
    }

    public function test_completed_consultation_rejects_diagnosis_deletion(): void
    {
        [$tenant, $user, $consultation] =
            $this->createConsultationContext();

        app(TenantContext::class)->set($tenant);

        $diagnosis = ConsultationDiagnosis::create([
            'consultation_id' => $consultation->id,
            'description' => 'Diagnóstico protegido',
        ]);

        $consultation->complete();

        Livewire::actingAs($user)
            ->test('pages::consultations.show', [
                'uuid' => $consultation->uuid,
            ])
            ->call(
                'deleteDiagnosis',
                $diagnosis->id
            )
            ->assertStatus(403);

        $this->assertDatabaseHas(
            'consultation_diagnoses',
            [
                'id' => $diagnosis->id,
            ]
        );
    }

    private function createConsultationContext(
        string $tenantName = 'Consultorio Test',
        string $tenantSlug = 'consultorio-test',
        string $email = 'doctor@example.com',
    ): array {
        [$tenant, $user] = $this->createUser(
            tenantName: $tenantName,
            tenantSlug: $tenantSlug,
            email: $email
        );

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

    private function createUser(
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

        return [
            $tenant,
            $user,
        ];
    }
}
