<?php

namespace Tests\Feature\Patients;

use App\Models\Patient;
use App\Models\PatientEmergencyContact;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmergencyContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_emergency_contact(): void
    {
        [$tenant, $user, $patient] = $this->createUserAndPatient();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::patients.show', [
                'uuid' => $patient->uuid,
            ])
            ->call('openEmergencyContactModal')
            ->set('emergency_contact_name', 'María López')
            ->set('emergency_contact_relationship', 'Esposa')
            ->set('emergency_contact_phone', '9611111111')
            ->set('emergency_contact_email', 'maria@example.com')
            ->set('emergency_contact_is_primary', true)
            ->call('saveEmergencyContact')
            ->assertRedirect(
                route('patients.show', [
                    'uuid' => $patient->uuid,
                ])
            );

        $contact = PatientEmergencyContact::firstOrFail();

        $this->assertSame($tenant->id, $contact->tenant_id);
        $this->assertSame($patient->id, $contact->patient_id);
        $this->assertSame('María López', $contact->name);
        $this->assertSame('Esposa', $contact->relationship);
        $this->assertSame('9611111111', $contact->phone);
        $this->assertSame('maria@example.com', $contact->email);
        $this->assertTrue($contact->is_primary);
    }

    public function test_emergency_contact_requires_name_and_phone(): void
    {
        [$tenant, $user, $patient] = $this->createUserAndPatient();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::patients.show', [
                'uuid' => $patient->uuid,
            ])
            ->call('openEmergencyContactModal')
            ->set('emergency_contact_name', '')
            ->set('emergency_contact_phone', '')
            ->call('saveEmergencyContact')
            ->assertHasErrors([
                'emergency_contact_name',
                'emergency_contact_phone',
            ]);

        $this->assertDatabaseCount(
            'patient_emergency_contacts',
            0
        );
    }

    public function test_emergency_contact_rejects_invalid_email(): void
    {
        [$tenant, $user, $patient] = $this->createUserAndPatient();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::patients.show', [
                'uuid' => $patient->uuid,
            ])
            ->set('emergency_contact_name', 'María López')
            ->set('emergency_contact_phone', '9611111111')
            ->set('emergency_contact_email', 'correo-invalido')
            ->call('saveEmergencyContact')
            ->assertHasErrors([
                'emergency_contact_email',
            ]);

        $this->assertDatabaseCount(
            'patient_emergency_contacts',
            0
        );
    }

    public function test_emergency_contact_belongs_to_patient_and_tenant(): void
    {
        [$tenant, $user, $patient] = $this->createUserAndPatient();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::patients.show', [
                'uuid' => $patient->uuid,
            ])
            ->set('emergency_contact_name', 'Pedro Pérez')
            ->set('emergency_contact_phone', '9612222222')
            ->call('saveEmergencyContact');

        $contact = PatientEmergencyContact::firstOrFail();

        $this->assertSame($tenant->id, $contact->tenant_id);
        $this->assertSame($patient->id, $contact->patient_id);
        $this->assertTrue($contact->patient->is($patient));
    }

    public function test_only_one_contact_can_be_primary_for_patient(): void
    {
        [$tenant, $user, $patient] = $this->createUserAndPatient();

        app(TenantContext::class)->set($tenant);

        $firstContact = PatientEmergencyContact::create([
            'patient_id' => $patient->id,
            'name' => 'Contacto Uno',
            'relationship' => 'Hermano',
            'phone' => '9611111111',
            'is_primary' => true,
        ]);

        Livewire::actingAs($user)
            ->test('pages::patients.show', [
                'uuid' => $patient->uuid,
            ])
            ->set('emergency_contact_name', 'Contacto Dos')
            ->set('emergency_contact_relationship', 'Esposa')
            ->set('emergency_contact_phone', '9612222222')
            ->set('emergency_contact_is_primary', true)
            ->call('saveEmergencyContact');

        $firstContact->refresh();

        $secondContact = PatientEmergencyContact::query()
            ->where('name', 'Contacto Dos')
            ->firstOrFail();

        $this->assertFalse($firstContact->is_primary);
        $this->assertTrue($secondContact->is_primary);

        $this->assertSame(
            1,
            PatientEmergencyContact::query()
                ->where('patient_id', $patient->id)
                ->where('is_primary', true)
                ->count()
        );
    }

    public function test_primary_contact_change_does_not_affect_other_patients(): void
    {
        [$tenant, $user, $patientA] = $this->createUserAndPatient();

        app(TenantContext::class)->set($tenant);

        $patientB = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'B',
        ]);

        $contactB = PatientEmergencyContact::create([
            'patient_id' => $patientB->id,
            'name' => 'Contacto B',
            'phone' => '9613333333',
            'is_primary' => true,
        ]);

        Livewire::actingAs($user)
            ->test('pages::patients.show', [
                'uuid' => $patientA->uuid,
            ])
            ->set('emergency_contact_name', 'Contacto A')
            ->set('emergency_contact_phone', '9614444444')
            ->set('emergency_contact_is_primary', true)
            ->call('saveEmergencyContact');

        $contactB->refresh();

        $this->assertTrue($contactB->is_primary);
    }

    public function test_user_can_edit_emergency_contact(): void
    {
        [$tenant, $user, $patient] = $this->createUserAndPatient();

        app(TenantContext::class)->set($tenant);

        $contact = PatientEmergencyContact::create([
            'patient_id' => $patient->id,
            'name' => 'Contacto Original',
            'relationship' => 'Hermano',
            'phone' => '9611111111',
            'email' => 'original@example.com',
            'is_primary' => false,
        ]);

        Livewire::actingAs($user)
            ->test('pages::patients.show', [
                'uuid' => $patient->uuid,
            ])
            ->call('editEmergencyContact', $contact->id)
            ->assertSet(
                'editingEmergencyContactId',
                $contact->id
            )
            ->assertSet(
                'emergency_contact_name',
                'Contacto Original'
            )
            ->set(
                'emergency_contact_name',
                'Contacto Editado'
            )
            ->set(
                'emergency_contact_relationship',
                'Esposa'
            )
            ->set(
                'emergency_contact_phone',
                '9619999999'
            )
            ->set(
                'emergency_contact_email',
                'editado@example.com'
            )
            ->set(
                'emergency_contact_is_primary',
                true
            )
            ->call('saveEmergencyContact')
            ->assertRedirect(
                route('patients.show', [
                    'uuid' => $patient->uuid,
                ])
            );

        $contact->refresh();

        $this->assertSame(
            'Contacto Editado',
            $contact->name
        );

        $this->assertSame(
            'Esposa',
            $contact->relationship
        );

        $this->assertSame(
            '9619999999',
            $contact->phone
        );

        $this->assertSame(
            'editado@example.com',
            $contact->email
        );

        $this->assertTrue(
            $contact->is_primary
        );
    }

    public function test_editing_contact_as_primary_removes_previous_primary(): void
    {
        [$tenant, $user, $patient] = $this->createUserAndPatient();

        app(TenantContext::class)->set($tenant);

        $primary = PatientEmergencyContact::create([
            'patient_id' => $patient->id,
            'name' => 'Principal',
            'phone' => '9611111111',
            'is_primary' => true,
        ]);

        $secondary = PatientEmergencyContact::create([
            'patient_id' => $patient->id,
            'name' => 'Secundario',
            'phone' => '9612222222',
            'is_primary' => false,
        ]);

        Livewire::actingAs($user)
            ->test('pages::patients.show', [
                'uuid' => $patient->uuid,
            ])
            ->call(
                'editEmergencyContact',
                $secondary->id
            )
            ->set(
                'emergency_contact_is_primary',
                true
            )
            ->call('saveEmergencyContact');

        $primary->refresh();
        $secondary->refresh();

        $this->assertFalse(
            $primary->is_primary
        );

        $this->assertTrue(
            $secondary->is_primary
        );
    }

    public function test_user_can_delete_emergency_contact(): void
    {
        [$tenant, $user, $patient] = $this->createUserAndPatient();

        app(TenantContext::class)->set($tenant);

        $contact = PatientEmergencyContact::create([
            'patient_id' => $patient->id,
            'name' => 'Contacto a eliminar',
            'phone' => '9611111111',
        ]);

        Livewire::actingAs($user)
            ->test('pages::patients.show', [
                'uuid' => $patient->uuid,
            ])
            ->call(
                'deleteEmergencyContact',
                $contact->id
            )
            ->assertRedirect(
                route('patients.show', [
                    'uuid' => $patient->uuid,
                ])
            );

        $this->assertDatabaseMissing(
            'patient_emergency_contacts',
            [
                'id' => $contact->id,
            ]
        );
    }

    public function test_cannot_edit_contact_from_another_patient(): void
    {
        [$tenant, $user, $patientA] = $this->createUserAndPatient();

        app(TenantContext::class)->set($tenant);

        $patientB = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'B',
        ]);

        $contactB = PatientEmergencyContact::create([
            'patient_id' => $patientB->id,
            'name' => 'Contacto B',
            'phone' => '9613333333',
        ]);

        $this->expectException(
            \Illuminate\Database\Eloquent\ModelNotFoundException::class
        );

        Livewire::actingAs($user)
            ->test('pages::patients.show', [
                'uuid' => $patientA->uuid,
            ])
            ->call(
                'editEmergencyContact',
                $contactB->id
            );
    }

    public function test_cannot_delete_contact_from_another_patient(): void
    {
        [$tenant, $user, $patientA] = $this->createUserAndPatient();

        app(TenantContext::class)->set($tenant);

        $patientB = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'B',
        ]);

        $contactB = PatientEmergencyContact::create([
            'patient_id' => $patientB->id,
            'name' => 'Contacto B',
            'phone' => '9613333333',
        ]);

        try {
            Livewire::actingAs($user)
                ->test('pages::patients.show', [
                    'uuid' => $patientA->uuid,
                ])
                ->call(
                    'deleteEmergencyContact',
                    $contactB->id
                );

            $this->fail(
                'Se esperaba ModelNotFoundException.'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->assertTrue(true);
        }

        $this->assertDatabaseHas(
            'patient_emergency_contacts',
            [
                'id' => $contactB->id,
            ]
        );
    }

    public function test_user_cannot_open_patient_from_another_tenant_to_manage_contacts(): void
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

        PatientEmergencyContact::create([
            'patient_id' => $patientB->id,
            'name' => 'Contacto B',
            'phone' => '9615555555',
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
