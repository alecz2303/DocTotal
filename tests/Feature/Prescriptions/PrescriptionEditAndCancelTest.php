<?php

namespace Tests\Feature\Prescriptions;

use App\Models\Consultation;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Specialty;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use App\Models\PracticeProfile;

class PrescriptionEditAndCancelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_edit_existing_prescription(): void
    {
        [
            $tenant,
            $user,
            $doctor,
            $patient,
            $consultation,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $prescription = $this->createPrescription(
            $doctor,
            $patient,
            $consultation
        );

        PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medication_name' => 'Paracetamol',
            'presentation' => 'Tabletas 500 mg',
            'dose' => '1 tableta',
            'frequency' => 'Cada 8 horas',
            'duration' => '3 días',
            'instructions' => 'Después de alimentos.',
            'sort_order' => 1,
        ]);

        Livewire::actingAs($user)
            ->test('pages::prescriptions.edit', [
                'uuid' => $prescription->uuid,
            ])
            ->set(
                'general_instructions',
                'Nueva indicación general.'
            )
            ->set(
                'items.0.medication_name',
                'Ibuprofeno'
            )
            ->set(
                'items.0.presentation',
                'Tabletas 400 mg'
            )
            ->set(
                'items.0.dose',
                '1 tableta'
            )
            ->set(
                'items.0.frequency',
                'Cada 12 horas'
            )
            ->set(
                'items.0.duration',
                '5 días'
            )
            ->set(
                'items.0.instructions',
                'Tomar con alimentos.'
            )
            ->call('updatePrescription');

        $prescription->refresh();

        $this->assertSame(
            'Nueva indicación general.',
            $prescription->general_instructions
        );

        $this->assertCount(
            1,
            $prescription->items
        );

        $item = $prescription->items->first();

        $this->assertSame(
            'Ibuprofeno',
            $item->medication_name
        );

        $this->assertSame(
            'Tabletas 400 mg',
            $item->presentation
        );

        $this->assertSame(
            'Cada 12 horas',
            $item->frequency
        );

        $this->assertSame(
            '5 días',
            $item->duration
        );
    }

    public function test_user_can_add_medication_when_editing_prescription(): void
    {
        [
            $tenant,
            $user,
            $doctor,
            $patient,
            $consultation,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $prescription = $this->createPrescription(
            $doctor,
            $patient,
            $consultation
        );

        PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medication_name' => 'Paracetamol',
            'sort_order' => 1,
        ]);

        Livewire::actingAs($user)
            ->test('pages::prescriptions.edit', [
                'uuid' => $prescription->uuid,
            ])
            ->call('addMedication')
            ->set(
                'items.1.medication_name',
                'Ibuprofeno'
            )
            ->call('updatePrescription');

        $prescription->refresh();

        $this->assertCount(
            2,
            $prescription->items
        );

        $this->assertSame(
            [
                'Paracetamol',
                'Ibuprofeno',
            ],
            $prescription->items
                ->pluck('medication_name')
                ->all()
        );
    }

    public function test_user_can_remove_medication_when_editing_prescription(): void
    {
        [
            $tenant,
            $user,
            $doctor,
            $patient,
            $consultation,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $prescription = $this->createPrescription(
            $doctor,
            $patient,
            $consultation
        );

        PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medication_name' => 'Paracetamol',
            'sort_order' => 1,
        ]);

        PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medication_name' => 'Ibuprofeno',
            'sort_order' => 2,
        ]);

        Livewire::actingAs($user)
            ->test('pages::prescriptions.edit', [
                'uuid' => $prescription->uuid,
            ])
            ->call('removeMedication', 0)
            ->call('updatePrescription');

        $prescription->refresh();

        $this->assertCount(
            1,
            $prescription->items
        );

        $this->assertSame(
            'Ibuprofeno',
            $prescription->items->first()->medication_name
        );
    }

    public function test_editing_prescription_rebuilds_sort_order(): void
    {
        [
            $tenant,
            $user,
            $doctor,
            $patient,
            $consultation,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $prescription = $this->createPrescription(
            $doctor,
            $patient,
            $consultation
        );

        PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medication_name' => 'Medicamento A',
            'sort_order' => 1,
        ]);

        PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medication_name' => 'Medicamento B',
            'sort_order' => 2,
        ]);

        PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medication_name' => 'Medicamento C',
            'sort_order' => 3,
        ]);

        Livewire::actingAs($user)
            ->test('pages::prescriptions.edit', [
                'uuid' => $prescription->uuid,
            ])
            ->call('removeMedication', 1)
            ->call('updatePrescription');

        $items = $prescription
            ->fresh()
            ->items;

        $this->assertSame(
            [
                'Medicamento A',
                'Medicamento C',
            ],
            $items->pluck('medication_name')->all()
        );

        $this->assertSame(
            [1, 2],
            $items->pluck('sort_order')->all()
        );
    }

    public function test_editing_requires_medication_name(): void
    {
        [
            $tenant,
            $user,
            $doctor,
            $patient,
            $consultation,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $prescription = $this->createPrescription(
            $doctor,
            $patient,
            $consultation
        );

        PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medication_name' => 'Paracetamol',
            'sort_order' => 1,
        ]);

        Livewire::actingAs($user)
            ->test('pages::prescriptions.edit', [
                'uuid' => $prescription->uuid,
            ])
            ->set(
                'items.0.medication_name',
                ''
            )
            ->call('updatePrescription')
            ->assertHasErrors([
                'items.0.medication_name',
            ]);

        $this->assertDatabaseHas(
            'prescription_items',
            [
                'prescription_id' => $prescription->id,
                'medication_name' => 'Paracetamol',
            ]
        );
    }

    public function test_cancelled_prescription_cannot_be_edited(): void
    {
        [
            $tenant,
            $user,
            $doctor,
            $patient,
            $consultation,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $prescription = $this->createPrescription(
            $doctor,
            $patient,
            $consultation
        );

        $prescription->update([
            'status' => 'cancelled',
        ]);

        $this->actingAs($user)
            ->get(
                route('prescriptions.edit', [
                    'uuid' => $prescription->uuid,
                ])
            )
            ->assertNotFound();
    }

    public function test_user_cannot_edit_prescription_from_another_tenant(): void
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

        $prescriptionB = $this->createPrescription(
            $doctorB,
            $patientB,
            $consultationB
        );

        app(TenantContext::class)->set($tenantA);

        $this->actingAs($userA)
            ->get(
                route('prescriptions.edit', [
                    'uuid' => $prescriptionB->uuid,
                ])
            )
            ->assertNotFound();
    }

    public function test_user_can_cancel_active_prescription(): void
    {
        [
            $tenant,
            $user,
            $doctor,
            $patient,
            $consultation,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $prescription = $this->createPrescription(
            $doctor,
            $patient,
            $consultation
        );

        Livewire::actingAs($user)
            ->test('pages::prescriptions.show', [
                'uuid' => $prescription->uuid,
            ])
            ->call('cancelPrescription');

        $this->assertDatabaseHas(
            'prescriptions',
            [
                'id' => $prescription->id,
                'status' => 'cancelled',
            ]
        );
    }

    public function test_cancelling_already_cancelled_prescription_does_not_change_it(): void
    {
        [
            $tenant,
            $user,
            $doctor,
            $patient,
            $consultation,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $prescription = $this->createPrescription(
            $doctor,
            $patient,
            $consultation
        );

        $prescription->update([
            'status' => 'cancelled',
        ]);

        $updatedAt = $prescription
            ->fresh()
            ->updated_at
            ->copy();

        Livewire::actingAs($user)
            ->test('pages::prescriptions.show', [
                'uuid' => $prescription->uuid,
            ])
            ->call('cancelPrescription');

        $prescription->refresh();

        $this->assertSame(
            'cancelled',
            $prescription->status
        );

        $this->assertTrue(
            $prescription->updated_at->equalTo($updatedAt)
        );
    }

    public function test_cancelled_prescription_remains_available_for_printing(): void
    {
        [
            $tenant,
            $user,
            $doctor,
            $patient,
            $consultation,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $prescription = $this->createPrescription(
            $doctor,
            $patient,
            $consultation
        );

        $prescription->update([
            'status' => 'cancelled',
        ]);

        $this->actingAs($user)
            ->get(
                route('prescriptions.print', [
                    'uuid' => $prescription->uuid,
                ])
            )
            ->assertOk()
            ->assertSee('RECETA ANULADA');
    }

    public function test_cancelled_prescription_pdf_is_still_downloadable(): void
    {
        [
            $tenant,
            $user,
            $doctor,
            $patient,
            $consultation,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $prescription = $this->createPrescription(
            $doctor,
            $patient,
            $consultation
        );

        $prescription->update([
            'status' => 'cancelled',
        ]);

        $response = $this->actingAs($user)
            ->get(
                route('prescriptions.pdf', [
                    'uuid' => $prescription->uuid,
                ])
            );

        $response->assertOk();

        $this->assertStringStartsWith(
            'application/pdf',
            (string) $response->headers->get('Content-Type')
        );
    }

    public function test_cancelled_prescription_detail_hides_edit_and_cancel_actions(): void
    {
        [
            $tenant,
            $user,
            $doctor,
            $patient,
            $consultation,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $prescription = $this->createPrescription(
            $doctor,
            $patient,
            $consultation
        );

        $prescription->update([
            'status' => 'cancelled',
        ]);

        $this->actingAs($user)
            ->get(
                route('prescriptions.show', [
                    'uuid' => $prescription->uuid,
                ])
            )
            ->assertOk()
            ->assertSee('Anulada')
            ->assertDontSee('Editar receta')
            ->assertDontSee('Anular receta');
    }

    private function createPrescription(
        DoctorProfile $doctor,
        Patient $patient,
        Consultation $consultation,
    ): Prescription {
        return Prescription::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_id' => $consultation->id,
            'prescribed_at' => '2026-08-23 10:30:00',
            'general_instructions' => 'Indicaciones generales.',
            'status' => 'active',
        ]);
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

        $specialty = Specialty::firstOrCreate(
            [
                'slug' => 'medicina-general',
            ],
            [
                'name' => 'Medicina General',
            ]
        );

        $doctor = DoctorProfile::create([
            'user_id' => $user->id,
            'specialty_id' => $specialty->id,
            'first_name' => 'Doctor',
            'last_name' => 'Test',
            'professional_license' => '12345678',
        ]);

        PracticeProfile::create([
            'public_name' => $tenantName,
            'phone' => '9611234567',
            'email' => 'consultorio@example.com',
            'address_line_1' => 'Calle Prueba 123',
            'neighborhood' => 'Centro',
            'city' => 'Tuxtla Gutiérrez',
            'state' => 'Chiapas',
            'postal_code' => '29000',
            'country' => 'MX',
            'print_footer' => 'Citas: 9611234567',
        ]);

        $patient = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Test',
            'birth_date' => '1990-01-15',
        ]);

        $consultation = Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => '2026-08-23 09:00:00',
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
