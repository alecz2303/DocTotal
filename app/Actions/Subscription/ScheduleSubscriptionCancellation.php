<?php

namespace App\Actions\Subscription;

use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use LogicException;

class ScheduleSubscriptionCancellation
{
    public function execute(
        Subscription $subscription
    ): Subscription {
        return DB::transaction(
            function () use ($subscription): Subscription {
                $subscription->refresh();

                if (! $subscription->isActive()) {
                    throw new LogicException(
                        'Sólo una suscripción activa puede programar su cancelación.'
                    );
                }

                $subscription->scheduleCancellation();

                return $subscription->refresh();
            }
        );
    }
}
