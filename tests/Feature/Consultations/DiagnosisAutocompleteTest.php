<?php

namespace Tests\Feature\Consultations;

use App\Models\Consultation;
use App\Models\DiagnosisCatalog;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DiagnosisAutocompleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_autocomplete_searches_by_description(): void
    {
        [$tenant, $user, $consultation] = $this->createConsultationContext();

        app(TenantContext::class)->set($tenant);

        DiagnosisCatalog::create([
            'code' => 'R51X',
            'description' => 'CEFALEA',
            'active' => true,
        ]);

        DiagnosisCatalog::create([
            'code' => 'R42',
            'description' => 'MAREO',
            'active' => true,
        ]);

        Livewire::actingAs($user)
            ->test('pages::consultations.show', [
                'uuid' => $consultation->uuid,
            ])
            ->call('openDiagnosisModal')
            ->set('diagnosisSearch', 'cefa')
            ->assertSee('R51X')
            ->assertSee('CEFALEA')
            ->assertDontSee('MAREO');
    }

    public function test_autocomplete_searches_by_code(): void
    {
        [$tenant, $user, $consultation] = $this->createConsultationContext();

        app(TenantContext::class)->set($tenant);

        DiagnosisCatalog::create([
            'code' => 'G442',
            'description' => 'CEFALEA DEBIDA A TENSIÓN',
            'active' => true,
        ]);

        DiagnosisCatalog::create([
            'code' => 'R42',
            'description' => 'MAREO',
            'active' => true,
        ]);

        Livewire::actingAs($user)
            ->test('pages::consultations.show', [
                'uuid' => $consultation->uuid,
            ])
            ->call('openDiagnosisModal')
            ->set('diagnosisSearch', 'G44')
            ->assertSee('G442')
            ->assertSee('CEFALEA DEBIDA A TENSIÓN')
            ->assertDontSee('R42');
    }

    public function test_autocomplete_does_not_search_with_less_than_two_characters(): void
    {
        [$tenant, $user, $consultation] = $this->createConsultationContext();

        app(TenantContext::class)->set($tenant);

        DiagnosisCatalog::create([
            'code' => 'R51X',
            'description' => 'CEFALEA',
            'active' => true,
        ]);

        $component = Livewire::actingAs($user)
            ->test('pages::consultations.show', [
                'uuid' => $consultation->uuid,
            ])
            ->call('openDiagnosisModal')
            ->set('diagnosisSearch', 'c');

        $this->assertCount(
            0,
            $component->get('diagnosisResults')
        );
    }

    public function test_user_can_select_diagnosis_from_catalog(): void
    {
        [$tenant, $user, $consultation] = $this->createConsultationContext();

        app(TenantContext::class)->set($tenant);

        $catalog = DiagnosisCatalog::create([
            'code' => 'R51X',
            'description' => 'CEFALEA',
            'active' => true,
        ]);

        Livewire::actingAs($user)
            ->test('pages::consultations.show', [
                'uuid' => $consultation->uuid,
            ])
            ->set('diagnosisSearch', 'cefa')
            ->call('selectDiagnosis', $catalog->id)
            ->assertSet(
                'diagnosis_code',
                'R51X'
            )
            ->assertSet(
                'diagnosis_description',
                'CEFALEA'
            )
            ->assertSet(
                'diagnosisSearch',
                ''
            );
    }

    public function test_inactive_diagnoses_are_not_returned_in_autocomplete(): void
    {
        [$tenant, $user, $consultation] = $this->createConsultationContext();

        app(TenantContext::class)->set($tenant);

        DiagnosisCatalog::create([
            'code' => 'R51X',
            'description' => 'CEFALEA ACTIVA',
            'active' => true,
        ]);

        DiagnosisCatalog::create([
            'code' => 'R51Y',
            'description' => 'CEFALEA INACTIVA',
            'active' => false,
        ]);

        Livewire::actingAs($user)
            ->test('pages::consultations.show', [
                'uuid' => $consultation->uuid,
            ])
            ->call('openDiagnosisModal')
            ->set('diagnosisSearch', 'cefalea')
            ->assertSee('CEFALEA ACTIVA')
            ->assertDontSee('CEFALEA INACTIVA');
    }

    public function test_inactive_diagnosis_cannot_be_selected_directly(): void
    {
        [$tenant, $user, $consultation] = $this->createConsultationContext();

        app(TenantContext::class)->set($tenant);

        $catalog = DiagnosisCatalog::create([
            'code' => 'R51X',
            'description' => 'CEFALEA',
            'active' => false,
        ]);

        $this->expectException(
            \Illuminate\Database\Eloquent\ModelNotFoundException::class
        );

        Livewire::actingAs($user)
            ->test('pages::consultations.show', [
                'uuid' => $consultation->uuid,
            ])
            ->call(
                'selectDiagnosis',
                $catalog->id
            );
    }

    public function test_manual_diagnosis_can_still_be_saved_without_catalog_selection(): void
    {
        [$tenant, $user, $consultation] = $this->createConsultationContext();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::consultations.show', [
                'uuid' => $consultation->uuid,
            ])
            ->set(
                'diagnosis_code',
                'TEST-01'
            )
            ->set(
                'diagnosis_description',
                'Diagnóstico manual'
            )
            ->set(
                'diagnosis_notes',
                'Capturado manualmente.'
            )
            ->call('saveDiagnosis')
            ->assertRedirect(
                route('consultations.show', [
                    'uuid' => $consultation->uuid,
                ])
            );

        $this->assertDatabaseHas(
            'consultation_diagnoses',
            [
                'consultation_id' => $consultation->id,
                'code' => 'TEST-01',
                'description' => 'Diagnóstico manual',
                'notes' => 'Capturado manualmente.',
            ]
        );
    }

    public function test_catalog_selection_does_not_create_diagnosis_until_user_saves(): void
    {
        [$tenant, $user, $consultation] = $this->createConsultationContext();

        app(TenantContext::class)->set($tenant);

        $catalog = DiagnosisCatalog::create([
            'code' => 'R51X',
            'description' => 'CEFALEA',
            'active' => true,
        ]);

        Livewire::actingAs($user)
            ->test('pages::consultations.show', [
                'uuid' => $consultation->uuid,
            ])
            ->call(
                'selectDiagnosis',
                $catalog->id
            )
            ->assertSet(
                'diagnosis_code',
                'R51X'
            )
            ->assertSet(
                'diagnosis_description',
                'CEFALEA'
            );

        $this->assertDatabaseCount(
            'consultation_diagnoses',
            0
        );
    }

    public function test_search_is_limited_to_ten_results(): void
    {
        [$tenant, $user, $consultation] = $this->createConsultationContext();

        app(TenantContext::class)->set($tenant);

        foreach (range(1, 15) as $index) {
            DiagnosisCatalog::create([
                'code' => 'T' . str_pad(
                    (string) $index,
                    3,
                    '0',
                    STR_PAD_LEFT
                ),
                'description' =>
                'DIAGNÓSTICO PRUEBA ' . str_pad(
                    (string) $index,
                    2,
                    '0',
                    STR_PAD_LEFT
                ),
                'active' => true,
            ]);
        }

        Livewire::actingAs($user)
            ->test('pages::consultations.show', [
                'uuid' => $consultation->uuid,
            ])
            ->call('openDiagnosisModal')
            ->set('diagnosisSearch', 'prueba')

            // Los primeros 10 sí deben aparecer.
            ->assertSee('DIAGNÓSTICO PRUEBA 01')
            ->assertSee('DIAGNÓSTICO PRUEBA 10')

            // Del 11 en adelante no deben renderizarse
            // porque el autocomplete tiene limit(10).
            ->assertDontSee('DIAGNÓSTICO PRUEBA 11')
            ->assertDontSee('DIAGNÓSTICO PRUEBA 15');
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
