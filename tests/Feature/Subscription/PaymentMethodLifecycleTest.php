<?php

namespace Tests\Feature\Subscription;

use App\Models\PaymentMethod;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class PaymentMethodLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_payment_method_can_become_default(): void
    {
        [$tenant, $paymentMethod] =
            $this->createPaymentMethod();

        $this->assertFalse(
            $paymentMethod->isDefault()
        );

        $paymentMethod->setAsDefault();

        $this->assertTrue(
            $paymentMethod->isDefault()
        );

        $this->assertTrue(
            $tenant
                ->defaultPaymentMethod()
                ->is($paymentMethod)
        );
    }

    public function test_setting_new_default_removes_previous_default(): void
    {
        [$tenant, $first] =
            $this->createPaymentMethod([
                'is_default' =>
                true,
            ]);

        $second =
            $this->createForTenant(
                $tenant,
                [
                    'provider_payment_method_id' =>
                    'pm_second',

                    'is_default' =>
                    false,
                ]
            );

        $second->setAsDefault();

        $first->refresh();
        $second->refresh();

        $this->assertFalse(
            $first->isDefault()
        );

        $this->assertTrue(
            $second->isDefault()
        );

        $this->assertTrue(
            $tenant
                ->defaultPaymentMethod()
                ->is($second)
        );
    }

    public function test_setting_default_does_not_affect_another_tenant(): void
    {
        [$tenantA, $methodA] =
            $this->createPaymentMethod([
                'is_default' =>
                true,
            ]);

        $tenantB =
            $this->createTenant(
                'Tenant B',
                'tenant-b-' . uniqid()
            );

        $methodB =
            $this->createForTenant(
                $tenantB,
                [
                    'provider_payment_method_id' =>
                    'pm_tenant_b',

                    'is_default' =>
                    true,
                ]
            );

        app(TenantContext::class)->set(
            $tenantA
        );

        $secondA =
            $this->createForTenant(
                $tenantA,
                [
                    'provider_payment_method_id' =>
                    'pm_second_a',
                ]
            );

        $secondA->setAsDefault();

        $methodA->refresh();
        $methodB->refresh();

        $this->assertFalse(
            $methodA->isDefault()
        );

        $this->assertTrue(
            $methodB->isDefault()
        );
    }

    public function test_inactive_payment_method_cannot_become_default(): void
    {
        [, $paymentMethod] =
            $this->createPaymentMethod([
                'is_active' =>
                false,
            ]);

        $this->expectException(
            LogicException::class
        );

        $paymentMethod->setAsDefault();
    }

    public function test_default_payment_method_can_be_deactivated(): void
    {
        [$tenant, $paymentMethod] =
            $this->createPaymentMethod([
                'is_default' =>
                true,
            ]);

        $paymentMethod->deactivate();

        $paymentMethod->refresh();

        $this->assertFalse(
            $paymentMethod->isActive()
        );

        $this->assertFalse(
            $paymentMethod->isDefault()
        );

        $this->assertNull(
            $tenant->defaultPaymentMethod()
        );
    }

    public function test_deactivating_default_does_not_select_another_method_automatically(): void
    {
        [$tenant, $default] =
            $this->createPaymentMethod([
                'is_default' =>
                true,
            ]);

        $secondary =
            $this->createForTenant(
                $tenant,
                [
                    'provider_payment_method_id' =>
                    'pm_secondary',

                    'is_default' =>
                    false,
                ]
            );

        $default->deactivate();

        $secondary->refresh();

        $this->assertFalse(
            $secondary->isDefault()
        );

        $this->assertNull(
            $tenant->defaultPaymentMethod()
        );
    }

    public function test_deactivate_is_idempotent(): void
    {
        [, $paymentMethod] =
            $this->createPaymentMethod();

        $paymentMethod->deactivate();

        $paymentMethod->deactivate();

        $paymentMethod->refresh();

        $this->assertFalse(
            $paymentMethod->isActive()
        );

        $this->assertFalse(
            $paymentMethod->isDefault()
        );
    }

    public function test_setting_already_default_method_is_idempotent(): void
    {
        [$tenant, $paymentMethod] =
            $this->createPaymentMethod([
                'is_default' =>
                true,
            ]);

        $paymentMethod->setAsDefault();

        $paymentMethod->setAsDefault();

        $this->assertTrue(
            $paymentMethod
                ->refresh()
                ->isDefault()
        );

        $this->assertTrue(
            $tenant
                ->defaultPaymentMethod()
                ->is($paymentMethod)
        );
    }

    private function createPaymentMethod(
        array $attributes = []
    ): array {
        $tenant =
            $this->createTenant(
                'Consultorio Test',
                'consultorio-' . uniqid()
            );

        $paymentMethod =
            $this->createForTenant(
                $tenant,
                $attributes
            );

        return [
            $tenant,
            $paymentMethod,
        ];
    }

    private function createTenant(
        string $name,
        string $slug,
    ): Tenant {
        return Tenant::create([
            'name' =>
            $name,

            'slug' =>
            $slug,

            'status' =>
            'active',

            'onboarding_completed_at' =>
            now(),
        ]);
    }

    private function createForTenant(
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
