<?php

namespace Tests\Feature\Subscription;

use App\Models\PaymentMethod;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentMethodModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_method_generates_uuid_automatically(): void
    {
        $paymentMethod =
            $this->createPaymentMethod();

        $this->assertNotNull(
            $paymentMethod->uuid
        );
    }

    public function test_payment_method_uses_uuid_as_route_key(): void
    {
        $paymentMethod =
            $this->createPaymentMethod();

        $this->assertSame(
            'uuid',
            $paymentMethod->getRouteKeyName()
        );
    }

    public function test_payment_method_belongs_to_tenant(): void
    {
        $paymentMethod =
            $this->createPaymentMethod();

        $this->assertInstanceOf(
            Tenant::class,
            $paymentMethod->tenant
        );

        $this->assertSame(
            $paymentMethod->tenant_id,
            $paymentMethod->tenant->id
        );
    }

    public function test_tenant_has_payment_methods(): void
    {
        $paymentMethod =
            $this->createPaymentMethod();

        $tenant =
            $paymentMethod->tenant;

        $this->assertTrue(
            $tenant
                ->paymentMethods
                ->contains($paymentMethod)
        );
    }

    public function test_payment_method_identifies_stripe_provider(): void
    {
        $paymentMethod =
            $this->createPaymentMethod([
                'provider' =>
                PaymentMethod::PROVIDER_STRIPE,
            ]);

        $this->assertTrue(
            $paymentMethod->isStripe()
        );
    }

    public function test_payment_method_identifies_card_type(): void
    {
        $paymentMethod =
            $this->createPaymentMethod([
                'type' =>
                PaymentMethod::TYPE_CARD,
            ]);

        $this->assertTrue(
            $paymentMethod->isCard()
        );
    }

    public function test_payment_method_preserves_safe_card_metadata(): void
    {
        $paymentMethod =
            $this->createPaymentMethod([
                'brand' => 'visa',
                'last_four' => '4242',
                'expires_month' => 12,
                'expires_year' => 2030,
            ]);

        $this->assertSame(
            'visa',
            $paymentMethod->brand
        );

        $this->assertSame(
            '4242',
            $paymentMethod->last_four
        );

        $this->assertSame(
            12,
            $paymentMethod->expires_month
        );

        $this->assertSame(
            2030,
            $paymentMethod->expires_year
        );
    }

    public function test_payment_method_boolean_fields_are_cast_correctly(): void
    {
        $paymentMethod =
            $this->createPaymentMethod([
                'is_default' => 1,
                'is_active' => 1,
            ]);

        $this->assertIsBool(
            $paymentMethod->is_default
        );

        $this->assertIsBool(
            $paymentMethod->is_active
        );

        $this->assertTrue(
            $paymentMethod->isDefault()
        );

        $this->assertTrue(
            $paymentMethod->isActive()
        );
    }

    public function test_provider_payment_method_id_must_be_unique_per_provider(): void
    {
        $this->createPaymentMethod([
            'provider' =>
            PaymentMethod::PROVIDER_STRIPE,

            'provider_payment_method_id' =>
            'pm_unique_123',
        ]);

        $this->expectException(
            QueryException::class
        );

        $this->createPaymentMethod([
            'provider' =>
            PaymentMethod::PROVIDER_STRIPE,

            'provider_payment_method_id' =>
            'pm_unique_123',
        ]);
    }

    public function test_default_payment_method_returns_active_default_method(): void
    {
        $tenant =
            $this->createTenant();

        app(TenantContext::class)->set(
            $tenant
        );

        $this->createPaymentMethodForTenant(
            $tenant,
            [
                'provider_payment_method_id' =>
                'pm_secondary',

                'is_default' =>
                false,
            ]
        );

        $default =
            $this->createPaymentMethodForTenant(
                $tenant,
                [
                    'provider_payment_method_id' =>
                    'pm_default',

                    'is_default' =>
                    true,
                ]
            );

        $this->assertTrue(
            $tenant
                ->defaultPaymentMethod()
                ->is($default)
        );
    }

    public function test_default_payment_method_ignores_inactive_method(): void
    {
        $tenant =
            $this->createTenant();

        app(TenantContext::class)->set(
            $tenant
        );

        $this->createPaymentMethodForTenant(
            $tenant,
            [
                'provider_payment_method_id' =>
                'pm_inactive',

                'is_default' =>
                true,

                'is_active' =>
                false,
            ]
        );

        $this->assertNull(
            $tenant->defaultPaymentMethod()
        );
    }

    public function test_payment_methods_are_isolated_by_tenant(): void
    {
        $tenantA =
            $this->createTenant(
                name: 'Tenant A',
                slug: 'tenant-a-' . uniqid()
            );

        app(TenantContext::class)->set(
            $tenantA
        );

        $paymentMethodA =
            $this->createPaymentMethodForTenant(
                $tenantA,
                [
                    'provider_payment_method_id' =>
                    'pm_tenant_a',
                ]
            );

        $tenantB =
            $this->createTenant(
                name: 'Tenant B',
                slug: 'tenant-b-' . uniqid()
            );

        app(TenantContext::class)->set(
            $tenantB
        );

        $this->assertNull(
            PaymentMethod::find(
                $paymentMethodA->id
            )
        );
    }

    private function createTenant(
        string $name = 'Consultorio Test',
        string $slug = 'consultorio-test',
    ): Tenant {
        return Tenant::create([
            'name' => $name,
            'slug' => $slug,
            'status' => 'active',
            'onboarding_completed_at' => now(),
        ]);
    }

    private function createPaymentMethod(
        array $attributes = []
    ): PaymentMethod {
        $tenant =
            $this->createTenant(
                slug: 'consultorio-test-' .
                    uniqid()
            );

        app(TenantContext::class)->set(
            $tenant
        );

        return $this
            ->createPaymentMethodForTenant(
                $tenant,
                $attributes
            );
    }

    private function createPaymentMethodForTenant(
        Tenant $tenant,
        array $attributes = []
    ): PaymentMethod {
        app(TenantContext::class)->set(
            $tenant
        );

        return PaymentMethod::create(
            array_merge([
                'provider' =>
                PaymentMethod::PROVIDER_STRIPE,

                'provider_payment_method_id' =>
                'pm_' . uniqid(),

                'type' =>
                PaymentMethod::TYPE_CARD,

                'brand' =>
                'visa',

                'last_four' =>
                '4242',

                'expires_month' =>
                12,

                'expires_year' =>
                2030,

                'is_default' =>
                false,

                'is_active' =>
                true,
            ], $attributes)
        );
    }
}
