<?php

namespace Tests\Feature\Patients;

use App\Models\Patient;
use App\Models\PatientEmergencyContact;
use App\Models\PatientMedicalHistory;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_generates_uuid_and_gets_current_tenant(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
        ]);

        $this->assertSame($tenant->id, $patient->tenant_id);
        $this->assertNotNull($patient->uuid);
        $this->assertSame('MX', $patient->country);
    }

    public function test_patient_can_have_emergency_contacts(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
        ]);

        $contact = PatientEmergencyContact::create([
            'patient_id' => $patient->id,
            'name' => 'María Pérez',
            'relationship' => 'Esposa',
            'phone' => '5555555555',
            'is_primary' => true,
        ]);

        $this->assertTrue(
            $patient->emergencyContacts()->get()->contains($contact)
        );

        $this->assertSame($tenant->id, $contact->tenant_id);
        $this->assertTrue($contact->is_primary);
    }

    public function test_patient_can_have_medical_history(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
        ]);

        $history = PatientMedicalHistory::create([
            'patient_id' => $patient->id,
            'allergies_text' => 'Penicilina',
        ]);

        $this->assertTrue(
            $patient->medicalHistory()->first()->is($history)
        );

        $this->assertSame($tenant->id, $history->tenant_id);
        $this->assertSame('Penicilina', $history->allergies_text);
    }

    public function test_patients_are_isolated_between_tenants(): void
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

        Patient::create([
            'first_name' => 'Juan',
            'last_name' => 'A',
        ]);

        app(TenantContext::class)->set($tenantB);

        Patient::create([
            'first_name' => 'Pedro',
            'last_name' => 'B',
        ]);

        app(TenantContext::class)->set($tenantA);

        $patients = Patient::all();

        $this->assertCount(1, $patients);
        $this->assertSame('A', $patients->first()->last_name);
        $this->assertSame(
            $tenantA->id,
            $patients->first()->tenant_id
        );
    }

    public function test_patient_cannot_be_created_without_tenant_context(): void
    {
        app(TenantContext::class)->clear();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'No tenant has been resolved for the current request.'
        );

        Patient::create([
            'first_name' => 'Juan',
            'last_name' => 'Sin Tenant',
        ]);
    }

    public function test_emergency_contacts_are_isolated_between_tenants(): void
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

        PatientEmergencyContact::create([
            'patient_id' => $patientA->id,
            'name' => 'Contacto A',
            'phone' => '1111111111',
        ]);

        app(TenantContext::class)->set($tenantB);

        $patientB = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'B',
        ]);

        PatientEmergencyContact::create([
            'patient_id' => $patientB->id,
            'name' => 'Contacto B',
            'phone' => '2222222222',
        ]);

        app(TenantContext::class)->set($tenantA);

        $contacts = PatientEmergencyContact::all();

        $this->assertCount(1, $contacts);
        $this->assertSame('Contacto A', $contacts->first()->name);
        $this->assertSame($tenantA->id, $contacts->first()->tenant_id);
    }
}
