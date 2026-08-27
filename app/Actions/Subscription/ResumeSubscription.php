<?php

namespace App\Actions\Subscription;

use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use LogicException;

class ResumeSubscription
{
    public function execute(
        Subscription $subscription
    ): Subscription {
        return DB::transaction(
            function () use ($subscription): Subscription {
                $subscription->refresh();

                if (! $subscription->isActive()) {
                    throw new LogicException(
                        'Sólo una suscripción activa puede conservarse.'
                    );
                }

                if (! $subscription->cancel_at_period_end) {
                    return $subscription;
                }

                $subscription->update([
                    'cancel_at_period_end' => false,
                ]);

                return $subscription->refresh();
            }
        );
    }
}
