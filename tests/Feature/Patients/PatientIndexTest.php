<?php

namespace Tests\Feature\Patients;

use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PatientIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_access_patient_index(): void
    {
        [$tenant, $user] = $this->createUser();

        $tenant->update([
            'onboarding_completed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/patients')
            ->assertOk()
            ->assertSee('Pacientes');
    }

    public function test_patient_index_only_shows_current_tenant_patients(): void
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

        app(TenantContext::class)->set($tenantA);

        Patient::create([
            'first_name' => 'Juan',
            'last_name' => 'TenantA',
        ]);

        app(TenantContext::class)->set($tenantB);

        Patient::create([
            'first_name' => 'Pedro',
            'last_name' => 'TenantB',
        ]);

        app(TenantContext::class)->set($tenantA);

        Livewire::actingAs($userA)
            ->test('pages::patients.index')
            ->assertSee('Juan')
            ->assertSee('TenantA')
            ->assertDontSee('Pedro')
            ->assertDontSee('TenantB');
    }

    public function test_patient_search_filters_results(): void
    {
        [$tenant, $user] = $this->createUser();

        app(TenantContext::class)->set($tenant);

        Patient::create([
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'phone' => '9611111111',
        ]);

        Patient::create([
            'first_name' => 'María',
            'last_name' => 'López',
            'phone' => '9612222222',
        ]);

        Livewire::actingAs($user)
            ->test('pages::patients.index')
            ->set('search', 'Juan')
            ->assertSee('Juan')
            ->assertDontSee('María');

        Livewire::actingAs($user)
            ->test('pages::patients.index')
            ->set('search', '9612222222')
            ->assertSee('María')
            ->assertDontSee('Juan');
    }

    public function test_user_can_open_and_close_create_patient_modal(): void
    {
        [$tenant, $user] = $this->createUser();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::patients.index')
            ->assertSet('showCreateModal', false)
            ->call('openCreateModal')
            ->assertSet('showCreateModal', true)
            ->call('closeCreateModal')
            ->assertSet('showCreateModal', false);
    }

    public function test_user_can_create_patient(): void
    {
        [$tenant, $user] = $this->createUser();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::patients.index')
            ->call('openCreateModal')
            ->set('first_name', 'Alejandro')
            ->set('last_name', 'Rueda')
            ->set('second_last_name', 'López')
            ->set('birth_date', '1990-05-15')
            ->set('sex', 'male')
            ->set('email', 'alejandro@example.com')
            ->set('phone', '9611111111')
            ->set('whatsapp', '9611111111')
            ->set('blood_type', 'O+')
            ->call('createPatient')
            ->assertRedirect('/patients');

        $patient = Patient::firstOrFail();

        $this->assertSame($tenant->id, $patient->tenant_id);
        $this->assertNotNull($patient->uuid);

        $this->assertSame('Alejandro', $patient->first_name);
        $this->assertSame('Rueda', $patient->last_name);
        $this->assertSame('López', $patient->second_last_name);
        $this->assertSame('male', $patient->sex);
        $this->assertSame('alejandro@example.com', $patient->email);
        $this->assertSame('9611111111', $patient->phone);
        $this->assertSame('9611111111', $patient->whatsapp);
        $this->assertSame('O+', $patient->blood_type);
    }

    public function test_patient_first_name_and_last_name_are_required(): void
    {
        [$tenant, $user] = $this->createUser();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::patients.index')
            ->call('openCreateModal')
            ->set('first_name', '')
            ->set('last_name', '')
            ->call('createPatient')
            ->assertHasErrors([
                'first_name',
                'last_name',
            ]);

        $this->assertDatabaseCount('patients', 0);
    }

    public function test_patient_rejects_invalid_email(): void
    {
        [$tenant, $user] = $this->createUser();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::patients.index')
            ->call('openCreateModal')
            ->set('first_name', 'Juan')
            ->set('last_name', 'Pérez')
            ->set('email', 'correo-invalido')
            ->call('createPatient')
            ->assertHasErrors([
                'email',
            ]);

        $this->assertDatabaseCount('patients', 0);
    }

    public function test_patient_rejects_future_birth_date(): void
    {
        [$tenant, $user] = $this->createUser();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::patients.index')
            ->call('openCreateModal')
            ->set('first_name', 'Juan')
            ->set('last_name', 'Pérez')
            ->set(
                'birth_date',
                now()->addDay()->format('Y-m-d')
            )
            ->call('createPatient')
            ->assertHasErrors([
                'birth_date',
            ]);

        $this->assertDatabaseCount('patients', 0);
    }

    public function test_patient_rejects_invalid_blood_type(): void
    {
        [$tenant, $user] = $this->createUser();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::patients.index')
            ->call('openCreateModal')
            ->set('first_name', 'Juan')
            ->set('last_name', 'Pérez')
            ->set('blood_type', 'Z+')
            ->call('createPatient')
            ->assertHasErrors([
                'blood_type',
            ]);

        $this->assertDatabaseCount('patients', 0);
    }

    public function test_created_patient_belongs_to_authenticated_users_tenant(): void
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

        app(TenantContext::class)->set($tenantA);

        Livewire::actingAs($userA)
            ->test('pages::patients.index')
            ->set('first_name', 'Paciente')
            ->set('last_name', 'Tenant A')
            ->call('createPatient');

        $patient = Patient::firstOrFail();

        $this->assertSame(
            $tenantA->id,
            $patient->tenant_id
        );

        $this->assertNotSame(
            $tenantB->id,
            $patient->tenant_id
        );
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
