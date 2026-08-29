<?php

namespace App\Actions\Billing;

use App\Contracts\StripePaymentIntentApi;
use App\Models\Payment;
use App\Models\Tenant;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use LogicException;

class ConfirmManualSubscriptionRecoveryPayment
{
    public function __construct(
        private readonly StripePaymentIntentApi $paymentIntents
    ) {}

    public function execute(
        Tenant $tenant,
        Payment $payment,
        CarbonInterface $confirmedAt,
    ): Payment {
        return DB::transaction(
            function () use (
                $tenant,
                $payment,
                $confirmedAt,
            ): Payment {
                $payment->refresh();

                if (
                    $payment->tenant_id !==
                    $tenant->id
                ) {
                    throw new LogicException(
                        'El pago no pertenece al tenant.'
                    );
                }

                if ($payment->isSucceeded()) {
                    return $payment;
                }

                if (! $payment->isPending()) {
                    throw new LogicException(
                        'El pago de recuperación ya no está pendiente.'
                    );
                }

                if (
                    $payment->provider !== 'stripe'
                    || ! $payment->provider_payment_id
                ) {
                    throw new LogicException(
                        'El pago de recuperación no tiene un PaymentIntent de Stripe válido.'
                    );
                }

                $subscription =
                    $payment->subscription;

                if (
                    ! $subscription
                    || ! $subscription->isPastDue()
                ) {
                    throw new LogicException(
                        'El pago de recuperación requiere una suscripción vencida.'
                    );
                }

                if (
                    $subscription->tenant_id !==
                    $tenant->id
                ) {
                    throw new LogicException(
                        'La suscripción no pertenece al tenant.'
                    );
                }

                $billingCustomer =
                    $tenant->stripeBillingCustomer();

                if (! $billingCustomer) {
                    throw new LogicException(
                        'El tenant no tiene un cliente Stripe configurado.'
                    );
                }

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

                if (
                    (int) $paymentIntent->amount !==
                    $payment->amount
                ) {
                    throw new LogicException(
                        'El importe del PaymentIntent no coincide con el pago.'
                    );
                }

                if (
                    strtoupper(
                        (string) $paymentIntent->currency
                    ) !==
                    strtoupper(
                        $payment->currency
                    )
                ) {
                    throw new LogicException(
                        'La moneda del PaymentIntent no coincide con el pago.'
                    );
                }

                if (
                    $paymentIntent->customer !==
                    $billingCustomer
                    ->provider_customer_id
                ) {
                    throw new LogicException(
                        'El PaymentIntent no pertenece al cliente Stripe del tenant.'
                    );
                }

                $metadata =
                    $paymentIntent->metadata;

                if (
                    (string) (
                        $metadata
                        ?->doctotal_payment_uuid
                        ?? ''
                    ) !==
                    $payment->uuid
                ) {
                    throw new LogicException(
                        'El PaymentIntent no pertenece al pago de DocTotal.'
                    );
                }

                if (
                    (string) (
                        $metadata
                        ?->doctotal_tenant_id
                        ?? ''
                    ) !==
                    (string) $tenant->id
                ) {
                    throw new LogicException(
                        'El PaymentIntent no pertenece al tenant de DocTotal.'
                    );
                }

                if (
                    (string) (
                        $metadata
                        ?->subscription_id
                        ?? ''
                    ) !==
                    (string) $subscription->id
                ) {
                    throw new LogicException(
                        'El PaymentIntent no pertenece a la suscripción de DocTotal.'
                    );
                }

                if (
                    (string) (
                        $metadata
                        ?->payment_mode
                        ?? ''
                    ) !==
                    'manual_recovery'
                ) {
                    throw new LogicException(
                        'El PaymentIntent no corresponde a una recuperación manual.'
                    );
                }

                if (
                    $paymentIntent->status !==
                    'succeeded'
                ) {
                    throw new LogicException(
                        sprintf(
                            'El PaymentIntent todavía no está pagado. Estado: "%s".',
                            $paymentIntent->status
                        )
                    );
                }

                return app(
                    ProcessRecoveredPayment::class
                )->execute(
                    $payment,
                    $confirmedAt,
                    $paymentIntent->id
                );
            }
        );
    }
}
