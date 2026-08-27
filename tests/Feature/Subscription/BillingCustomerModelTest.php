<?php

namespace Tests\Feature\Subscription;

use App\Models\BillingCustomer;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingCustomerModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_customer_generates_uuid_automatically(): void
    {
        $billingCustomer =
            $this->createBillingCustomer();

        $this->assertNotNull(
            $billingCustomer->uuid
        );
    }

    public function test_billing_customer_uses_uuid_as_route_key(): void
    {
        $billingCustomer =
            $this->createBillingCustomer();

        $this->assertSame(
            'uuid',
            $billingCustomer->getRouteKeyName()
        );
    }

    public function test_billing_customer_belongs_to_tenant(): void
    {
        $billingCustomer =
            $this->createBillingCustomer();

        $this->assertInstanceOf(
            Tenant::class,
            $billingCustomer->tenant
        );

        $this->assertSame(
            $billingCustomer->tenant_id,
            $billingCustomer->tenant->id
        );
    }

    public function test_tenant_has_billing_customers(): void
    {
        $billingCustomer =
            $this->createBillingCustomer();

        $tenant =
            $billingCustomer->tenant;

        $this->assertTrue(
            $tenant
                ->billingCustomers
                ->contains($billingCustomer)
        );
    }

    public function test_billing_customer_identifies_stripe_provider(): void
    {
        $billingCustomer =
            $this->createBillingCustomer([
                'provider' =>
                BillingCustomer::PROVIDER_STRIPE,
            ]);

        $this->assertTrue(
            $billingCustomer->isStripe()
        );
    }

    public function test_tenant_returns_stripe_billing_customer(): void
    {
        $tenant =
            $this->createTenant();

        app(TenantContext::class)->set(
            $tenant
        );

        $stripeCustomer =
            $this->createForTenant(
                $tenant,
                [
                    'provider' =>
                    BillingCustomer::PROVIDER_STRIPE,

                    'provider_customer_id' =>
                    'cus_stripe_123',
                ]
            );

        $this->assertTrue(
            $tenant
                ->stripeBillingCustomer()
                ->is($stripeCustomer)
        );
    }

    public function test_tenant_can_have_only_one_billing_customer_per_provider(): void
    {
        $tenant =
            $this->createTenant();

        app(TenantContext::class)->set(
            $tenant
        );

        $this->createForTenant(
            $tenant,
            [
                'provider' =>
                BillingCustomer::PROVIDER_STRIPE,

                'provider_customer_id' =>
                'cus_first',
            ]
        );

        $this->expectException(
            QueryException::class
        );

        $this->createForTenant(
            $tenant,
            [
                'provider' =>
                BillingCustomer::PROVIDER_STRIPE,

                'provider_customer_id' =>
                'cus_second',
            ]
        );
    }

    public function test_provider_customer_id_must_be_unique_per_provider(): void
    {
        $tenantA =
            $this->createTenant(
                name: 'Tenant A',
                slug: 'tenant-a-' . uniqid()
            );

        app(TenantContext::class)->set(
            $tenantA
        );

        $this->createForTenant(
            $tenantA,
            [
                'provider' =>
                BillingCustomer::PROVIDER_STRIPE,

                'provider_customer_id' =>
                'cus_shared',
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

        $this->expectException(
            QueryException::class
        );

        $this->createForTenant(
            $tenantB,
            [
                'provider' =>
                BillingCustomer::PROVIDER_STRIPE,

                'provider_customer_id' =>
                'cus_shared',
            ]
        );
    }

    public function test_billing_customers_are_isolated_by_tenant(): void
    {
        $tenantA =
            $this->createTenant(
                name: 'Tenant A',
                slug: 'tenant-a-' . uniqid()
            );

        app(TenantContext::class)->set(
            $tenantA
        );

        $billingCustomerA =
            $this->createForTenant(
                $tenantA,
                [
                    'provider_customer_id' =>
                    'cus_tenant_a',
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
            BillingCustomer::find(
                $billingCustomerA->id
            )
        );
    }

    private function createTenant(
        string $name = 'Consultorio Billing',
        string $slug = 'consultorio-billing',
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

    private function createBillingCustomer(
        array $attributes = []
    ): BillingCustomer {
        $tenant =
            $this->createTenant(
                slug: 'consultorio-billing-' .
                    uniqid()
            );

        return $this->createForTenant(
            $tenant,
            $attributes
        );
    }

    private function createForTenant(
        Tenant $tenant,
        array $attributes = []
    ): BillingCustomer {
        app(TenantContext::class)->set(
            $tenant
        );

        return BillingCustomer::create(
            array_merge([
                'provider' =>
                BillingCustomer::PROVIDER_STRIPE,

                'provider_customer_id' =>
                'cus_' . uniqid(),
            ], $attributes)
        );
    }
}
