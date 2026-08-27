<?php

namespace Tests\Feature\Subscription;

use App\Actions\Billing\RemoveStripePaymentMethod;
use App\Contracts\StripePaymentMethodApi;
use App\Models\BillingCustomer;
use App\Models\PaymentMethod;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\Fakes\FakeStripePaymentMethodApi;
use Tests\TestCase;

class RemoveStripePaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    private FakeStripePaymentMethodApi $stripe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stripe =
            new FakeStripePaymentMethodApi();

        $this->app->instance(
            StripePaymentMethodApi::class,
            $this->stripe
        );
    }

    public function test_detaches_payment_method_from_stripe(): void
    {
        [$tenant, $paymentMethod] =
            $this->scenario();

        $this->stripe->returnCard(
            id: 'pm_remove_123',
            customer: 'cus_remove_123',
        );

        $this->stripe->returnDetachedCard(
            'pm_remove_123'
        );

        $this->action()->execute(
            $tenant,
            $paymentMethod
        );

        $this->assertSame(
            'pm_remove_123',
            $this->stripe
                ->receivedDetachPaymentMethodId
        );

        $this->assertSame(
            1,
            $this->stripe->detachCallCount
        );
    }

    public function test_removed_payment_method_becomes_inactive(): void
    {
        [$tenant, $paymentMethod] =
            $this->scenario();

        $this->prepareStripe();

        $result =
            $this->action()->execute(
                $tenant,
                $paymentMethod
            );

        $this->assertFalse(
            $result->isActive()
        );
    }

    public function test_removed_payment_method_is_no_longer_default(): void
    {
        [$tenant, $paymentMethod] =
            $this->scenario();

        $this->prepareStripe();

        $result =
            $this->action()->execute(
                $tenant,
                $paymentMethod
            );

        $this->assertFalse(
            $result->isDefault()
        );

        $this->assertNull(
            $tenant->defaultPaymentMethod()
        );
    }

    public function test_payment_method_from_another_tenant_cannot_be_removed(): void
    {
        [$tenant, $paymentMethod] =
            $this->scenario();

        $otherTenant =
            Tenant::create([
                'name' =>
                'Otro Consultorio',

                'slug' =>
                'otro-consultorio-' .
                    uniqid(),

                'status' =>
                'active',

                'onboarding_completed_at' =>
                now(),
            ]);

        $this->expectException(
            LogicException::class
        );

        $this->action()->execute(
            $otherTenant,
            $paymentMethod
        );
    }

    public function test_payment_method_must_belong_to_tenant_stripe_customer(): void
    {
        [$tenant, $paymentMethod] =
            $this->scenario();

        $this->stripe->returnCard(
            id: 'pm_remove_123',
            customer: 'cus_someone_else',
        );

        $this->expectException(
            LogicException::class
        );

        $this->action()->execute(
            $tenant,
            $paymentMethod
        );
    }

    public function test_removing_inactive_payment_method_is_idempotent(): void
    {
        [$tenant, $paymentMethod] =
            $this->scenario();

        $paymentMethod->deactivate();

        $result =
            $this->action()->execute(
                $tenant,
                $paymentMethod
            );

        $this->assertFalse(
            $result->isActive()
        );

        $this->assertSame(
            0,
            $this->stripe->retrieveCallCount
        );

        $this->assertSame(
            0,
            $this->stripe->detachCallCount
        );
    }

    public function test_already_detached_stripe_method_is_deactivated_locally(): void
    {
        [$tenant, $paymentMethod] =
            $this->scenario();

        $this->stripe->returnCard(
            id: 'pm_remove_123',
            customer: null,
        );

        $result =
            $this->action()->execute(
                $tenant,
                $paymentMethod
            );

        $this->assertFalse(
            $result->isActive()
        );

        $this->assertSame(
            0,
            $this->stripe->detachCallCount
        );
    }

    private function action(): RemoveStripePaymentMethod
    {
        return app(
            RemoveStripePaymentMethod::class
        );
    }

    private function prepareStripe(): void
    {
        $this->stripe->returnCard(
            id: 'pm_remove_123',
            customer: 'cus_remove_123',
        );

        $this->stripe->returnDetachedCard(
            'pm_remove_123'
        );
    }

    private function scenario(): array
    {
        $tenant =
            Tenant::create([
                'name' =>
                'Consultorio Remove Card',

                'slug' =>
                'remove-card-' .
                    uniqid(),

                'status' =>
                'active',

                'onboarding_completed_at' =>
                now(),
            ]);

        BillingCustomer::withoutGlobalScopes()
            ->create([
                'tenant_id' =>
                $tenant->id,

                'provider' =>
                BillingCustomer::PROVIDER_STRIPE,

                'provider_customer_id' =>
                'cus_remove_123',
            ]);

        $paymentMethod =
            PaymentMethod::withoutGlobalScopes()
            ->create([
                'tenant_id' =>
                $tenant->id,

                'provider' =>
                PaymentMethod::PROVIDER_STRIPE,

                'provider_payment_method_id' =>
                'pm_remove_123',

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
                true,

                'is_active' =>
                true,
            ]);

        return [
            $tenant,
            $paymentMethod,
        ];
    }
}
