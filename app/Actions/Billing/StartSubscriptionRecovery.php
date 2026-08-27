<?php

namespace App\Actions\Billing;

use App\Models\Subscription;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use LogicException;

class StartSubscriptionRecovery
{
    public function execute(
        Subscription $subscription,
        CarbonInterface $failedAt,
    ): Subscription {
        return DB::transaction(
            function () use (
                $subscription,
                $failedAt,
            ): Subscription {
                if (! $subscription->isActive()) {
                    throw new LogicException(
                        sprintf(
                            'La recuperación no puede iniciarse desde el estado "%s".',
                            $subscription->status
                        )
                    );
                }

                $gracePeriodDays = (int) config(
                    'billing.grace_period_days'
                );

                $retrySchedule = config(
                    'billing.retry_schedule_hours',
                    []
                );

                if (
                    $gracePeriodDays <= 0
                    || empty($retrySchedule)
                ) {
                    throw new LogicException(
                        'La configuración de recuperación de pagos no es válida.'
                    );
                }

                $firstRetryHours = (int) $retrySchedule[0];

                if ($firstRetryHours <= 0) {
                    throw new LogicException(
                        'La configuración del primer reintento no es válida.'
                    );
                }

                $subscription->update([
                    'status' =>
                    Subscription::STATUS_PAST_DUE,

                    'past_due_since' =>
                    $failedAt,

                    'grace_ends_at' =>
                    $failedAt
                        ->copy()
                        ->addDays(
                            $gracePeriodDays
                        ),

                    'next_retry_at' =>
                    $failedAt
                        ->copy()
                        ->addHours(
                            $firstRetryHours
                        ),

                    'retry_count' =>
                    0,
                ]);

                return $subscription->refresh();
            }
        );
    }
}
