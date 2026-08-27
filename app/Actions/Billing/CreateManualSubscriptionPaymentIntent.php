<?php

namespace App\Actions\Billing;

use App\Contracts\StripePaymentIntentApi;
use App\Data\ManualSubscriptionPaymentIntent;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Tenant;
use Carbon\CarbonInterface;
use InvalidArgumentException;
use LogicException;

class CreateManualSubscriptionPaymentIntent
{
    public function __construct(
        private readonly EnsureStripeBillingCustomer $ensureCustomer,
        private readonly StripePaymentIntentApi $paymentIntents,
    ) {}

    public function execute(
        Tenant $tenant,
        string $billingCycle,
        CarbonInterface $attemptedAt,
        string $idempotencyKey,
        bool $saveForFuture = false,
    ): ManualSubscriptionPaymentIntent {
        $plan =
            config(
                sprintf(
                    'billing.plans.%s',
                    $billingCycle
                )
            );

        if (! is_array($plan)) {
            throw new InvalidArgumentException(
                sprintf(
                    'El ciclo de facturación "%s" no está disponible para checkout.',
                    $billingCycle
                )
            );
        }

        $amount =
            (int) ($plan['amount'] ?? 0);

        $currency =
            strtoupper(
                (string) ($plan['currency'] ?? '')
            );

        if ($amount <= 0) {
            throw new LogicException(
                'El plan no tiene un importe válido.'
            );
        }

        if ($currency === '') {
            throw new LogicException(
                'El plan no tiene una moneda válida.'
            );
        }

        $billingCustomer =
            $this->ensureCustomer->execute(
                $tenant
            );

        $payment =
            Payment::withoutGlobalScopes()
                ->where(
                    'idempotency_key',
                    $idempotencyKey
                )
                ->first();

        if (! $payment) {
            $payment =
                Payment::withoutGlobalScopes()
                    ->create([
                        'tenant_id' =>
                            $tenant->id,

                        'subscription_id' =>
                            null,

                        'billing_cycle' =>
                            $billingCycle,

                        'amount' =>
                            $amount,

                        'currency' =>
                            $currency,

                        'status' =>
                            Payment::STATUS_PENDING,

                        'attempted_at' =>
                            $attemptedAt,

                        'provider' =>
                            'stripe',

                        'idempotency_key' =>
                            $idempotencyKey,
                    ]);
        }

        if ($payment->tenant_id !== $tenant->id) {
            throw new LogicException(
                'La llave de idempotencia pertenece a otro tenant.'
            );
        }

        if (! $payment->isPending()) {
            throw new LogicException(
                'El pago manual ya fue procesado.'
            );
        }

        $params = [
            'amount' =>
                $payment->amount,

            'currency' =>
                strtolower(
                    $payment->currency
                ),

            'customer' =>
                $billingCustomer
                    ->provider_customer_id,

            'payment_method_types' => [
                'card',
            ],

            'metadata' => [
                'doctotal_payment_uuid' =>
                    $payment->uuid,

                'doctotal_tenant_id' =>
                    (string) $tenant->id,

                'billing_cycle' =>
                    $billingCycle,

                'payment_mode' =>
                    'manual',

                'save_for_future' =>
                    $saveForFuture
                        ? '1'
                        : '0',
            ],
        ];

        if ($saveForFuture) {
            $params['setup_future_usage'] =
                'off_session';
        }

        $paymentIntent =
            $this->paymentIntents->create(
                $params,
                [
                    'idempotency_key' =>
                        $payment->idempotency_key,
                ]
            );

        if (! $paymentIntent->id) {
            throw new LogicException(
                'Stripe no devolvió un PaymentIntent válido.'
            );
        }

        if (! $paymentIntent->client_secret) {
            throw new LogicException(
                'Stripe no devolvió un client_secret.'
            );
        }

        $payment->update([
            'provider_payment_id' =>
                $paymentIntent->id,
        ]);

        return new ManualSubscriptionPaymentIntent(
            payment: $payment->refresh(),

            clientSecret: $paymentIntent->client_secret,
        );
    }
}
