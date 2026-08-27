<?php

namespace Tests\Feature\Subscription;

use App\Actions\Billing\RegisterStripePaymentMethod;
use App\Contracts\StripeCustomerApi;
use App\Contracts\StripePaymentMethodApi;
use App\Models\BillingCustomer;
use App\Models\PaymentMethod;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\Fakes\FakeStripeCustomerApi;
use Tests\Fakes\FakeStripePaymentMethodApi;
use Tests\TestCase;

class RegisterStripePaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    private FakeStripeCustomerApi $customers;

    private FakeStripePaymentMethodApi $paymentMethods;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customers =
            new FakeStripeCustomerApi();

        $this->paymentMethods =
            new FakeStripePaymentMethodApi();

        $this->app->instance(
            StripeCustomerApi::class,
            $this->customers
        );

        $this->app->instance(
            StripePaymentMethodApi::class,
            $this->paymentMethods
        );
    }

    public function test_registers_stripe_card_for_tenant(): void
    {
        $tenant =
            $this->tenantWithStripeCustomer();

        $this->paymentMethods->returnCard(
            id: 'pm_card_123',
            customer: 'cus_test_123',
        );

        $paymentMethod =
            app(
                RegisterStripePaymentMethod::class
            )->execute(
                $tenant,
                'pm_card_123'
            );

        $this->assertSame(
            $tenant->id,
            $paymentMethod->tenant_id
        );

        $this->assertSame(
            PaymentMethod::PROVIDER_STRIPE,
            $paymentMethod->provider
        );

        $this->assertSame(
            'pm_card_123',
            $paymentMethod
                ->provider_payment_method_id
        );
    }

    public function test_stores_only_safe_card_metadata(): void
    {
        $tenant =
            $this->tenantWithStripeCustomer();

        $this->paymentMethods->returnCard(
            customer: 'cus_test_123',
            brand: 'mastercard',
            lastFour: '4444',
            expiresMonth: 7,
            expiresYear: 2031,
        );

        $paymentMethod =
            app(
                RegisterStripePaymentMethod::class
            )->execute(
                $tenant,
                'pm_test_4242'
            );

        $this->assertSame(
            'mastercard',
            $paymentMethod->brand
        );

        $this->assertSame(
            '4444',
            $paymentMethod->last_four
        );

        $this->assertSame(
            7,
            $paymentMethod->expires_month
        );

        $this->assertSame(
            2031,
            $paymentMethod->expires_year
        );
    }

    public function test_registered_payment_method_becomes_default(): void
    {
        $tenant =
            $this->tenantWithStripeCustomer();

        $this->paymentMethods->returnCard(
            customer: 'cus_test_123'
        );

        $paymentMethod =
            app(
                RegisterStripePaymentMethod::class
            )->execute(
                $tenant,
                'pm_test_4242'
            );

        $this->assertTrue(
            $paymentMethod->isDefault()
        );

        $this->assertTrue(
            $tenant
                ->defaultPaymentMethod()
                ->is($paymentMethod)
        );
    }

    public function test_new_card_replaces_previous_default(): void
    {
        $tenant =
            $this->tenantWithStripeCustomer();

        $existing =
            PaymentMethod::withoutGlobalScopes()
            ->create([
                'tenant_id' =>
                $tenant->id,

                'provider' =>
                PaymentMethod::PROVIDER_STRIPE,

                'provider_payment_method_id' =>
                'pm_old',

                'type' =>
                PaymentMethod::TYPE_CARD,

                'brand' =>
                'visa',

                'last_four' =>
                '1111',

                'expires_month' =>
                1,

                'expires_year' =>
                2030,

                'is_default' =>
                true,

                'is_active' =>
                true,
            ]);

        $this->paymentMethods->returnCard(
            id: 'pm_new',
            customer: 'cus_test_123'
        );

        app(
            RegisterStripePaymentMethod::class
        )->execute(
            $tenant,
            'pm_new'
        );

        $this->assertFalse(
            $existing
                ->refresh()
                ->isDefault()
        );
    }

    public function test_payment_method_must_belong_to_tenant_stripe_customer(): void
    {
        $tenant =
            $this->tenantWithStripeCustomer();

        $this->paymentMethods->returnCard(
            customer: 'cus_someone_else'
        );

        $this->expectException(
            LogicException::class
        );

        app(
            RegisterStripePaymentMethod::class
        )->execute(
            $tenant,
            'pm_test_4242'
        );
    }

    public function test_re_registering_same_payment_method_is_idempotent(): void
    {
        $tenant =
            $this->tenantWithStripeCustomer();

        $this->paymentMethods->returnCard(
            customer: 'cus_test_123'
        );

        $first =
            app(
                RegisterStripePaymentMethod::class
            )->execute(
                $tenant,
                'pm_test_4242'
            );

        $second =
            app(
                RegisterStripePaymentMethod::class
            )->execute(
                $tenant,
                'pm_test_4242'
            );

        $this->assertTrue(
            $first->is($second)
        );

        $this->assertSame(
            1,
            PaymentMethod::withoutGlobalScopes()
                ->where(
                    'provider_payment_method_id',
                    'pm_test_4242'
                )
                ->count()
        );
    }

    private function tenantWithStripeCustomer(): Tenant
    {
        $tenant =
            Tenant::create([
                'name' =>
                'Consultorio Payment Method',

                'slug' =>
                'consultorio-payment-method-' .
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
                'cus_test_123',
            ]);

        return $tenant;
    }
}
