<?php

namespace Tests\Feature\Patients;

use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PatientEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_edit_patient(): void
    {
        [$tenant, $user] = $this->createUser();

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'email' => 'juan@example.com',
            'phone' => '9611111111',
        ]);

        Livewire::actingAs($user)
            ->test('pages::patients.edit', [
                'uuid' => $patient->uuid,
            ])
            ->set('first_name', 'Juan Carlos')
            ->set('last_name', 'Pérez')
            ->set('email', 'juancarlos@example.com')
            ->set('phone', '9612222222')
            ->call('updatePatient')
            ->assertRedirect(
                route('patients.show', [
                    'uuid' => $patient->uuid,
                ])
            );

        $patient->refresh();

        $this->assertSame('Juan Carlos', $patient->first_name);
        $this->assertSame('juancarlos@example.com', $patient->email);
        $this->assertSame('9612222222', $patient->phone);
    }

    public function test_edit_preserves_uuid_and_tenant(): void
    {
        [$tenant, $user] = $this->createUser();

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
        ]);

        $uuid = $patient->uuid;
        $tenantId = $patient->tenant_id;

        Livewire::actingAs($user)
            ->test('pages::patients.edit', [
                'uuid' => $patient->uuid,
            ])
            ->set('first_name', 'Juan Editado')
            ->call('updatePatient');

        $patient->refresh();

        $this->assertSame($uuid, $patient->uuid);
        $this->assertSame($tenantId, $patient->tenant_id);
    }

    public function test_edit_validates_required_fields(): void
    {
        [$tenant, $user] = $this->createUser();

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
        ]);

        Livewire::actingAs($user)
            ->test('pages::patients.edit', [
                'uuid' => $patient->uuid,
            ])
            ->set('first_name', '')
            ->set('last_name', '')
            ->call('updatePatient')
            ->assertHasErrors([
                'first_name',
                'last_name',
            ]);
    }

    public function test_user_cannot_edit_patient_from_another_tenant(): void
    {
        [$tenantA, $userA] = $this->createUser(
            'Tenant A',
            'tenant-a',
            'a@example.com'
        );

        [$tenantB] = $this->createUser(
            'Tenant B',
            'tenant-b',
            'b@example.com'
        );

        app(TenantContext::class)->set($tenantB);

        $patientB = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'B',
        ]);

        app(TenantContext::class)->set($tenantA);

        $this->actingAs($userA)
            ->get(
                route('patients.edit', [
                    'uuid' => $patientB->uuid,
                ])
            )
            ->assertNotFound();
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

        return [$tenant, $user];
    }
}
