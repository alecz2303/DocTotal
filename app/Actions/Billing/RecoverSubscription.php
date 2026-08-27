<?php

namespace App\Actions\Billing;

use App\Models\Subscription;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use LogicException;

class RecoverSubscription
{
    public function execute(
        Subscription $subscription,
        CarbonInterface $paidAt,
    ): Subscription {
        return DB::transaction(
            function () use (
                $subscription,
                $paidAt,
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

                $newPeriodStartsAt =
                    $subscription
                    ->current_period_ends_at
                    ->copy();

                $newPeriodEndsAt =
                    $this->calculateNextPeriodEnd(
                        $subscription,
                        $newPeriodStartsAt
                    );

                $subscription->update([
                    'status' =>
                    Subscription::STATUS_ACTIVE,

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
                ]);

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
    ): CarbonInterface {
        if ($subscription->isMonthly()) {
            return $this->calculateMonthlyEnd(
                $subscription,
                $newPeriodStartsAt
            );
        }

        if ($subscription->isYearly()) {
            return $this->calculateYearlyEnd(
                $subscription,
                $newPeriodStartsAt
            );
        }

        throw new LogicException(
            sprintf(
                'El ciclo de facturación "%s" no es válido.',
                $subscription->billing_cycle
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
