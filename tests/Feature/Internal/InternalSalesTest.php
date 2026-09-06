<?php

namespace Tests\Feature\Internal;

use App\Models\AuditEvent;
use App\Models\PromoCode;
use App\Models\SalesPartner;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Commercial\PromoCodeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalSalesTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_admin_can_access_sales_console(): void
    {
        $this->actingAs($this->internalAdmin())
            ->get(route('internal.sales.index'))
            ->assertOk()
            ->assertSee('Vendedores, códigos y comisiones');
    }

    public function test_tenant_user_cannot_access_sales_console(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Tenant test',
            'slug' => 'tenant-sales-test',
            'status' => 'trial',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => User::ROLE_OWNER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('internal.sales.index'))
            ->assertForbidden();
    }

    public function test_internal_admin_can_create_partner_and_global_audit_is_recorded(): void
    {
        $admin = $this->internalAdmin();

        $this->actingAs($admin)
            ->post(route('internal.sales.partners.store'), [
                'name' => 'Promotor Chiapas',
                'email' => 'ventas@example.test',
            ])
            ->assertRedirect();

        $partner = SalesPartner::query()
            ->where('name', 'Promotor Chiapas')
            ->firstOrFail();

        $event = AuditEvent::query()
            ->withoutGlobalScopes()
            ->where('action', 'internal.sales_partner.created')
            ->where('auditable_id', $partner->id)
            ->first();

        $this->assertNotNull($event);
        $this->assertNull($event->tenant_id);
        $this->assertSame($admin->id, $event->user_id);
    }

    public function test_internal_admin_can_edit_partner_and_promo_code(): void
    {
        $admin = $this->internalAdmin();
        $partner = SalesPartner::query()->create([
            'name' => 'Vendedor Original',
            'active' => true,
        ]);

        $promoCode = PromoCode::query()->create([
            'sales_partner_id' => $partner->id,
            'code' => 'ORIGINAL10',
            'active' => true,
            'doctor_discount_percent' => 10,
            'commission_percent' => 15,
        ]);

        $this->actingAs($admin)
            ->put(route('internal.sales.partners.update', $partner), [
                'name' => 'Vendedor Editado',
                'email' => 'editado@example.test',
                'phone' => '9610000000',
                'notes' => 'Actualizado',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->put(route('internal.sales.promo-codes.update', $promoCode), [
                'sales_partner_id' => $partner->id,
                'code' => 'EDITADO20',
                'doctor_discount_percent' => 20,
                'commission_percent' => 25,
                'starts_at' => null,
                'ends_at' => null,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('sales_partners', [
            'id' => $partner->id,
            'name' => 'Vendedor Editado',
            'email' => 'editado@example.test',
        ]);

        $this->assertDatabaseHas('promo_codes', [
            'id' => $promoCode->id,
            'code' => 'EDITADO20',
            'doctor_discount_percent' => 20,
            'commission_percent' => 25,
        ]);
    }

    public function test_internal_admin_can_create_and_deactivate_promo_code(): void
    {
        $admin = $this->internalAdmin();
        $partner = SalesPartner::query()->create([
            'name' => 'Vendedor Uno',
            'active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('internal.sales.promo-codes.store'), [
                'sales_partner_id' => $partner->id,
                'code' => 'promo10',
                'doctor_discount_percent' => 10,
                'commission_percent' => 15,
            ])
            ->assertRedirect();

        $promoCode = PromoCode::query()
            ->where('code', 'PROMO10')
            ->firstOrFail();

        $this->actingAs($admin)
            ->put(route('internal.sales.promo-codes.toggle', $promoCode))
            ->assertRedirect();

        $this->assertFalse($promoCode->fresh()->active);
        $this->assertNull(
            app(PromoCodeResolver::class)->findValid('PROMO10')
        );
    }

    public function test_promo_percentages_are_validated(): void
    {
        $partner = SalesPartner::query()->create([
            'name' => 'Vendedor Uno',
            'active' => true,
        ]);

        $this->actingAs($this->internalAdmin())
            ->post(route('internal.sales.promo-codes.store'), [
                'sales_partner_id' => $partner->id,
                'code' => 'INVALIDO',
                'doctor_discount_percent' => 101,
                'commission_percent' => -1,
            ])
            ->assertSessionHasErrors([
                'doctor_discount_percent',
                'commission_percent',
            ]);

        $this->assertDatabaseMissing('promo_codes', [
            'code' => 'INVALIDO',
        ]);
    }

    public function test_expired_promo_code_is_not_valid(): void
    {
        $partner = SalesPartner::query()->create([
            'name' => 'Vendedor Uno',
            'active' => true,
        ]);

        PromoCode::query()->create([
            'sales_partner_id' => $partner->id,
            'code' => 'VENCIDO',
            'active' => true,
            'doctor_discount_percent' => 10,
            'commission_percent' => 15,
            'ends_at' => now()->subMinute(),
        ]);

        $this->assertNull(
            app(PromoCodeResolver::class)->findValid('VENCIDO')
        );
    }

    private function internalAdmin(): User
    {
        return User::factory()->create([
            'tenant_id' => null,
            'role' => User::ROLE_INTERNAL_ADMIN,
            'email_verified_at' => now(),
        ]);
    }
}
