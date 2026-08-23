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
use Tests\TestCase;

class PrescriptionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_prescription_generates_uuid_and_belongs_to_context(): void
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
            'general_instructions' => 'Tomar medicamentos según indicaciones.',
            'status' => 'active',
        ]);

        $this->assertNotNull($prescription->uuid);

        $this->assertSame(
            $tenant->id,
            $prescription->tenant_id
        );

        $this->assertTrue(
            $prescription->patient->is($patient)
        );

        $this->assertTrue(
            $prescription->doctorProfile->is($doctor)
        );

        $this->assertTrue(
            $prescription->consultation->is($consultation)
        );

        $this->assertSame(
            'active',
            $prescription->status
        );
    }

    public function test_prescription_can_have_multiple_items(): void
    {
        [
            $tenant,,
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
            'presentation' => 'Tabletas 500 mg',
            'dose' => '1 tableta',
            'frequency' => 'Cada 8 horas',
            'duration' => '3 días',
            'instructions' => 'Tomar después de alimentos.',
            'sort_order' => 1,
        ]);

        PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medication_name' => 'Ibuprofeno',
            'presentation' => 'Tabletas 400 mg',
            'dose' => '1 tableta',
            'frequency' => 'Cada 12 horas',
            'duration' => '3 días',
            'instructions' => 'Suspender si presenta irritación gástrica.',
            'sort_order' => 2,
        ]);

        $prescription->refresh();

        $this->assertCount(
            2,
            $prescription->items
        );

        $this->assertSame(
            'Paracetamol',
            $prescription->items->first()->medication_name
        );

        $this->assertSame(
            'Ibuprofeno',
            $prescription->items->last()->medication_name
        );
    }

    public function test_prescription_items_inherit_current_tenant(): void
    {
        [
            $tenant,,
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

        $item = PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medication_name' => 'Paracetamol',
        ]);

        $this->assertSame(
            $tenant->id,
            $item->tenant_id
        );

        $this->assertTrue(
            $item->prescription->is($prescription)
        );
    }

    public function test_prescription_items_are_ordered_by_sort_order(): void
    {
        [
            $tenant,,
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
            'medication_name' => 'Medicamento C',
            'sort_order' => 3,
        ]);

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

        $items = $prescription->items;

        $this->assertSame(
            [
                'Medicamento A',
                'Medicamento B',
                'Medicamento C',
            ],
            $items->pluck('medication_name')->all()
        );
    }

    public function test_prescription_can_exist_without_consultation(): void
    {
        [
            $tenant,,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $prescription = Prescription::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_id' => null,
            'prescribed_at' => now(),
        ]);

        $this->assertNull(
            $prescription->consultation_id
        );

        $this->assertNull(
            $prescription->consultation
        );
    }

    public function test_prescription_uses_uuid_as_route_key(): void
    {
        [
            $tenant,,
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

        $this->assertSame(
            'uuid',
            $prescription->getRouteKeyName()
        );

        $this->assertSame(
            $prescription->uuid,
            $prescription->getRouteKey()
        );
    }

    public function test_prescriptions_are_isolated_between_tenants(): void
    {
        [
            $tenantA,,
            $doctorA,
            $patientA,
            $consultationA,
        ] = $this->createContext(
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

        app(TenantContext::class)->set($tenantA);

        Prescription::create([
            'patient_id' => $patientA->id,
            'doctor_profile_id' => $doctorA->id,
            'consultation_id' => $consultationA->id,
            'prescribed_at' => now(),
            'general_instructions' => 'Receta Tenant A',
        ]);

        app(TenantContext::class)->set($tenantB);

        Prescription::create([
            'patient_id' => $patientB->id,
            'doctor_profile_id' => $doctorB->id,
            'consultation_id' => $consultationB->id,
            'prescribed_at' => now(),
            'general_instructions' => 'Receta Tenant B',
        ]);

        app(TenantContext::class)->set($tenantA);

        $prescriptions = Prescription::all();

        $this->assertCount(
            1,
            $prescriptions
        );

        $this->assertSame(
            'Receta Tenant A',
            $prescriptions->first()->general_instructions
        );
    }

    public function test_prescription_items_are_isolated_between_tenants(): void
    {
        [
            $tenantA,,
            $doctorA,
            $patientA,
            $consultationA,
        ] = $this->createContext(
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

        app(TenantContext::class)->set($tenantA);

        $prescriptionA = Prescription::create([
            'patient_id' => $patientA->id,
            'doctor_profile_id' => $doctorA->id,
            'consultation_id' => $consultationA->id,
            'prescribed_at' => now(),
        ]);

        PrescriptionItem::create([
            'prescription_id' => $prescriptionA->id,
            'medication_name' => 'Medicamento Tenant A',
        ]);

        app(TenantContext::class)->set($tenantB);

        $prescriptionB = Prescription::create([
            'patient_id' => $patientB->id,
            'doctor_profile_id' => $doctorB->id,
            'consultation_id' => $consultationB->id,
            'prescribed_at' => now(),
        ]);

        PrescriptionItem::create([
            'prescription_id' => $prescriptionB->id,
            'medication_name' => 'Medicamento Tenant B',
        ]);

        app(TenantContext::class)->set($tenantA);

        $items = PrescriptionItem::all();

        $this->assertCount(
            1,
            $items
        );

        $this->assertSame(
            'Medicamento Tenant A',
            $items->first()->medication_name
        );
    }

    public function test_deleting_prescription_deletes_its_items(): void
    {
        [
            $tenant,,
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

        $item = PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medication_name' => 'Paracetamol',
        ]);

        /*
         * Prescription usa SoftDeletes.
         *
         * Un delete normal no dispara ON DELETE CASCADE
         * porque el registro sigue existiendo físicamente.
         *
         * Para probar la FK usamos forceDelete().
         */
        $prescription->forceDelete();

        $this->assertDatabaseMissing(
            'prescription_items',
            [
                'id' => $item->id,
            ]
        );
    }

    private function createContext(
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
            $doctor,
            $patient,
            $consultation,
        ];
    }
}
