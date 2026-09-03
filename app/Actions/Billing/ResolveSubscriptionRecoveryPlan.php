<?php

namespace App\Actions\Billing;

use App\Models\Subscription;
use LogicException;

class ResolveSubscriptionRecoveryPlan
{
    public function execute(
        Subscription $subscription,
    ): array {
        $subscription->refresh();

        if (! $subscription->isPastDue()) {
            throw new LogicException(
                'El plan de recuperación requiere una suscripción vencida.'
            );
        }

        $billingCycle =
            $subscription->pending_billing_cycle
            ?? $subscription->billing_cycle;

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
                    'El ciclo de recuperación "%s" no es válido.',
                    $billingCycle
                )
            );
        }

        /*
         * Sin cambio pendiente, la deuda conserva exactamente
         * las condiciones contractuales con las que nació.
         *
         * Esto protege importes históricos/legacy y evita que un
         * cambio futuro en config altere una obligación ya creada.
         */
        if (! $subscription->pending_billing_cycle) {
            $amount = (int) $subscription->billing_amount;
            $currency = strtoupper(
                (string) $subscription->billing_currency
            );
        } else {
            /*
             * En past_due, pending_billing_cycle representa el plan
             * elegido para RECUPERAR el servicio. Todavía no mutamos
             * la suscripción: el cambio definitivo ocurre únicamente
             * cuando ese plan es realmente pagado.
             */
            $plan = config(
                sprintf(
                    'billing.plans.%s',
                    $billingCycle
                )
            );

            if (! is_array($plan)) {
                throw new LogicException(
                    'El plan de recuperación no está configurado.'
                );
            }

            $amount = (int) ($plan['amount'] ?? 0);
            $currency = strtoupper(
                (string) ($plan['currency'] ?? '')
            );
        }

        if ($amount <= 0 || $currency === '') {
            throw new LogicException(
                'El plan de recuperación no tiene una configuración de cobro válida.'
            );
        }

        return [
            'billing_cycle' => $billingCycle,
            'amount' => $amount,
            'currency' => $currency,
        ];
    }
}
