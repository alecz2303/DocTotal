<?php

namespace Tests\Feature\Subscription;

use App\Actions\Billing\EnsureStripeBillingCustomer;
use App\Contracts\StripeCustomerApi;
use App\Models\BillingCustomer;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Fakes\FakeStripeCustomerApi;
use Tests\TestCase;

class EnsureStripeBillingCustomerTest extends TestCase
{
    use RefreshDatabase;

    private FakeStripeCustomerApi $stripe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stripe =
            new FakeStripeCustomerApi();

        $this->app->instance(
            StripeCustomerApi::class,
            $this->stripe
        );
    }

    public function test_creates_stripe_customer_for_tenant(): void
    {
        $tenant =
            $this->createTenant();

        $this->stripe->returnCustomer(
            'cus_doctotal_123'
        );

        $billingCustomer =
            app(
                EnsureStripeBillingCustomer::class
            )->execute(
                $tenant
            );

        $this->assertSame(
            BillingCustomer::PROVIDER_STRIPE,
            $billingCustomer->provider
        );

        $this->assertSame(
            'cus_doctotal_123',
            $billingCustomer
                ->provider_customer_id
        );

        $this->assertSame(
            $tenant->id,
            $billingCustomer->tenant_id
        );
    }

    public function test_persists_created_stripe_customer(): void
    {
        $tenant =
            $this->createTenant();

        $this->stripe->returnCustomer(
            'cus_persisted_123'
        );

        app(
            EnsureStripeBillingCustomer::class
        )->execute(
            $tenant
        );

        $this->assertDatabaseHas(
            'billing_customers',
            [
                'tenant_id' =>
                $tenant->id,

                'provider' =>
                BillingCustomer::PROVIDER_STRIPE,

                'provider_customer_id' =>
                'cus_persisted_123',
            ]
        );
    }

    public function test_sends_tenant_identity_to_stripe(): void
    {
        $tenant =
            $this->createTenant();

        $this->stripe->returnCustomer();

        app(
            EnsureStripeBillingCustomer::class
        )->execute(
            $tenant
        );

        $this->assertSame(
            $tenant->name,
            $this->stripe
                ->receivedParams['name']
        );

        $metadata =
            $this->stripe
                ->receivedParams['metadata'];

        $this->assertSame(
            (string) $tenant->id,
            $metadata['doctotal_tenant_id']
        );

        $this->assertSame(
            $tenant->uuid,
            $metadata['doctotal_tenant_uuid']
        );
    }

    public function test_uses_stable_stripe_idempotency_key(): void
    {
        $tenant =
            $this->createTenant();

        $this->stripe->returnCustomer();

        app(
            EnsureStripeBillingCustomer::class
        )->execute(
            $tenant
        );

        $this->assertSame(
            sprintf(
                'doctotal:tenant:%s:stripe-customer',
                $tenant->uuid
            ),
            $this->stripe
                ->receivedOptions['idempotency_key']
        );
    }

    public function test_existing_stripe_customer_is_reused(): void
    {
        $tenant =
            $this->createTenant();

        $existing =
            BillingCustomer::create([
                'tenant_id' =>
                $tenant->id,

                'provider' =>
                BillingCustomer::PROVIDER_STRIPE,

                'provider_customer_id' =>
                'cus_existing_123',
            ]);

        $result =
            app(
                EnsureStripeBillingCustomer::class
            )->execute(
                $tenant
            );

        $this->assertTrue(
            $result->is($existing)
        );

        $this->assertSame(
            0,
            $this->stripe->callCount
        );
    }

    public function test_calling_action_twice_does_not_create_second_customer(): void
    {
        $tenant =
            $this->createTenant();

        $this->stripe->returnCustomer(
            'cus_once_123'
        );

        $first =
            app(
                EnsureStripeBillingCustomer::class
            )->execute(
                $tenant
            );

        $second =
            app(
                EnsureStripeBillingCustomer::class
            )->execute(
                $tenant
            );

        $this->assertTrue(
            $first->is($second)
        );

        $this->assertSame(
            1,
            $this->stripe->callCount
        );

        $this->assertSame(
            1,
            BillingCustomer::withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $tenant->id
                )
                ->where(
                    'provider',
                    BillingCustomer::PROVIDER_STRIPE
                )
                ->count()
        );
    }

    public function test_stripe_infrastructure_error_is_propagated(): void
    {
        $tenant =
            $this->createTenant();

        $this->stripe->throwException(
            new RuntimeException(
                'Stripe unavailable.'
            )
        );

        $this->expectException(
            RuntimeException::class
        );

        app(
            EnsureStripeBillingCustomer::class
        )->execute(
            $tenant
        );
    }

    private function createTenant(): Tenant
    {
        return Tenant::create([
            'name' =>
            'Consultorio Stripe Test',

            'slug' =>
            'consultorio-stripe-' .
                uniqid(),

            'status' =>
            'active',

            'onboarding_completed_at' =>
            now(),
        ]);
    }
}
