<?php

namespace App\Actions\Billing;

use App\Contracts\StripeSetupIntentApi;
use App\Models\Tenant;
use LogicException;

class CreateStripeSetupIntent
{
    public function __construct(
        private readonly EnsureStripeBillingCustomer $ensureCustomer,
        private readonly StripeSetupIntentApi $setupIntents,
    ) {}

    public function execute(
        Tenant $tenant
    ): string {
        $billingCustomer =
            $this->ensureCustomer->execute(
                $tenant
            );

        $setupIntent =
            $this->setupIntents->create(
                [
                    'customer' =>
                    $billingCustomer
                        ->provider_customer_id,

                    'payment_method_types' => [
                        'card',
                    ],

                    'usage' =>
                    'off_session',

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
                        'doctotal:tenant:%s:setup-intent:%s',
                        $tenant->uuid,
                        now()->format('YmdHi')
                    ),
                ]
            );

        if (! $setupIntent->client_secret) {
            throw new LogicException(
                'Stripe no devolvió un client_secret para el SetupIntent.'
            );
        }

        return $setupIntent->client_secret;
    }
}
