<?php

namespace Tests\Feature\Patients;

use App\Models\AuditEvent;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Illuminate\Support\Facades\Schema;

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

        return [$tenant, $user];
    }

    public function test_updating_patient_creates_audit_event_with_changed_fields_only(): void
    {
        $tenant = Tenant::create([
            'name' => 'Consultorio Test',
            'slug' => 'consultorio-test',
            'trial_started_at' => now(),
            'trial_ends_at' => now()->addDays(30),
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

        $patient = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Prueba',
            'second_last_name' => 'Test',
            'birth_date' => '1990-01-15',
            'phone' => '9611111111',
        ]);

        $this->actingAs($user);

        Livewire::test('pages::patients.edit', [
            'uuid' => $patient->uuid,
        ])
            ->set('phone', '9612222222')
            ->call('updatePatient');

        $event = AuditEvent::query()
            ->where('action', 'patient.updated')
            ->firstOrFail();

        $this->assertSame(
            $tenant->id,
            $event->tenant_id
        );

        $this->assertSame(
            $user->id,
            $event->user_id
        );

        $this->assertSame(
            Patient::class,
            $event->auditable_type
        );

        $this->assertSame(
            $patient->id,
            $event->auditable_id
        );

        $this->assertSame(
            ['phone'],
            $event->metadata['changed_fields']
        );

        $this->assertArrayNotHasKey(
            'old',
            $event->metadata
        );

        $this->assertArrayNotHasKey(
            'new',
            $event->metadata
        );
    }

    public function test_patient_update_succeeds_even_when_audit_persistence_fails(): void
    {
        [$tenant, $user] = $this->createUser();

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'email' => 'juan@example.com',
            'phone' => '9611111111',
        ]);

        Schema::drop('audit_events');

        Livewire::actingAs($user)
            ->test('pages::patients.edit', [
                'uuid' => $patient->uuid,
            ])
            ->set('phone', '9619999999')
            ->call('updatePatient')
            ->assertRedirect(
                route('patients.show', [
                    'uuid' => $patient->uuid,
                ])
            );

        $patient->refresh();

        $this->assertSame(
            '9619999999',
            $patient->phone
        );
    }
}
