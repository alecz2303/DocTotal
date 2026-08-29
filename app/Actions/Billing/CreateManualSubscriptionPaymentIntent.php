<?php

namespace App\Actions\Billing;

use App\Contracts\StripePaymentIntentApi;
use App\Data\ManualSubscriptionPaymentIntent;
use App\Models\Payment;
use App\Models\Tenant;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

class CreateManualSubscriptionPaymentIntent
{
    public function __construct(
        private readonly EnsureStripeBillingCustomer $ensureCustomer,
        private readonly StripePaymentIntentApi $paymentIntents,
        private readonly ReservePromotionalCredits $reservePromotionalCredits,
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

        $amountBreakdown =
            app(
                CalculatePaymentAmount::class
            )->execute(
                $tenant,
                $amount
            );

        $billingCustomer =
            $this->ensureCustomer->execute(
                $tenant
            );

        /*
         * Serializamos la creación de checkouts manuales
         * por Tenant.
         *
         * Esto evita que dos solicitudes simultáneas
         * creen dos Payment pendientes.
         */
        $payment =
            DB::transaction(
                function () use (
                    $tenant,
                    $billingCycle,
                    $attemptedAt,
                    $idempotencyKey,
                    $amountBreakdown,
                    $currency,
                ): Payment {
                    Tenant::query()
                        ->whereKey($tenant->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    /*
                     * Primero respetamos la idempotencia
                     * explícita solicitada por el caller.
                     */
                    $payment =
                        Payment::withoutGlobalScopes()
                        ->where(
                            'idempotency_key',
                            $idempotencyKey
                        )
                        ->first();

                    if ($payment) {
                        if (
                            (int) $payment->tenant_id !==
                            (int) $tenant->id
                        ) {
                            throw new LogicException(
                                'La llave de idempotencia pertenece a otro tenant.'
                            );
                        }

                        if (! $payment->isPending()) {
                            throw new LogicException(
                                'El pago manual ya fue procesado.'
                            );
                        }

                        if (
                            $payment->billing_cycle !==
                            $billingCycle
                        ) {
                            throw new LogicException(
                                'La llave de idempotencia pertenece a otro plan.'
                            );
                        }

                        return $payment;
                    }

                    /*
                     * Aunque llegue una llave nueva, un Tenant
                     * solamente puede tener un checkout manual
                     * pendiente a la vez.
                     */
                    $pendingPayment =
                        Payment::withoutGlobalScopes()
                        ->where(
                            'tenant_id',
                            $tenant->id
                        )
                        ->whereNull(
                            'subscription_id'
                        )
                        ->where(
                            'provider',
                            'stripe'
                        )
                        ->where(
                            'status',
                            Payment::STATUS_PENDING
                        )
                        ->latest('id')
                        ->first();

                    if ($pendingPayment) {
                        /*
                         * Si es el mismo plan reutilizamos el
                         * checkout existente.
                         *
                         * No reservamos nuevamente créditos y
                         * tampoco generamos otro Payment.
                         */
                        if (
                            $pendingPayment->billing_cycle ===
                            $billingCycle
                        ) {
                            return $pendingPayment;
                        }

                        /*
                         * Si es otro plan exigimos que el checkout
                         * anterior sea abandonado explícitamente.
                         *
                         * La pantalla de Facturación ya realiza
                         * ese flujo mediante "Cambiar plan".
                         */
                        throw new LogicException(
                            'Ya existe un checkout pendiente para otro plan. Cancélalo antes de cambiar de plan.'
                        );
                    }

                    $payment =
                        Payment::withoutGlobalScopes()
                        ->create([
                            'tenant_id' =>
                            $tenant->id,

                            'subscription_id' =>
                            null,

                            'billing_cycle' =>
                            $billingCycle,

                            'gross_amount' =>
                            $amountBreakdown['gross_amount'],

                            'referral_discount_amount' =>
                            $amountBreakdown['referral_discount_amount'],

                            'promotional_credit_amount' =>
                            $amountBreakdown['promotional_credit_amount'],

                            'amount' =>
                            $amountBreakdown['amount'],

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

                    /*
                     * El crédito debe quedar reservado antes de
                     * crear el PaymentIntent para que Stripe
                     * reciba el importe neto definitivo.
                     */
                    return
                        $this->reservePromotionalCredits
                        ->execute(
                            $payment
                        );
                }
            );

        /*
         * A partir de aquí siempre usamos los datos del Payment
         * definitivo.
         *
         * Si reutilizamos un checkout previo, también reutilizamos
         * su llave de idempotencia.
         */
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
                $payment->billing_cycle,

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
