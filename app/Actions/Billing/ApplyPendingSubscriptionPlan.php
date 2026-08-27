<?php

namespace App\Actions\Billing;

use App\Models\Subscription;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use LogicException;

class ApplyPendingSubscriptionPlan
{
    public function execute(
        Subscription $subscription,
        CarbonInterface $effectiveAt,
    ): Subscription {
        return DB::transaction(function () use (
            $subscription,
            $effectiveAt,
        ): Subscription {
            $subscription->refresh();

            if (! $subscription->pending_billing_cycle) {
                return $subscription;
            }

            if (! $subscription->isActive()) {
                throw new LogicException(
                    'El cambio programado sólo puede aplicarse a una suscripción activa.'
                );
            }

            if ($subscription->cancel_at_period_end) {
                throw new LogicException(
                    'No puede aplicarse un cambio de plan a una suscripción programada para cancelarse.'
                );
            }

            if (
                ! $subscription->next_billing_at
                || $effectiveAt->lessThan($subscription->next_billing_at)
            ) {
                throw new LogicException(
                    'El cambio de plan todavía no puede aplicarse antes de la siguiente renovación.'
                );
            }

            $billingCycle = $subscription->pending_billing_cycle;
            $plan = config(
                sprintf('billing.plans.%s', $billingCycle)
            );

            if (! is_array($plan)) {
                throw new LogicException(
                    'El plan programado ya no está disponible.'
                );
            }

            $amount = (int) ($plan['amount'] ?? 0);
            $currency = strtoupper(
                (string) ($plan['currency'] ?? '')
            );

            if ($amount <= 0 || $currency === '') {
                throw new LogicException(
                    'El plan programado no tiene una configuración de cobro válida.'
                );
            }

            $subscription->update([
                'billing_cycle' => $billingCycle,
                'billing_amount' => $amount,
                'billing_currency' => $currency,
                'pending_billing_cycle' => null,
            ]);

            return $subscription->refresh();
        });
    }
}
