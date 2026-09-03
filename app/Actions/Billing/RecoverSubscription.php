<?php

namespace App\Actions\Billing;

use App\Models\Payment;
use App\Models\Subscription;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use LogicException;

class RecoverSubscription
{
    public function execute(
        Subscription $subscription,
        CarbonInterface $paidAt,
        ?Payment $payment = null,
    ): Subscription {
        return DB::transaction(
            function () use (
                $subscription,
                $paidAt,
                $payment,
            ): Subscription {
                $subscription->refresh();

                if (! $subscription->isPastDue()) {
                    throw new LogicException(
                        sprintf(
                            'La suscripción no puede recuperarse desde el estado "%s".',
                            $subscription->status
                        )
                    );
                }

                $billingCycle =
                    $subscription->billing_cycle;

                $billingAmount =
                    $subscription->billing_amount !== null
                        ? (int) $subscription->billing_amount
                        : null;

                $billingCurrency =
                    trim(
                        strtoupper(
                            (string) $subscription->billing_currency
                        )
                    );

                $hasPaymentContractSnapshot = false;

                /*
                 * Cuando existe un Payment exitoso, ese pago es la
                 * verdad económica de la recuperación. Esto permite
                 * que una deuda anual no pagada se recupere como
                 * mensual sin mutar la suscripción antes de cobrar.
                 */
                if ($payment) {
                    $payment->refresh();

                    if (
                        (int) $payment->subscription_id !==
                        (int) $subscription->id
                    ) {
                        throw new LogicException(
                            'El pago de recuperación no pertenece a la suscripción.'
                        );
                    }

                    if (! $payment->isSucceeded()) {
                        throw new LogicException(
                            'La suscripción sólo puede recuperarse con un pago exitoso.'
                        );
                    }

                    /*
                     * Compatibilidad con pagos históricos:
                     *
                     * Antes de DT-23, los pagos de recuperación no
                     * persistían billing_cycle/gross_amount como
                     * snapshot contractual. En esos casos debemos
                     * conservar las condiciones de la suscripción.
                     *
                     * Los nuevos checkouts DT-23 sí persisten
                     * billing_cycle, por lo que el pago se convierte
                     * en la verdad económica de la recuperación y
                     * puede aplicar un cambio de plan (ej. yearly
                     * past_due -> monthly).
                     */
                    $paymentBillingCycle =
                        trim(
                            (string) $payment->billing_cycle
                        );

                    if ($paymentBillingCycle !== '') {
                        $hasPaymentContractSnapshot = true;

                        $billingCycle =
                            $paymentBillingCycle;

                        $billingAmount =
                            $payment->contractualAmount();

                        $billingCurrency =
                            trim(
                                strtoupper(
                                    (string) $payment->currency
                                )
                            );
                    }
                }

                if (! in_array(
                    $billingCycle,
                    [
                        Subscription::BILLING_CYCLE_MONTHLY,
                        Subscription::BILLING_CYCLE_YEARLY,
                    ],
                    true
                )) {
                    throw new LogicException(
                        sprintf(
                            'El ciclo de facturación "%s" no es válido.',
                            $billingCycle
                        )
                    );
                }

                if (
                    $hasPaymentContractSnapshot
                    && (
                        $billingAmount === null
                        || $billingAmount <= 0
                        || $billingCurrency === ''
                    )
                ) {
                    throw new LogicException(
                        'El pago de recuperación no tiene condiciones contractuales válidas.'
                    );
                }

                $newPeriodStartsAt =
                    $subscription
                    ->current_period_ends_at
                    ->copy();

                $newPeriodEndsAt =
                    $this->calculateNextPeriodEnd(
                        $subscription,
                        $newPeriodStartsAt,
                        $billingCycle
                    );

                $subscriptionUpdates = [
                    'status' =>
                    Subscription::STATUS_ACTIVE,

                    'billing_cycle' =>
                    $billingCycle,

                    'pending_billing_cycle' =>
                    null,

                    'current_period_starts_at' =>
                    $newPeriodStartsAt,

                    'current_period_ends_at' =>
                    $newPeriodEndsAt,

                    'next_billing_at' =>
                    $newPeriodEndsAt,

                    'past_due_since' =>
                    null,

                    'grace_ends_at' =>
                    null,

                    'next_retry_at' =>
                    null,

                    'retry_count' =>
                    0,
                ];

                /*
                 * Sólo sobrescribimos importe y moneda cuando el
                 * Payment contiene el snapshot contractual nuevo.
                 *
                 * Los pagos históricos pueden no tener
                 * billing_cycle/gross_amount y sus suscripciones
                 * también pueden carecer de billing_amount o
                 * billing_currency. En esos casos la recuperación
                 * histórica debe seguir funcionando sin inventar
                 * condiciones contractuales.
                 */
                if ($hasPaymentContractSnapshot) {
                    $subscriptionUpdates['billing_amount'] =
                        $billingAmount;

                    $subscriptionUpdates['billing_currency'] =
                        $billingCurrency;
                }

                $subscription->update(
                    $subscriptionUpdates
                );

                $tenant =
                    $subscription->tenant;

                if (! $tenant) {
                    throw new LogicException(
                        'La suscripción no tiene un tenant asociado.'
                    );
                }

                $tenant->update([
                    'status' => 'active',
                    'suspended_at' => null,
                ]);

                return $subscription->refresh();
            }
        );
    }

    private function calculateNextPeriodEnd(
        Subscription $subscription,
        CarbonInterface $newPeriodStartsAt,
        string $billingCycle,
    ): CarbonInterface {
        if (
            $billingCycle ===
            Subscription::BILLING_CYCLE_MONTHLY
        ) {
            return $this->calculateMonthlyEnd(
                $subscription,
                $newPeriodStartsAt
            );
        }

        if (
            $billingCycle ===
            Subscription::BILLING_CYCLE_YEARLY
        ) {
            return $this->calculateYearlyEnd(
                $subscription,
                $newPeriodStartsAt
            );
        }

        throw new LogicException(
            sprintf(
                'El ciclo de facturación "%s" no es válido.',
                $billingCycle
            )
        );
    }

    private function calculateMonthlyEnd(
        Subscription $subscription,
        CarbonInterface $newPeriodStartsAt,
    ): CarbonInterface {
        $targetMonth =
            $newPeriodStartsAt
            ->copy()
            ->startOfMonth()
            ->addMonth();

        $anchor =
            $subscription->starts_at;

        $targetDay = min(
            $anchor->day,
            $targetMonth->daysInMonth
        );

        return $targetMonth
            ->copy()
            ->setDate(
                $targetMonth->year,
                $targetMonth->month,
                $targetDay
            )
            ->setTime(
                $anchor->hour,
                $anchor->minute,
                $anchor->second
            );
    }

    private function calculateYearlyEnd(
        Subscription $subscription,
        CarbonInterface $newPeriodStartsAt,
    ): CarbonInterface {
        $anchor =
            $subscription->starts_at;

        $targetYear =
            $newPeriodStartsAt->year + 1;

        $targetMonth =
            $newPeriodStartsAt
            ->copy()
            ->setDate(
                $targetYear,
                $anchor->month,
                1
            );

        $targetDay = min(
            $anchor->day,
            $targetMonth->daysInMonth
        );

        return $targetMonth
            ->setDate(
                $targetYear,
                $anchor->month,
                $targetDay
            )
            ->setTime(
                $anchor->hour,
                $anchor->minute,
                $anchor->second
            );
    }
}
