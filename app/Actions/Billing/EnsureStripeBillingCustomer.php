<?php

namespace App\Actions\Billing;

use App\Contracts\StripeCustomerApi;
use App\Models\BillingCustomer;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use LogicException;

class EnsureStripeBillingCustomer
{
    public function __construct(
        private readonly StripeCustomerApi $stripeCustomers
    ) {}

    public function execute(
        Tenant $tenant
    ): BillingCustomer {
        $existing =
            BillingCustomer::query()
            ->withoutGlobalScope(
                TenantScope::class
            )
            ->where(
                'tenant_id',
                $tenant->id
            )
            ->where(
                'provider',
                BillingCustomer::PROVIDER_STRIPE
            )
            ->first();

        if ($existing) {
            return $existing;
        }

        $stripeCustomer =
            $this->stripeCustomers->create(
                [
                    'name' =>
                    $tenant->name,

                    'metadata' => [
                        'doctotal_tenant_id' =>
                        (string) $tenant->id,

                        'doctotal_tenant_uuid' =>
                        $tenant->uuid,
                    ],
                ],
                [
                    'idempotency_key' =>
                    sprintf(
                        'doctotal:tenant:%s:stripe-customer',
                        $tenant->uuid
                    ),
                ]
            );

        if (! $stripeCustomer->id) {
            throw new LogicException(
                'Stripe no devolvió un identificador de cliente válido.'
            );
        }

        return BillingCustomer::query()
            ->withoutGlobalScope(
                TenantScope::class
            )
            ->create([
                'tenant_id' =>
                $tenant->id,

                'provider' =>
                BillingCustomer::PROVIDER_STRIPE,

                'provider_customer_id' =>
                $stripeCustomer->id,
            ]);
    }
}
