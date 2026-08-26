<?php

namespace App\Actions\Subscription;

use App\Models\Scopes\TenantScope;
use App\Models\Subscription;
use App\Models\Tenant;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

class ActivateSubscription
{
    public function execute(
        Tenant $tenant,
        string $billingCycle,
        CarbonInterface $paidAt,
    ): Subscription {
        $this->validateBillingCycle(
            $billingCycle
        );

        return DB::transaction(function () use (
            $tenant,
            $billingCycle,
            $paidAt,
        ): Subscription {
            /*
             * Aquí NO usamos hasActiveSubscription().
             *
             * hasActiveSubscription() responde si existe una
             * suscripción vigente en este instante.
             *
             * Para activar una nueva necesitamos una regla
             * distinta: el tenant no puede tener ninguna
             * Subscription con status = active, aunque su
             * periodo empiece en el futuro.
             *
             * Tampoco dependemos de TenantContext porque esta
             * Action podrá ejecutarse posteriormente desde
             * jobs/webhooks sin un request autenticado.
             */
            $alreadyHasActiveSubscription =
                Subscription::query()
                ->withoutGlobalScope(
                    TenantScope::class
                )
                ->where(
                    'tenant_id',
                    $tenant->id
                )
                ->whereIn(
                    'status',
                    [
                        Subscription::STATUS_ACTIVE,
                        Subscription::STATUS_PAST_DUE,
                    ]
                )
                ->exists();

            if ($alreadyHasActiveSubscription) {
                throw new LogicException(
                    'El tenant ya tiene una suscripción activa.'
                );
            }

            $periodEndsAt =
                $this->calculatePeriodEnd(
                    $paidAt,
                    $billingCycle
                );

            /*
             * Creamos la suscripción directamente con
             * tenant_id explícito.
             *
             * Así esta Action tampoco depende de que exista
             * TenantContext al momento de ejecutarse.
             */
            $subscription =
                Subscription::withoutGlobalScope(
                    TenantScope::class
                )->create([
                    'tenant_id' =>
                    $tenant->id,

                    'billing_cycle' =>
                    $billingCycle,

                    'status' =>
                    Subscription::STATUS_ACTIVE,

                    'starts_at' =>
                    $paidAt,

                    'current_period_starts_at' =>
                    $paidAt,

                    'current_period_ends_at' =>
                    $periodEndsAt,

                    'next_billing_at' =>
                    $periodEndsAt,

                    'cancel_at_period_end' =>
                    false,

                    'cancelled_at' =>
                    null,
                ]);

            $tenant->update([
                'status' => 'active',
                'suspended_at' => null,
            ]);

            return $subscription;
        });
    }

    private function calculatePeriodEnd(
        CarbonInterface $paidAt,
        string $billingCycle,
    ): CarbonInterface {
        return match ($billingCycle) {
            Subscription::BILLING_CYCLE_MONTHLY =>
            $paidAt
                ->copy()
                ->addMonthNoOverflow(),

            Subscription::BILLING_CYCLE_YEARLY =>
            $paidAt
                ->copy()
                ->addYearNoOverflow(),

            default =>
            throw new InvalidArgumentException(
                sprintf(
                    'El ciclo de facturación "%s" no es válido.',
                    $billingCycle
                )
            ),
        };
    }

    private function validateBillingCycle(
        string $billingCycle
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
