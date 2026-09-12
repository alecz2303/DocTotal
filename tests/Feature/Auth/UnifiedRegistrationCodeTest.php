<?php

namespace Tests\Feature\Auth;

use App\Models\PromoCode;
use App\Models\Referral;
use App\Models\SalesPartner;
use App\Models\Tenant;
use App\Models\TenantPromoAttribution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnifiedRegistrationCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_page_shows_one_optional_code_field(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('Código')
            ->assertSee('name="code"', false)
            ->assertDontSee('name="referral_code"', false)
            ->assertDontSee('name="promo_code"', false);
    }

    public function test_referral_link_prefills_unified_code_field(): void
    {
        $referrer = Tenant::query()->create([
            'name' => 'Consultorio Referidor',
            'slug' => 'consultorio-referidor',
        ]);

        $this->get('/register?ref='.$referrer->referral_code)
            ->assertOk()
            ->assertSee('name="code"', false)
            ->assertSee('value="'.$referrer->referral_code.'"', false);
    }

    public function test_promo_link_prefills_unified_code_field(): void
    {
        $this->get('/register?promo=VENDE20')
            ->assertOk()
            ->assertSee('name="code"', false)
            ->assertSee('value="VENDE20"', false);
    }

    public function test_unified_code_resolves_referral(): void
    {
        $referrer = Tenant::query()->create([
            'name' => 'Consultorio Referidor',
            'slug' => 'consultorio-referidor',
        ]);

        $this->post('/register', $this->registrationPayload([
            'email' => 'referido@example.test',
            'code' => strtolower($referrer->referral_code),
        ]))->assertRedirect('/dashboard');

        $referred = Tenant::query()
            ->where('slug', 'consultorio-nuevo')
            ->firstOrFail();

        $this->assertDatabaseHas('referrals', [
            'referrer_tenant_id' => $referrer->id,
            'referred_tenant_id' => $referred->id,
            'referral_code' => $referrer->referral_code,
            'status' => Referral::STATUS_PENDING,
        ]);
    }

    public function test_unified_code_resolves_promotional_code(): void
    {
        $partner = SalesPartner::query()->create([
            'name' => 'Vendedor Uno',
            'active' => true,
        ]);

        $promo = PromoCode::query()->create([
            'sales_partner_id' => $partner->id,
            'code' => 'VENDE20',
            'active' => true,
            'doctor_discount_percent' => 15,
            'commission_percent' => 20,
            'commission_scope' => PromoCode::SCOPE_FIRST_SUCCESSFUL_PAYMENT,
        ]);

        $this->post('/register', $this->registrationPayload([
            'email' => 'promo@example.test',
            'code' => 'vende20',
        ]))->assertRedirect('/dashboard');

        $tenant = Tenant::query()
            ->where('slug', 'consultorio-nuevo')
            ->firstOrFail();

        $this->assertDatabaseHas('tenant_promo_attributions', [
            'tenant_id' => $tenant->id,
            'promo_code_id' => $promo->id,
            'sales_partner_id' => $partner->id,
            'code_snapshot' => 'VENDE20',
        ]);
    }

    public function test_unified_code_rejects_unknown_code(): void
    {
        $this->from('/register')
            ->post('/register', $this->registrationPayload([
                'email' => 'invalid@example.test',
                'code' => 'NOEXISTE',
            ]))
            ->assertRedirect('/register')
            ->assertSessionHasErrors([
                'code' => 'El código ingresado no es válido o ya no está vigente.',
            ]);

        $this->assertDatabaseCount('tenants', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_without_code_still_works(): void
    {
        $this->post('/register', $this->registrationPayload([
            'email' => 'sin.codigo@example.test',
        ]))->assertRedirect('/dashboard');

        $this->assertDatabaseCount('tenants', 1);
        $this->assertDatabaseCount('referrals', 0);
        $this->assertDatabaseCount('tenant_promo_attributions', 0);
    }

    private function registrationPayload(array $overrides = []): array
    {
        return array_merge([
            'practice_name' => 'Consultorio Nuevo',
            'first_name' => 'Ana',
            'last_name' => 'López',
            'email' => 'ana@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms_accepted' => '1',
        ], $overrides);
    }
}
