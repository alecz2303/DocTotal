<?php

namespace App\Actions\Billing;

use App\Actions\Subscription\RenewSubscription;
use App\Contracts\PaymentGateway;
use App\Models\Payment;
use App\Models\Subscription;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use LogicException;

class AttemptSubscriptionPayment
{
    public function __construct(
        private readonly PaymentGateway $gateway
    ) {}

    public function execute(
        Subscription $subscription,
        CarbonInterface $attemptedAt,
        string $idempotencyKey,
        bool $isRetry = false,
    ): Payment {
        return DB::transaction(
            function () use (
                $subscription,
                $attemptedAt,
                $idempotencyKey,
                $isRetry,
            ): Payment {
                $subscription->refresh();

                /*
                 * Un cambio programado se vuelve contractual justo en
                 * el intento de renovación. Esto sucede dentro de la misma
                 * transacción que crea Payment.
                 *
                 * Si Stripe rechaza la tarjeta, el nuevo ciclo permanece
                 * aplicado porque esa es la deuda que acaba de generarse.
                 * Si ocurre una excepción inesperada, la transacción local
                 * revierte y Stripe conserva la misma idempotency key.
                 */
                if (
                    ! $isRetry
                    && $subscription->pending_billing_cycle
                ) {
                    $subscription = app(
                        ApplyPendingSubscriptionPlan::class
                    )->execute(
                        $subscription,
                        $attemptedAt
                    );
                }

                if (
                    ! $subscription->billing_amount
                    || $subscription->billing_amount <= 0
                ) {
                    throw new LogicException(
                        'La suscripción no tiene un importe de facturación válido.'
                    );
                }

                if (! $subscription->billing_currency) {
                    throw new LogicException(
                        'La suscripción no tiene una moneda de facturación válida.'
                    );
                }

                $existingPayment = Payment::query()
                    ->withoutGlobalScopes()
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();

                if ($existingPayment) {
                    return $existingPayment;
                }

                if ($isRetry && ! $subscription->isPastDue()) {
                    throw new LogicException(
                        'Un reintento requiere una suscripción past_due.'
                    );
                }

                if (! $isRetry && ! $subscription->isActive()) {
                    throw new LogicException(
                        'El cobro inicial requiere una suscripción activa.'
                    );
                }

                $payment = Payment::withoutGlobalScopes()->create([
                    'tenant_id' => $subscription->tenant_id,
                    'subscription_id' => $subscription->id,
                    'billing_cycle' => $subscription->billing_cycle,
                    'amount' => $subscription->billing_amount,
                    'currency' => $subscription->billing_currency,
                    'status' => Payment::STATUS_PENDING,
                    'attempted_at' => $attemptedAt,
                    'provider' => $this->gateway->name(),
                    'idempotency_key' => $idempotencyKey,
                ]);

                $result = $this->gateway->charge($payment);

                if ($result->isSucceeded()) {
                    if ($isRetry) {
                        return app(
                            ProcessRecoveredPayment::class
                        )->execute(
                            $payment,
                            $attemptedAt,
                            $result->providerPaymentId
                        );
                    }

                    $payment->succeed(
                        $attemptedAt,
                        $result->providerPaymentId
                    );

                    app(RenewSubscription::class)->execute(
                        $subscription
                    );

                    return $payment->refresh();
                }

                if ($isRetry) {
                    return app(
                        ProcessFailedPaymentRetry::class
                    )->execute(
                        $payment,
                        $attemptedAt,
                        $result->failureCode,
                        $result->failureMessage,
                        $result->providerPaymentId
                    );
                }

                return app(ProcessFailedPayment::class)->execute(
                    $payment,
                    $attemptedAt,
                    $result->failureCode,
                    $result->failureMessage,
                    $result->providerPaymentId
                );
            }
        );
    }
}
