<?php

namespace Tests\Feature\Prescriptions;

use App\Models\Consultation;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PrescriptionFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_prescription_from_consultation(): void
    {
        [
            $tenant,
            $user,
            $doctor,
            $patient,
            $consultation,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::prescriptions.create', [
                'uuid' => $consultation->uuid,
            ])
            ->set('prescribed_at', '2026-08-23T01:03')
            ->set(
                'general_instructions',
                'Mantener buena hidrataci├│n y reposo.'
            )
            ->set('items.0.medication_name', 'Paracetamol')
            ->set('items.0.presentation', 'Tabletas 500 mg')
            ->set('items.0.dose', '1 tableta')
            ->set('items.0.frequency', 'Cada 8 horas')
            ->set('items.0.duration', '3 d├¡as')
            ->set(
                'items.0.instructions',
                'Tomar despu├®s de los alimentos.'
            )
            ->call('savePrescription');

        $prescription = Prescription::firstOrFail();

        $this->assertSame(
            $tenant->id,
            $prescription->tenant_id
        );

        $this->assertSame(
            $patient->id,
            $prescription->patient_id
        );

        $this->assertSame(
            $doctor->id,
            $prescription->doctor_profile_id
        );

        $this->assertSame(
            $consultation->id,
            $prescription->consultation_id
        );

        $this->assertNotNull(
            $prescription->uuid
        );

        $this->assertSame(
            'active',
            $prescription->status
        );

        $this->assertSame(
            'Mantener buena hidrataci├│n y reposo.',
            $prescription->general_instructions
        );
    }

    public function test_user_can_create_prescription_with_multiple_medications(): void
    {
        [
            $tenant,
            $user,,,
            $consultation,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::prescriptions.create', [
                'uuid' => $consultation->uuid,
            ])
            ->set('items.0.medication_name', 'Paracetamol')
            ->set('items.0.presentation', 'Tabletas 500 mg')
            ->set('items.0.dose', '1 tableta')
            ->set('items.0.frequency', 'Cada 8 horas')
            ->set('items.0.duration', '3 d├¡as')
            ->call('addMedication')
            ->set('items.1.medication_name', 'Ibuprofeno')
            ->set('items.1.presentation', 'Tabletas 400 mg')
            ->set('items.1.dose', '1 tableta')
            ->set('items.1.frequency', 'Cada 12 horas')
            ->set('items.1.duration', '3 d├¡as')
            ->call('savePrescription');

        $prescription = Prescription::firstOrFail();

        $this->assertCount(
            2,
            $prescription->items
        );

        $this->assertSame(
            'Paracetamol',
            $prescription->items[0]->medication_name
        );

        $this->assertSame(
            'Ibuprofeno',
            $prescription->items[1]->medication_name
        );
    }

    public function test_medication_name_is_required(): void
    {
        [
            $tenant,
            $user,,,
            $consultation,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::prescriptions.create', [
                'uuid' => $consultation->uuid,
            ])
            ->set('items.0.medication_name', '')
            ->call('savePrescription')
            ->assertHasErrors([
                'items.0.medication_name',
            ]);

        $this->assertDatabaseCount(
            'prescriptions',
            0
        );
    }

    public function test_prescription_requires_date(): void
    {
        [
            $tenant,
            $user,,,
            $consultation,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::prescriptions.create', [
                'uuid' => $consultation->uuid,
            ])
            ->set('prescribed_at', '')
            ->set('items.0.medication_name', 'Paracetamol')
            ->call('savePrescription')
            ->assertHasErrors([
                'prescribed_at',
            ]);

        $this->assertDatabaseCount(
            'prescriptions',
            0
        );
    }

    public function test_user_can_add_and_remove_medication_rows(): void
    {
        [
            $tenant,
            $user,,,
            $consultation,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::prescriptions.create', [
                'uuid' => $consultation->uuid,
            ])
            ->assertCount('items', 1)
            ->call('addMedication')
            ->assertCount('items', 2)
            ->call('addMedication')
            ->assertCount('items', 3)
            ->call('removeMedication', 1)
            ->assertCount('items', 2);
    }

    public function test_user_cannot_remove_last_medication_row(): void
    {
        [
            $tenant,
            $user,,,
            $consultation,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::prescriptions.create', [
                'uuid' => $consultation->uuid,
            ])
            ->assertCount('items', 1)
            ->call('removeMedication', 0)
            ->assertCount('items', 1);
    }

    public function test_prescription_items_keep_sort_order(): void
    {
        [
            $tenant,
            $user,,,
            $consultation,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::prescriptions.create', [
                'uuid' => $consultation->uuid,
            ])
            ->set('items.0.medication_name', 'Medicamento A')
            ->call('addMedication')
            ->set('items.1.medication_name', 'Medicamento B')
            ->call('addMedication')
            ->set('items.2.medication_name', 'Medicamento C')
            ->call('savePrescription');

        $items = PrescriptionItem::query()
            ->orderBy('sort_order')
            ->get();

        $this->assertSame(
            [
                'Medicamento A',
                'Medicamento B',
                'Medicamento C',
            ],
            $items->pluck('medication_name')->all()
        );

        $this->assertSame(
            [1, 2, 3],
            $items->pluck('sort_order')->all()
        );
    }

    public function test_user_can_view_prescription_detail(): void
    {
        [
            $tenant,
            $user,
            $doctor,
            $patient,
            $consultation,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $prescription = Prescription::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_id' => $consultation->id,
            'prescribed_at' => now(),
            'general_instructions' => 'Mantener hidrataci├│n.',
        ]);

        PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medication_name' => 'Paracetamol',
            'presentation' => 'Tabletas 500 mg',
            'dose' => '1 tableta',
            'frequency' => 'Cada 8 horas',
            'duration' => '3 d├¡as',
            'instructions' => 'Tomar despu├®s de alimentos.',
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->get(
                route('prescriptions.show', [
                    'uuid' => $prescription->uuid,
                ])
            )
            ->assertOk()
            ->assertSee('Receta médica')
            ->assertSee('Paracetamol')
            ->assertSee('Tabletas 500 mg')
            ->assertSee('1 tableta')
            ->assertSee('Cada 8 horas')
            ->assertSee('3 d├¡as')
            ->assertSee('Mantener hidrataci├│n.');
    }

    public function test_consultation_page_displays_prescriptions(): void
    {
        [
            $tenant,
            $user,
            $doctor,
            $patient,
            $consultation,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $prescription = Prescription::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_id' => $consultation->id,
            'prescribed_at' => now(),
        ]);

        PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medication_name' => 'Paracetamol',
        ]);

        $this->actingAs($user)
            ->get(
                route('consultations.show', [
                    'uuid' => $consultation->uuid,
                ])
            )
            ->assertOk()
            ->assertSee('Recetas')
            ->assertSee('medicamento(s)')
            ->assertSee('Ver receta');
    }

    public function test_user_cannot_create_prescription_from_consultation_of_another_tenant(): void
    {
        [
            $tenantA,
            $userA,
        ] = $this->createUser(
            tenantName: 'Tenant A',
            tenantSlug: 'tenant-a',
            email: 'a@example.com'
        );

        [
            $tenantB,,,,
            $consultationB,
        ] = $this->createContext(
            tenantName: 'Tenant B',
            tenantSlug: 'tenant-b',
            email: 'b@example.com'
        );

        app(TenantContext::class)->set($tenantA);

        $this->actingAs($userA)
            ->get(
                route('prescriptions.create', [
                    'uuid' => $consultationB->uuid,
                ])
            )
            ->assertNotFound();

        $this->assertDatabaseCount(
            'prescriptions',
            0
        );
    }

    public function test_user_cannot_view_prescription_from_another_tenant(): void
    {
        [
            $tenantA,
            $userA,
        ] = $this->createUser(
            tenantName: 'Tenant A',
            tenantSlug: 'tenant-a',
            email: 'a@example.com'
        );

        [
            $tenantB,,
            $doctorB,
            $patientB,
            $consultationB,
        ] = $this->createContext(
            tenantName: 'Tenant B',
            tenantSlug: 'tenant-b',
            email: 'b@example.com'
        );

        app(TenantContext::class)->set($tenantB);

        $prescriptionB = Prescription::create([
            'patient_id' => $patientB->id,
            'doctor_profile_id' => $doctorB->id,
            'consultation_id' => $consultationB->id,
            'prescribed_at' => now(),
        ]);

        app(TenantContext::class)->set($tenantA);

        $this->actingAs($userA)
            ->get(
                route('prescriptions.show', [
                    'uuid' => $prescriptionB->uuid,
                ])
            )
            ->assertNotFound();
    }

    public function test_prescription_is_associated_with_authenticated_doctor(): void
    {
        [
            $tenant,
            $user,
            $doctor,,
            $consultation,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::prescriptions.create', [
                'uuid' => $consultation->uuid,
            ])
            ->set('items.0.medication_name', 'Paracetamol')
            ->call('savePrescription');

        $prescription = Prescription::firstOrFail();

        $this->assertSame(
            $doctor->id,
            $prescription->doctor_profile_id
        );

        $this->assertTrue(
            $prescription->doctorProfile->is($doctor)
        );
    }

    public function test_user_cannot_create_prescription_from_draft_consultation(): void
    {
        [
            $tenant,
            $user,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $draftConsultation = Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now(),
            'status' => Consultation::STATUS_DRAFT,
        ]);

        $this->actingAs($user)
            ->get(
                route('prescriptions.create', [
                    'uuid' => $draftConsultation->uuid,
                ])
            )
            ->assertNotFound();

        $this->assertDatabaseCount(
            'prescriptions',
            0
        );
    }

    public function test_user_can_open_prescription_form_from_completed_consultation(): void
    {
        [
            $tenant,
            $user,,,
            $consultation,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $this->actingAs($user)
            ->get(
                route('prescriptions.create', [
                    'uuid' => $consultation->uuid,
                ])
            )
            ->assertOk()
            ->assertSee('Nueva receta');
    }

    private function createContext(
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
            'status' => Consultation::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        return [
            $tenant,
            $user,
            $doctor,
            $patient,
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

        return [
            $tenant,
            $user,
        ];
    }
}
