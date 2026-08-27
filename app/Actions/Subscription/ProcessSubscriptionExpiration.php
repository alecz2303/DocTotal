<?php

namespace App\Actions\Subscription;

use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

class ProcessSubscriptionExpiration
{
    public function execute(
        Subscription $subscription
    ): Subscription {
        return DB::transaction(
            function () use ($subscription): Subscription {
                $subscription->refresh();

                if (! $subscription->isActive()) {
                    return $subscription;
                }

                if (! $subscription->cancel_at_period_end) {
                    return $subscription;
                }

                if (
                    now()->lessThan(
                        $subscription->current_period_ends_at
                    )
                ) {
                    return $subscription;
                }

                $subscription->update([
                    'status' =>
                    Subscription::STATUS_CANCELLED,

                    'cancel_at_period_end' =>
                    false,

                    'cancelled_at' =>
                    now(),

                    'next_billing_at' =>
                    null,

                    'pending_billing_cycle' =>
                    null,
                ]);

                return $subscription->refresh();
            }
        );
    }
}
