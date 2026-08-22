<?php

namespace Tests\Feature\Tenancy;

use App\Models\DoctorProfile;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_profiles_are_filtered_by_current_tenant(): void
    {
        $tenantA = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        $tenantB = Tenant::create([
            'name' => 'Tenant B',
            'slug' => 'tenant-b',
        ]);

        $userA = User::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Doctor A',
            'email' => 'a@example.com',
            'password' => 'password',
        ]);

        $userB = User::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Doctor B',
            'email' => 'b@example.com',
            'password' => 'password',
        ]);

        DoctorProfile::withoutGlobalScopes()->create([
            'tenant_id' => $tenantA->id,
            'user_id' => $userA->id,
            'first_name' => 'Doctor',
            'last_name' => 'A',
        ]);

        DoctorProfile::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'user_id' => $userB->id,
            'first_name' => 'Doctor',
            'last_name' => 'B',
        ]);

        app(TenantContext::class)->set($tenantA);

        $profiles = DoctorProfile::all();

        $this->assertCount(1, $profiles);
        $this->assertSame('A', $profiles->first()->last_name);
        $this->assertSame($tenantA->id, $profiles->first()->tenant_id);
    }

    public function test_tenant_id_is_assigned_automatically_when_creating_model(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Doctor A',
            'email' => 'a@example.com',
            'password' => 'password',
        ]);

        app(TenantContext::class)->set($tenant);

        $doctor = DoctorProfile::create([
            'user_id' => $user->id,
            'first_name' => 'Doctor',
            'last_name' => 'A',
        ]);

        $this->assertSame($tenant->id, $doctor->tenant_id);
    }

    public function test_tenant_context_is_registered_as_singleton(): void
    {
        $contextA = app(TenantContext::class);
        $contextB = app(TenantContext::class);

        $this->assertSame($contextA, $contextB);
    }

    public function test_tenant_model_returns_no_records_without_current_tenant(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Doctor A',
            'email' => 'a@example.com',
            'password' => 'password',
        ]);

        DoctorProfile::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'first_name' => 'Doctor',
            'last_name' => 'A',
        ]);

        app(TenantContext::class)->clear();

        $this->assertCount(0, DoctorProfile::all());
    }

    public function test_creating_tenant_model_without_tenant_context_fails(): void
    {
        app(TenantContext::class)->clear();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'No tenant has been resolved for the current request.'
        );

        DoctorProfile::create([
            'user_id' => 1,
            'first_name' => 'Doctor',
            'last_name' => 'Sin Tenant',
        ]);
    }
}