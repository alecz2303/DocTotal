<?php

namespace Tests\Feature\Prescriptions;

use App\Models\Consultation;
use App\Models\DoctorProfile;
use App\Models\MedicationCatalog;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MedicationAutocompleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_autocomplete_searches_medications_by_name(): void
    {
        [$tenant, $user, $consultation] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        MedicationCatalog::create([
            'code' => 'PARA-500',
            'name' => 'Paracetamol',
            'presentation' => 'Tabletas 500 mg',
            'active' => true,
        ]);

        MedicationCatalog::create([
            'code' => 'IBU-400',
            'name' => 'Ibuprofeno',
            'presentation' => 'Tabletas 400 mg',
            'active' => true,
        ]);

        Livewire::actingAs($user)
            ->test('pages::prescriptions.create', [
                'uuid' => $consultation->uuid,
            ])
            ->set('items.0.search', 'para')
            ->assertSee('Paracetamol')
            ->assertSee('Tabletas 500 mg')
            ->assertDontSee('Ibuprofeno');
    }

    public function test_autocomplete_searches_medications_by_code(): void
    {
        [$tenant, $user, $consultation] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        MedicationCatalog::create([
            'code' => 'PARA-500',
            'name' => 'Paracetamol',
            'presentation' => 'Tabletas 500 mg',
            'active' => true,
        ]);

        MedicationCatalog::create([
            'code' => 'IBU-400',
            'name' => 'Ibuprofeno',
            'presentation' => 'Tabletas 400 mg',
            'active' => true,
        ]);

        $component = Livewire::actingAs($user)
            ->test('pages::prescriptions.create', [
                'uuid' => $consultation->uuid,
            ]);

        $component->set('items.0.search', 'IBU');

        $results = $component->instance()
            ->medicationResults(0);

        $this->assertCount(1, $results);

        $this->assertSame(
            'Ibuprofeno',
            $results->first()->name
        );

        $this->assertSame(
            'IBU-400',
            $results->first()->code
        );
    }

    public function test_autocomplete_searches_medications_by_presentation(): void
    {
        [$tenant, $user, $consultation] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        MedicationCatalog::create([
            'code' => 'PARA-500',
            'name' => 'Paracetamol',
            'presentation' => 'Tabletas 500 mg',
            'active' => true,
        ]);

        MedicationCatalog::create([
            'code' => 'PARA-SUS',
            'name' => 'Paracetamol',
            'presentation' => 'Suspensión 160 mg/5 mL',
            'active' => true,
        ]);

        $component = Livewire::actingAs($user)
            ->test('pages::prescriptions.create', [
                'uuid' => $consultation->uuid,
            ]);

        $component->set(
            'items.0.search',
            'suspensión'
        );

        $results = $component->instance()
            ->medicationResults(0);

        $this->assertCount(1, $results);

        $this->assertSame(
            'Paracetamol',
            $results->first()->name
        );

        $this->assertSame(
            'Suspensión 160 mg/5 mL',
            $results->first()->presentation
        );
    }

    public function test_autocomplete_does_not_search_with_less_than_two_characters(): void
    {
        [$tenant, $user, $consultation] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        MedicationCatalog::create([
            'code' => 'PARA-500',
            'name' => 'Paracetamol',
            'presentation' => 'Tabletas 500 mg',
            'active' => true,
        ]);

        $component = Livewire::actingAs($user)
            ->test('pages::prescriptions.create', [
                'uuid' => $consultation->uuid,
            ]);

        $component->set('items.0.search', 'p');

        $results = $component->instance()
            ->medicationResults(0);

        $this->assertCount(0, $results);
    }

    public function test_user_can_select_medication_from_catalog(): void
    {
        [$tenant, $user, $consultation] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $medication = MedicationCatalog::create([
            'code' => 'PARA-500',
            'name' => 'Paracetamol',
            'presentation' => 'Tabletas 500 mg',
            'active' => true,
        ]);

        Livewire::actingAs($user)
            ->test('pages::prescriptions.create', [
                'uuid' => $consultation->uuid,
            ])
            ->set('items.0.search', 'para')
            ->call(
                'selectMedication',
                0,
                $medication->id
            )
            ->assertSet(
                'items.0.medication_catalog_id',
                $medication->id
            )
            ->assertSet(
                'items.0.medication_name',
                'Paracetamol'
            )
            ->assertSet(
                'items.0.presentation',
                'Tabletas 500 mg'
            )
            ->assertSet(
                'items.0.search',
                ''
            );
    }

    public function test_selecting_medication_does_not_fill_dose_frequency_or_duration(): void
    {
        [$tenant, $user, $consultation] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $medication = MedicationCatalog::create([
            'code' => 'PARA-500',
            'name' => 'Paracetamol',
            'presentation' => 'Tabletas 500 mg',
            'active' => true,
        ]);

        Livewire::actingAs($user)
            ->test('pages::prescriptions.create', [
                'uuid' => $consultation->uuid,
            ])
            ->call(
                'selectMedication',
                0,
                $medication->id
            )
            ->assertSet(
                'items.0.dose',
                ''
            )
            ->assertSet(
                'items.0.frequency',
                ''
            )
            ->assertSet(
                'items.0.duration',
                ''
            );
    }

    public function test_inactive_medications_are_not_returned(): void
    {
        [$tenant, $user, $consultation] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        MedicationCatalog::create([
            'code' => 'PARA-ACTIVE',
            'name' => 'Paracetamol activo',
            'presentation' => 'Tabletas 500 mg',
            'active' => true,
        ]);

        MedicationCatalog::create([
            'code' => 'PARA-INACTIVE',
            'name' => 'Paracetamol inactivo',
            'presentation' => 'Tabletas 500 mg',
            'active' => false,
        ]);

        Livewire::actingAs($user)
            ->test('pages::prescriptions.create', [
                'uuid' => $consultation->uuid,
            ])
            ->set('items.0.search', 'paracetamol')
            ->assertSee('Paracetamol activo')
            ->assertDontSee('Paracetamol inactivo');
    }

    public function test_inactive_medication_cannot_be_selected_directly(): void
    {
        [$tenant, $user, $consultation] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $medication = MedicationCatalog::create([
            'code' => 'PARA-INACTIVE',
            'name' => 'Paracetamol',
            'presentation' => 'Tabletas 500 mg',
            'active' => false,
        ]);

        $this->expectException(
            \Illuminate\Database\Eloquent\ModelNotFoundException::class
        );

        Livewire::actingAs($user)
            ->test('pages::prescriptions.create', [
                'uuid' => $consultation->uuid,
            ])
            ->call(
                'selectMedication',
                0,
                $medication->id
            );
    }

    public function test_manual_medication_can_still_be_saved_without_catalog_selection(): void
    {
        [$tenant, $user, $consultation] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::prescriptions.create', [
                'uuid' => $consultation->uuid,
            ])
            ->set(
                'items.0.medication_name',
                'Medicamento manual'
            )
            ->set(
                'items.0.presentation',
                'Presentación manual'
            )
            ->set(
                'items.0.dose',
                '1 tableta'
            )
            ->call('savePrescription');

        $prescription = Prescription::firstOrFail();

        $item = $prescription->items()
            ->firstOrFail();

        $this->assertSame(
            'Medicamento manual',
            $item->medication_name
        );

        $this->assertSame(
            'Presentación manual',
            $item->presentation
        );
    }

    public function test_catalog_selection_does_not_create_prescription_until_saved(): void
    {
        [$tenant, $user, $consultation] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $medication = MedicationCatalog::create([
            'code' => 'PARA-500',
            'name' => 'Paracetamol',
            'presentation' => 'Tabletas 500 mg',
            'active' => true,
        ]);

        Livewire::actingAs($user)
            ->test('pages::prescriptions.create', [
                'uuid' => $consultation->uuid,
            ])
            ->call(
                'selectMedication',
                0,
                $medication->id
            )
            ->assertSet(
                'items.0.medication_name',
                'Paracetamol'
            );

        $this->assertDatabaseCount(
            'prescriptions',
            0
        );

        $this->assertDatabaseCount(
            'prescription_items',
            0
        );
    }

    public function test_medication_search_is_limited_to_ten_results(): void
    {
        [$tenant, $user, $consultation] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        foreach (range(1, 15) as $index) {
            MedicationCatalog::create([
                'code' => 'TEST-' . str_pad(
                    (string) $index,
                    2,
                    '0',
                    STR_PAD_LEFT
                ),
                'name' => 'Medicamento Prueba ' . $index,
                'presentation' => 'Tabletas',
                'active' => true,
            ]);
        }

        $component = Livewire::actingAs($user)
            ->test('pages::prescriptions.create', [
                'uuid' => $consultation->uuid,
            ]);

        $component->set(
            'items.0.search',
            'Prueba'
        );

        $results = $component->instance()
            ->medicationResults(0);

        $this->assertCount(
            10,
            $results
        );
    }

    public function test_each_medication_row_has_independent_search(): void
    {
        [$tenant, $user, $consultation] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $paracetamol = MedicationCatalog::create([
            'code' => 'PARA-500',
            'name' => 'Paracetamol',
            'presentation' => 'Tabletas 500 mg',
            'active' => true,
        ]);

        $ibuprofen = MedicationCatalog::create([
            'code' => 'IBU-400',
            'name' => 'Ibuprofeno',
            'presentation' => 'Tabletas 400 mg',
            'active' => true,
        ]);

        Livewire::actingAs($user)
            ->test('pages::prescriptions.create', [
                'uuid' => $consultation->uuid,
            ])
            ->call('addMedication')
            ->call(
                'selectMedication',
                0,
                $paracetamol->id
            )
            ->call(
                'selectMedication',
                1,
                $ibuprofen->id
            )
            ->assertSet(
                'items.0.medication_name',
                'Paracetamol'
            )
            ->assertSet(
                'items.1.medication_name',
                'Ibuprofeno'
            )
            ->assertSet(
                'items.0.presentation',
                'Tabletas 500 mg'
            )
            ->assertSet(
                'items.1.presentation',
                'Tabletas 400 mg'
            );
    }

    private function createContext(): array
    {
        $tenant = Tenant::create([
            'name' => 'Consultorio Test',
            'slug' => 'consultorio-test',
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
            'last_name' => 'Test',
        ]);

        $consultation = Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now(),
            'status' => Consultation::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        return [
            $tenant,
            $user,
            $consultation,
        ];
    }
}
