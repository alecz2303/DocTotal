<?php

namespace Tests\Feature\Internal;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Internal\InternalSaasOverviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalSaasOverviewServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_can_explicitly_read_cross_tenant_subscription_and_payment_data(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant de prueba',
            'slug' => 'tenant-prueba-overview',
            'status' => 'trial',
            'trial_started_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);

        Subscription::query()->withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::BILLING_CYCLE_MONTHLY,
            'starts_at' => now(),
            'current_period_starts_at' => now(),
            'current_period_ends_at' => now()->addMonth(),
        ]);

        Payment::query()->withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'status' => Payment::STATUS_FAILED,
            'amount' => 100,
            'attempted_at' => now(),
            'idempotency_key' => 'test-payment-failed-001',
        ]);

        $overview = app(InternalSaasOverviewService::class)->overview();

        $this->assertSame(1, $overview['subscriptions']['active']);
        $this->assertSame(1, $overview['payments']['failed']);
    }

    public function test_service_can_list_tenants_globally(): void
    {
        Tenant::create([
            'name' => 'Clínica Uno',
            'slug' => 'clinica-uno',
            'status' => 'trial',
            'trial_started_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);

        Tenant::create([
            'name' => 'Clínica Dos',
            'slug' => 'clinica-dos',
            'status' => 'trial',
            'trial_started_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);

        $tenants = app(InternalSaasOverviewService::class)->tenants();

        $this->assertSame(2, $tenants->total());
        $this->assertSame(
            ['Clínica Dos', 'Clínica Uno'],
            $tenants->getCollection()->pluck('name')->all()
        );
    }

    public function test_service_can_build_operational_detail_for_a_tenant(): void
    {
        $tenant = Tenant::create([
            'name' => 'Clínica Detalle',
            'slug' => 'clinica-detalle',
            'status' => 'trial',
            'trial_started_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);

        User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => User::ROLE_OWNER,
            'name' => 'Doctor Prueba',
            'email' => 'doctor@example.com',
        ]);

        Subscription::query()->withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::BILLING_CYCLE_MONTHLY,
            'starts_at' => now(),
            'current_period_starts_at' => now(),
            'current_period_ends_at' => now()->addMonth(),
        ]);

        Payment::query()->withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'status' => Payment::STATUS_FAILED,
            'amount' => 100,
            'attempted_at' => now(),
            'idempotency_key' => 'tenant-detail-payment-001',
        ]);

        $detail = app(InternalSaasOverviewService::class)->tenantDetail($tenant);

        $this->assertSame($tenant->id, $detail['tenant']->id);
        $this->assertCount(1, $detail['users']);
        $this->assertSame(Subscription::STATUS_ACTIVE, $detail['subscription']->status);
        $this->assertCount(1, $detail['payments']);
    }
}
