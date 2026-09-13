<?php

namespace Tests\Feature\Internal;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalSalesModalUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_sales_page_keeps_creation_forms_available_for_modal_enhancement(): void
    {
        $user = User::factory()->create([
            'tenant_id' => null,
            'role' => User::ROLE_INTERNAL_ADMIN,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('internal.sales.index'))
            ->assertOk()
            ->assertSee('+ Nuevo vendedor')
            ->assertSee('+ Nuevo código')
            ->assertSee(route('internal.sales.partners.store'), false)
            ->assertSee(route('internal.sales.promo-codes.store'), false);
    }
}
