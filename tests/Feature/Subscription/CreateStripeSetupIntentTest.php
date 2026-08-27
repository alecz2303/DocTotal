<?php

namespace Tests\Feature\Subscription;

use App\Actions\Billing\CreateStripeSetupIntent;
use App\Contracts\StripeCustomerApi;
use App\Contracts\StripeSetupIntentApi;
use App\Models\BillingCustomer;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakeStripeCustomerApi;
use Tests\Fakes\FakeStripeSetupIntentApi;
use Tests\TestCase;

class CreateStripeSetupIntentTest extends TestCase
{
    use RefreshDatabase;

    private FakeStripeCustomerApi $customers;

    private FakeStripeSetupIntentApi $setupIntents;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customers =
            new FakeStripeCustomerApi();

        $this->setupIntents =
            new FakeStripeSetupIntentApi();

        $this->app->instance(
            StripeCustomerApi::class,
            $this->customers
        );

        $this->app->instance(
            StripeSetupIntentApi::class,
            $this->setupIntents
        );
    }

    public function test_creates_setup_intent_for_stripe_customer(): void
    {
        $tenant =
            $this->createTenant();

        $this->customers->returnCustomer(
            'cus_setup_123'
        );

        $this->setupIntents
            ->returnSetupIntent();

        app(
            CreateStripeSetupIntent::class
        )->execute(
            $tenant
        );

        $this->assertSame(
            'cus_setup_123',
            $this->setupIntents
                ->receivedParams['customer']
        );
    }

    public function test_setup_intent_is_for_card_and_off_session_usage(): void
    {
        $tenant =
            $this->createTenant();

        $this->customers->returnCustomer();

        $this->setupIntents
            ->returnSetupIntent();

        app(
            CreateStripeSetupIntent::class
        )->execute(
            $tenant
        );

        $this->assertSame(
            ['card'],
            $this->setupIntents
                ->receivedParams['payment_method_types']
        );

        $this->assertSame(
            'off_session',
            $this->setupIntents
                ->receivedParams['usage']
        );
    }

    public function test_setup_intent_contains_tenant_metadata(): void
    {
        $tenant =
            $this->createTenant();

        $this->customers->returnCustomer();

        $this->setupIntents
            ->returnSetupIntent();

        app(
            CreateStripeSetupIntent::class
        )->execute(
            $tenant
        );

        $metadata =
            $this->setupIntents
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

    public function test_returns_setup_intent_client_secret(): void
    {
        $tenant =
            $this->createTenant();

        $this->customers->returnCustomer();

        $this->setupIntents
            ->returnSetupIntent(
                clientSecret: 'seti_123_secret_xyz'
            );

        $clientSecret =
            app(
                CreateStripeSetupIntent::class
            )->execute(
                $tenant
            );

        $this->assertSame(
            'seti_123_secret_xyz',
            $clientSecret
        );
    }

    public function test_existing_billing_customer_is_reused(): void
    {
        $tenant =
            $this->createTenant();

        BillingCustomer::withoutGlobalScopes()
            ->create([
                'tenant_id' =>
                $tenant->id,

                'provider' =>
                BillingCustomer::PROVIDER_STRIPE,

                'provider_customer_id' =>
                'cus_existing_setup',
            ]);

        $this->setupIntents
            ->returnSetupIntent();

        app(
            CreateStripeSetupIntent::class
        )->execute(
            $tenant
        );

        $this->assertSame(
            0,
            $this->customers->callCount
        );

        $this->assertSame(
            'cus_existing_setup',
            $this->setupIntents
                ->receivedParams['customer']
        );
    }

    private function createTenant(): Tenant
    {
        return Tenant::create([
            'name' =>
            'Consultorio SetupIntent',

            'slug' =>
            'consultorio-setup-' .
                uniqid(),

            'status' =>
            'active',

            'onboarding_completed_at' =>
            now(),
        ]);
    }
}
