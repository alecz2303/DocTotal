<?php

namespace App\Actions\Billing;

use App\Contracts\StripePaymentMethodApi;
use App\Models\PaymentMethod;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use LogicException;

class RegisterStripePaymentMethod
{
    public function __construct(
        private readonly EnsureStripeBillingCustomer $ensureCustomer,
        private readonly StripePaymentMethodApi $paymentMethods,
    ) {}

    public function execute(
        Tenant $tenant,
        string $providerPaymentMethodId,
    ): PaymentMethod {
        $billingCustomer =
            $this->ensureCustomer->execute(
                $tenant
            );

        $stripePaymentMethod =
            $this->paymentMethods->retrieve(
                $providerPaymentMethodId
            );

        if (
            $stripePaymentMethod->id !==
            $providerPaymentMethodId
        ) {
            throw new LogicException(
                'Stripe devolvió un método de pago diferente al solicitado.'
            );
        }

        if (
            $stripePaymentMethod->customer !==
            $billingCustomer->provider_customer_id
        ) {
            throw new LogicException(
                'El método de pago no pertenece al cliente Stripe del tenant.'
            );
        }

        if (
            $stripePaymentMethod->type !==
            PaymentMethod::TYPE_CARD
            || ! $stripePaymentMethod->card
        ) {
            throw new LogicException(
                'El método de pago Stripe no es una tarjeta compatible.'
            );
        }

        return DB::transaction(
            function () use (
                $tenant,
                $stripePaymentMethod,
            ): PaymentMethod {
                $paymentMethod =
                    PaymentMethod::query()
                    ->withoutGlobalScope(
                        TenantScope::class
                    )
                    ->updateOrCreate(
                        [
                            'provider' =>
                            PaymentMethod::PROVIDER_STRIPE,

                            'provider_payment_method_id' =>
                            $stripePaymentMethod->id,
                        ],
                        [
                            'tenant_id' =>
                            $tenant->id,

                            'type' =>
                            PaymentMethod::TYPE_CARD,

                            'brand' =>
                            $stripePaymentMethod
                                ->card
                                ->brand,

                            'last_four' =>
                            $stripePaymentMethod
                                ->card
                                ->last4,

                            'expires_month' =>
                            $stripePaymentMethod
                                ->card
                                ->exp_month,

                            'expires_year' =>
                            $stripePaymentMethod
                                ->card
                                ->exp_year,

                            'is_active' =>
                            true,
                        ]
                    );

                $paymentMethod->setAsDefault();

                return $paymentMethod->refresh();
            }
        );
    }
}
