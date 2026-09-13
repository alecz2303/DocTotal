<?php

namespace Tests\Feature\Internal;

use App\Models\AuditEvent;
use App\Models\Payment;
use App\Models\PromoCode;
use App\Models\SalesCommission;
use App\Models\SalesPartner;
use App\Models\Tenant;
use App\Models\TenantPromoAttribution;
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
            ->assertSee('Ventas y comisiones')
            ->assertSee('Rendimiento por vendedor')
            ->assertSee('Rendimiento por código')
            ->assertSee('Pendiente por pagar');
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

    public function test_sales_console_reports_revenue_conversion_discount_and_pending_commission(): void
    {
        [$partner, $promoCode, $tenant, $payment, $commission] =
            $this->createCommercialSale(
                partnerName: 'Karelly Ramírez',
                code: 'KARELLY',
                amount: 54000,
                grossAmount: 60000,
                discountAmount: 6000,
                commissionAmount: 10800,
            );

        $this->actingAs($this->internalAdmin())
            ->get(route('internal.sales.index'))
            ->assertOk()
            ->assertSee('Karelly Ramírez')
            ->assertSee('KARELLY')
            ->assertSee('$540.00')
            ->assertSee('$60.00')
            ->assertSee('$108.00')
            ->assertSee('100.0%');

        $this->assertSame($partner->id, $commission->sales_partner_id);
        $this->assertSame($promoCode->id, $commission->promo_code_id);
        $this->assertSame($tenant->id, $payment->tenant_id);
    }

    public function test_zero_commission_code_still_counts_as_sale_and_conversion(): void
    {
        [$partner, $promoCode, $tenant] = $this->createAttribution(
            partnerName: 'Vendedor Prueba',
            code: 'PRUEBA98',
            discountPercent: 98,
            commissionPercent: 0,
        );

        Payment::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'amount' => 1200,
            'gross_amount' => 60000,
            'promo_code_discount_amount' => 58800,
            'currency' => 'MXN',
            'status' => Payment::STATUS_SUCCEEDED,
            'billing_cycle' => 'monthly',
            'attempted_at' => now(),
            'paid_at' => now(),
            'provider' => 'stripe',
            'idempotency_key' => (string) str()->uuid(),
        ]);

        $this->actingAs($this->internalAdmin())
            ->get(route('internal.sales.index'))
            ->assertOk()
            ->assertSee('PRUEBA98')
            ->assertSee('$12.00')
            ->assertSee('100.0%');

        $this->assertDatabaseCount('sales_commissions', 0);
        $this->assertSame($partner->id, $promoCode->sales_partner_id);
    }

    public function test_internal_admin_can_settle_all_pending_commissions_for_partner(): void
    {
        [$partner, , , , $commission] = $this->createCommercialSale(
            partnerName: 'Karelly Ramírez',
            code: 'KARELLY',
            amount: 54000,
            grossAmount: 60000,
            discountAmount: 6000,
            commissionAmount: 10800,
        );

        $this->actingAs($this->internalAdmin())
            ->put(route('internal.sales.partners.commissions.paid', $partner))
            ->assertRedirect()
            ->assertSessionHas('status');

        $commission->refresh();

        $this->assertSame(SalesCommission::STATUS_PAID, $commission->status);
        $this->assertNotNull($commission->paid_at);

        $this->assertDatabaseHas('audit_events', [
            'action' => 'internal.sales_partner.commissions_paid',
            'auditable_id' => $partner->id,
        ]);
    }

    public function test_partner_settlement_cannot_pay_same_commission_twice(): void
    {
        [$partner, , , , $commission] = $this->createCommercialSale(
            partnerName: 'Karelly Ramírez',
            code: 'KARELLY',
            amount: 54000,
            grossAmount: 60000,
            discountAmount: 6000,
            commissionAmount: 10800,
        );

        $admin = $this->internalAdmin();

        $this->actingAs($admin)
            ->put(route('internal.sales.partners.commissions.paid', $partner))
            ->assertRedirect();

        $firstPaidAt = $commission->fresh()->paid_at;

        $this->actingAs($admin)
            ->put(route('internal.sales.partners.commissions.paid', $partner))
            ->assertRedirect()
            ->assertSessionHasErrors('commission');

        $this->assertEquals($firstPaidAt, $commission->fresh()->paid_at);
    }

    private function createCommercialSale(
        string $partnerName,
        string $code,
        int $amount,
        int $grossAmount,
        int $discountAmount,
        int $commissionAmount,
    ): array {
        [$partner, $promoCode, $tenant] = $this->createAttribution(
            partnerName: $partnerName,
            code: $code,
            discountPercent: $grossAmount > 0
                ? ($discountAmount / $grossAmount) * 100
                : 0,
            commissionPercent: $amount > 0
                ? ($commissionAmount / $amount) * 100
                : 0,
        );

        $payment = Payment::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'amount' => $amount,
            'gross_amount' => $grossAmount,
            'promo_code_discount_amount' => $discountAmount,
            'currency' => 'MXN',
            'status' => Payment::STATUS_SUCCEEDED,
            'billing_cycle' => 'monthly',
            'attempted_at' => now(),
            'paid_at' => now(),
            'provider' => 'stripe',
            'idempotency_key' => (string) str()->uuid(),
        ]);

        $commission = SalesCommission::query()->create([
            'sales_partner_id' => $partner->id,
            'promo_code_id' => $promoCode->id,
            'tenant_id' => $tenant->id,
            'payment_id' => $payment->id,
            'status' => SalesCommission::STATUS_ACCRUED,
            'base_amount' => $amount,
            'commission_percent' => $amount > 0
                ? ($commissionAmount / $amount) * 100
                : 0,
            'commission_amount' => $commissionAmount,
            'currency' => 'MXN',
            'accrued_at' => now(),
        ]);

        return [$partner, $promoCode, $tenant, $payment, $commission];
    }

    private function createAttribution(
        string $partnerName,
        string $code,
        float $discountPercent,
        float $commissionPercent,
    ): array {
        $partner = SalesPartner::query()->create([
            'name' => $partnerName,
            'active' => true,
        ]);

        $promoCode = PromoCode::query()->create([
            'sales_partner_id' => $partner->id,
            'code' => $code,
            'active' => true,
            'doctor_discount_percent' => $discountPercent,
            'commission_percent' => $commissionPercent,
            'commission_scope' => PromoCode::SCOPE_FIRST_SUCCESSFUL_PAYMENT,
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Consultorio '.$partnerName,
            'slug' => 'consultorio-'.str()->lower(str()->random(8)),
            'status' => 'trial',
        ]);

        TenantPromoAttribution::query()->create([
            'tenant_id' => $tenant->id,
            'promo_code_id' => $promoCode->id,
            'sales_partner_id' => $partner->id,
            'code_snapshot' => $code,
            'doctor_discount_percent_snapshot' => $discountPercent,
            'commission_percent_snapshot' => $commissionPercent,
            'attributed_at' => now(),
        ]);

        return [$partner, $promoCode, $tenant];
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
