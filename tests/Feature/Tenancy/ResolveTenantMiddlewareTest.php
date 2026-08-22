<?php

namespace Tests\Feature\Tenancy;

use App\Http\Middleware\ResolveTenant;
use App\Models\DoctorProfile;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ResolveTenantMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_tenant_is_resolved_automatically(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Doctor A',
            'email' => 'doctor-a@example.com',
            'password' => 'password',
        ]);

        Route::middleware(ResolveTenant::class)
            ->get('/test-tenant-context', function () {
                return response()->json([
                    'tenant_id' => app(TenantContext::class)->id(),
                ]);
            });

        $this
            ->actingAs($user)
            ->get('/test-tenant-context')
            ->assertOk()
            ->assertJson([
                'tenant_id' => $tenant->id,
            ]);
    }

    public function test_authenticated_user_only_sees_records_from_own_tenant(): void
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
            'email' => 'doctor-a@example.com',
            'password' => 'password',
        ]);

        $userB = User::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Doctor B',
            'email' => 'doctor-b@example.com',
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

        Route::middleware(ResolveTenant::class)
            ->get('/test-doctor-profiles', function () {
                return response()->json(
                    DoctorProfile::query()
                        ->orderBy('id')
                        ->get(['id', 'tenant_id', 'first_name', 'last_name'])
                );
            });

        $response = $this
            ->actingAs($userA)
            ->get('/test-doctor-profiles');

        $response
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'tenant_id' => $tenantA->id,
                'last_name' => 'A',
            ])
            ->assertJsonMissing([
                'tenant_id' => $tenantB->id,
                'last_name' => 'B',
            ]);
    }
}