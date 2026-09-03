<?php

namespace App\Actions\Billing;

use App\Contracts\StripePaymentIntentApi;
use App\Data\ManualSubscriptionPaymentIntent;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Tenant;
use Carbon\CarbonInterface;
use LogicException;

class CreateManualSubscriptionRecoveryPaymentIntent
{
    public function __construct(
        private readonly EnsureStripeBillingCustomer $ensureCustomer,
        private readonly StripePaymentIntentApi $paymentIntents,
        private readonly ReservePromotionalCredits $reservePromotionalCredits,
        private readonly ResolveSubscriptionRecoveryPlan $resolveRecoveryPlan,
    ) {}

    public function execute(
        Tenant $tenant,
        Subscription $subscription,
        CarbonInterface $attemptedAt,
        string $idempotencyKey,
        bool $saveForFuture = false,
    ): ManualSubscriptionPaymentIntent {
        $subscription->refresh();

        if (
            $subscription->tenant_id !==
            $tenant->id
        ) {
            throw new LogicException(
                'La suscripción no pertenece al tenant.'
            );
        }

        if (! $subscription->isPastDue()) {
            throw new LogicException(
                'El pago manual de recuperación requiere una suscripción vencida.'
            );
        }

        $recoveryPlan =
            $this->resolveRecoveryPlan
            ->execute(
                $subscription
            );

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
            $amountBreakdown =
                app(
                    CalculatePaymentAmount::class
                )->execute(
                    $tenant,
                    $recoveryPlan['amount']
                );

            $payment =
                Payment::withoutGlobalScopes()
                ->create([
                    'tenant_id' =>
                    $tenant->id,

                    'subscription_id' =>
                    $subscription->id,

                    'billing_cycle' =>
                    $recoveryPlan['billing_cycle'],

                    'gross_amount' =>
                    $amountBreakdown['gross_amount'],

                    'referral_discount_amount' =>
                    $amountBreakdown['referral_discount_amount'],

                    'promotional_credit_amount' =>
                    $amountBreakdown['promotional_credit_amount'],

                    'amount' =>
                    $amountBreakdown['amount'],

                    'currency' =>
                    $recoveryPlan['currency'],

                    'status' =>
                    Payment::STATUS_PENDING,

                    'attempted_at' =>
                    $attemptedAt,

                    'provider' =>
                    'stripe',

                    'idempotency_key' =>
                    $idempotencyKey,
                ]);

            /*
             * Reservamos los créditos antes de crear el
             * PaymentIntent para que Stripe reciba el
             * importe neto definitivo.
             */
            $payment =
                $this->reservePromotionalCredits
                ->execute(
                    $payment
                );
        }

        if (
            $payment->tenant_id !==
            $tenant->id
            || $payment->subscription_id !==
            $subscription->id
        ) {
            throw new LogicException(
                'La llave de idempotencia pertenece a otra operación de facturación.'
            );
        }

        if (! $payment->isPending()) {
            throw new LogicException(
                'El pago de recuperación ya fue procesado.'
            );
        }

        if (
            $payment->billing_cycle !==
            $recoveryPlan['billing_cycle']
            || $payment->contractualAmount() !==
            $recoveryPlan['amount']
            || strtoupper(
                $payment->currency
            ) !==
            $recoveryPlan['currency']
        ) {
            throw new LogicException(
                'El pago pendiente ya no coincide con el plan de recuperación seleccionado.'
            );
        }

        /*
         * Reutilizamos el PaymentIntent únicamente si el checkout
         * sigue representando el mismo plan, importe y moneda.
         */
        if ($payment->provider_payment_id) {
            $paymentIntent =
                $this->paymentIntents->retrieve(
                    $payment->provider_payment_id
                );

            if (
                $paymentIntent->id !==
                $payment->provider_payment_id
            ) {
                throw new LogicException(
                    'Stripe devolvió un PaymentIntent diferente al esperado.'
                );
            }

            if (! $paymentIntent->client_secret) {
                throw new LogicException(
                    'Stripe no devolvió el client_secret del PaymentIntent pendiente.'
                );
            }

            if (
                $paymentIntent->status ===
                'canceled'
            ) {
                throw new LogicException(
                    'El PaymentIntent pendiente fue cancelado en Stripe.'
                );
            }

            return new ManualSubscriptionPaymentIntent(
                payment: $payment->refresh(),
                clientSecret: $paymentIntent->client_secret,
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

                'subscription_id' =>
                (string) $subscription->id,

                'billing_cycle' =>
                $payment->billing_cycle,

                'payment_mode' =>
                'manual_recovery',

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

        if (
            ! $paymentIntent->id
            || ! $paymentIntent->client_secret
        ) {
            throw new LogicException(
                'Stripe no devolvió un PaymentIntent válido para recuperar la suscripción.'
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
