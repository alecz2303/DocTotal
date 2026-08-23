<?php

namespace Tests\Feature\Patients;

use App\Models\Patient;
use App\Models\PatientMedicalHistory;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MedicalHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_medical_history(): void
    {
        [$tenant, $user, $patient] = $this->createUserAndPatient();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::patients.show', [
                'uuid' => $patient->uuid,
            ])
            ->call('openMedicalHistoryModal')
            ->set('allergies_text', 'Penicilina')
            ->set('current_medications_text', 'Losartán')
            ->set('chronic_conditions_text', 'Hipertensión')
            ->set('surgeries_text', 'Apendicectomía')
            ->set('family_history_text', 'Diabetes materna')
            ->set('personal_history_text', 'Sin antecedentes relevantes')
            ->set('gynecological_history_text', '')
            ->set('habits_text', 'No fuma')
            ->set('other_notes', 'Paciente estable')
            ->call('saveMedicalHistory')
            ->assertRedirect(
                route('patients.show', [
                    'uuid' => $patient->uuid,
                ])
            );

        $history = PatientMedicalHistory::firstOrFail();

        $this->assertSame($tenant->id, $history->tenant_id);
        $this->assertSame($patient->id, $history->patient_id);
        $this->assertSame('Penicilina', $history->allergies_text);
        $this->assertSame('Losartán', $history->current_medications_text);
        $this->assertSame('Hipertensión', $history->chronic_conditions_text);
        $this->assertSame('Apendicectomía', $history->surgeries_text);
        $this->assertSame('Diabetes materna', $history->family_history_text);
        $this->assertSame(
            'Sin antecedentes relevantes',
            $history->personal_history_text
        );
        $this->assertNull($history->gynecological_history_text);
        $this->assertSame('No fuma', $history->habits_text);
        $this->assertSame('Paciente estable', $history->other_notes);
    }

    public function test_existing_medical_history_is_loaded_into_modal(): void
    {
        [$tenant, $user, $patient] = $this->createUserAndPatient();

        app(TenantContext::class)->set($tenant);

        PatientMedicalHistory::create([
            'patient_id' => $patient->id,
            'allergies_text' => 'Penicilina',
            'current_medications_text' => 'Metformina',
            'chronic_conditions_text' => 'Diabetes',
            'surgeries_text' => 'Ninguna',
            'family_history_text' => 'Hipertensión',
            'personal_history_text' => 'Ninguno',
            'gynecological_history_text' => null,
            'habits_text' => 'No fuma',
            'other_notes' => 'Sin observaciones',
        ]);

        Livewire::actingAs($user)
            ->test('pages::patients.show', [
                'uuid' => $patient->uuid,
            ])
            ->call('openMedicalHistoryModal')
            ->assertSet('allergies_text', 'Penicilina')
            ->assertSet('current_medications_text', 'Metformina')
            ->assertSet('chronic_conditions_text', 'Diabetes')
            ->assertSet('surgeries_text', 'Ninguna')
            ->assertSet('family_history_text', 'Hipertensión')
            ->assertSet('personal_history_text', 'Ninguno')
            ->assertSet('gynecological_history_text', '')
            ->assertSet('habits_text', 'No fuma')
            ->assertSet('other_notes', 'Sin observaciones')
            ->assertSet('showMedicalHistoryModal', true);
    }

    public function test_user_can_update_existing_medical_history_without_creating_duplicate(): void
    {
        [$tenant, $user, $patient] = $this->createUserAndPatient();

        app(TenantContext::class)->set($tenant);

        $history = PatientMedicalHistory::create([
            'patient_id' => $patient->id,
            'allergies_text' => 'Sin alergias',
        ]);

        Livewire::actingAs($user)
            ->test('pages::patients.show', [
                'uuid' => $patient->uuid,
            ])
            ->call('openMedicalHistoryModal')
            ->set('allergies_text', 'Aspirina')
            ->set('current_medications_text', 'Ninguno')
            ->call('saveMedicalHistory');

        $history->refresh();

        $this->assertSame('Aspirina', $history->allergies_text);
        $this->assertSame('Ninguno', $history->current_medications_text);

        $this->assertSame(
            1,
            PatientMedicalHistory::query()
                ->where('patient_id', $patient->id)
                ->count()
        );
    }

    public function test_medical_history_belongs_to_patient_and_tenant(): void
    {
        [$tenant, $user, $patient] = $this->createUserAndPatient();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::patients.show', [
                'uuid' => $patient->uuid,
            ])
            ->set('allergies_text', 'Ninguna')
            ->call('saveMedicalHistory');

        $history = PatientMedicalHistory::firstOrFail();

        $this->assertSame($tenant->id, $history->tenant_id);
        $this->assertSame($patient->id, $history->patient_id);
        $this->assertTrue($history->patient->is($patient));
    }

    public function test_medical_history_text_fields_have_length_validation(): void
    {
        [$tenant, $user, $patient] = $this->createUserAndPatient();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::patients.show', [
                'uuid' => $patient->uuid,
            ])
            ->set('allergies_text', str_repeat('A', 5001))
            ->call('saveMedicalHistory')
            ->assertHasErrors([
                'allergies_text',
            ]);

        $this->assertDatabaseCount(
            'patient_medical_histories',
            0
        );
    }

    public function test_empty_values_are_saved_as_null(): void
    {
        [$tenant, $user, $patient] = $this->createUserAndPatient();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::patients.show', [
                'uuid' => $patient->uuid,
            ])
            ->set('allergies_text', '')
            ->set('current_medications_text', '')
            ->set('chronic_conditions_text', '')
            ->set('surgeries_text', '')
            ->set('family_history_text', '')
            ->set('personal_history_text', '')
            ->set('gynecological_history_text', '')
            ->set('habits_text', '')
            ->set('other_notes', '')
            ->call('saveMedicalHistory');

        $history = PatientMedicalHistory::firstOrFail();

        $this->assertNull($history->allergies_text);
        $this->assertNull($history->current_medications_text);
        $this->assertNull($history->chronic_conditions_text);
        $this->assertNull($history->surgeries_text);
        $this->assertNull($history->family_history_text);
        $this->assertNull($history->personal_history_text);
        $this->assertNull($history->gynecological_history_text);
        $this->assertNull($history->habits_text);
        $this->assertNull($history->other_notes);
    }

    public function test_updating_history_for_one_patient_does_not_affect_another_patient(): void
    {
        [$tenant, $user, $patientA] = $this->createUserAndPatient();

        app(TenantContext::class)->set($tenant);

        $patientB = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'B',
        ]);

        $historyB = PatientMedicalHistory::create([
            'patient_id' => $patientB->id,
            'allergies_text' => 'Penicilina',
        ]);

        Livewire::actingAs($user)
            ->test('pages::patients.show', [
                'uuid' => $patientA->uuid,
            ])
            ->set('allergies_text', 'Aspirina')
            ->call('saveMedicalHistory');

        $historyB->refresh();

        $this->assertSame(
            'Penicilina',
            $historyB->allergies_text
        );

        $historyA = PatientMedicalHistory::query()
            ->where('patient_id', $patientA->id)
            ->firstOrFail();

        $this->assertSame(
            'Aspirina',
            $historyA->allergies_text
        );
    }

    public function test_user_cannot_open_patient_from_another_tenant_to_manage_history(): void
    {
        [$tenantA, $userA] = $this->createUser(
            tenantName: 'Tenant A',
            tenantSlug: 'tenant-a',
            email: 'a@example.com'
        );

        [$tenantB] = $this->createUser(
            tenantName: 'Tenant B',
            tenantSlug: 'tenant-b',
            email: 'b@example.com'
        );

        app(TenantContext::class)->set($tenantB);

        $patientB = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Tenant B',
        ]);

        PatientMedicalHistory::create([
            'patient_id' => $patientB->id,
            'allergies_text' => 'Penicilina',
        ]);

        app(TenantContext::class)->set($tenantA);

        $this->actingAs($userA)
            ->get(
                route('patients.show', [
                    'uuid' => $patientB->uuid,
                ])
            )
            ->assertNotFound();
    }

    private function createUserAndPatient(): array
    {
        [$tenant, $user] = $this->createUser();

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
        ]);

        return [
            $tenant,
            $user,
            $patient,
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
