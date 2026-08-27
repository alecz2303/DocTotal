<?php

namespace App\Actions\Billing;

use App\Actions\Subscription\ActivateSubscription;
use App\Contracts\StripePaymentIntentApi;
use App\Models\Payment;
use App\Models\Tenant;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use LogicException;

class ConfirmManualSubscriptionPayment
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
                        'El pago manual ya no está pendiente.'
                    );
                }

                if (
                    $payment->provider !==
                    'stripe'
                ) {
                    throw new LogicException(
                        'El pago manual no pertenece a Stripe.'
                    );
                }

                if (! $payment->provider_payment_id) {
                    throw new LogicException(
                        'El pago no tiene un PaymentIntent asociado.'
                    );
                }

                if (! $payment->billing_cycle) {
                    throw new LogicException(
                        'El pago no tiene un ciclo de facturación.'
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
                    $this->paymentIntents
                        ->retrieve(
                            $payment
                                ->provider_payment_id
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
                            ?->payment_mode
                        ?? ''
                    ) !==
                    'manual'
                ) {
                    throw new LogicException(
                        'El PaymentIntent no corresponde a un pago manual.'
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

                $subscription =
                    app(
                        ActivateSubscription::class
                    )->execute(
                        $tenant,
                        $payment->billing_cycle,
                        $confirmedAt
                    );

                $subscription->update([
                    'billing_amount' =>
                        $payment->amount,

                    'billing_currency' =>
                        $payment->currency,
                ]);

                $payment->succeed(
                    $confirmedAt,
                    $paymentIntent->id
                );

                $payment->update([
                    'subscription_id' =>
                        $subscription->id,
                ]);

                return $payment->refresh();
            }
        );
    }
}
