<?php

namespace App\Actions\Billing;

use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use LogicException;

class ProcessExpiredGracePeriod
{
    public function execute(
        Subscription $subscription
    ): Subscription {
        return DB::transaction(
            function () use ($subscription): Subscription {
                $subscription->refresh();

                if (! $subscription->isPastDue()) {
                    return $subscription;
                }

                if (! $subscription->grace_ends_at) {
                    throw new LogicException(
                        'La suscripción no tiene fecha de finalización del periodo de gracia.'
                    );
                }

                if (
                    now()->lessThan(
                        $subscription->grace_ends_at
                    )
                ) {
                    return $subscription;
                }

                $tenant =
                    $subscription->tenant;

                if (! $tenant) {
                    throw new LogicException(
                        'La suscripción no tiene un tenant asociado.'
                    );
                }

                /*
                 * Al terminar el grace period ya no quedan
                 * reintentos automáticos programados.
                 */
                $subscription->update([
                    'next_retry_at' =>
                    null,
                ]);

                /*
                 * La Subscription permanece past_due.
                 *
                 * El problema comercial sigue existiendo.
                 * Lo que cambia es el estado operativo del Tenant.
                 */
                if ($tenant->status !== 'suspended') {
                    $tenant->update([
                        'status' =>
                        'suspended',

                        'suspended_at' =>
                        now(),
                    ]);
                }

                return $subscription->refresh();
            }
        );
    }
}
