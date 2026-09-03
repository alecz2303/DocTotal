<?php

namespace App\Actions\Billing;

use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

class ScheduleSubscriptionPlanChange
{
    public function execute(
        Subscription $subscription,
        string $billingCycle,
    ): Subscription {
        $this->validateBillingCycle($billingCycle);

        return DB::transaction(function () use (
            $subscription,
            $billingCycle,
        ): Subscription {
            $subscription->refresh();

            if (
                ! $subscription->isActive()
                && ! $subscription->isPastDue()
            ) {
                throw new LogicException(
                    sprintf(
                        'La suscripción no puede cambiar de plan desde el estado "%s".',
                        $subscription->status
                    )
                );
            }

            if ($subscription->cancel_at_period_end) {
                throw new LogicException(
                    'La suscripción está programada para cancelarse al finalizar el periodo.'
                );
            }

            if ($billingCycle === $subscription->billing_cycle) {
                $subscription->update([
                    'pending_billing_cycle' => null,
                ]);

                return $subscription->refresh();
            }

            $plan = config(
                sprintf('billing.plans.%s', $billingCycle)
            );

            if (! is_array($plan)) {
                throw new LogicException(
                    'El plan solicitado no está configurado.'
                );
            }

            /*
             * ACTIVE: el ciclo pendiente se aplicará en la próxima
             * renovación, después de terminar el periodo ya pagado.
             *
             * PAST_DUE: el ciclo pendiente representa la elección de
             * recuperación. No cambiamos todavía billing_cycle ni
             * billing_amount: el cambio definitivo sólo ocurre cuando
             * se confirma un pago exitoso de ese plan.
             */
            $subscription->update([
                'pending_billing_cycle' => $billingCycle,
            ]);

            return $subscription->refresh();
        });
    }

    private function validateBillingCycle(
        string $billingCycle,
    ): void {
        if (! in_array(
            $billingCycle,
            [
                Subscription::BILLING_CYCLE_MONTHLY,
                Subscription::BILLING_CYCLE_YEARLY,
            ],
            true
        )) {
            throw new InvalidArgumentException(
                sprintf(
                    'El ciclo de facturación "%s" no es válido.',
                    $billingCycle
                )
            );
        }
    }
}
