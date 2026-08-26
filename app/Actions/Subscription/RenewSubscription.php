<?php

namespace App\Actions\Subscription;

use App\Models\Subscription;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use LogicException;

class RenewSubscription
{
    public function execute(
        Subscription $subscription,
    ): Subscription {
        return DB::transaction(
            function () use ($subscription): Subscription {
                if (! $subscription->isActive()) {
                    throw new LogicException(
                        sprintf(
                            'La suscripción no puede renovarse desde el estado "%s".',
                            $subscription->status
                        )
                    );
                }

                if ($subscription->cancel_at_period_end) {
                    throw new LogicException(
                        'La suscripción está programada para cancelarse al finalizar el periodo.'
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
                    'current_period_starts_at' =>
                    $newPeriodStartsAt,

                    'current_period_ends_at' =>
                    $newPeriodEndsAt,

                    'next_billing_at' =>
                    $newPeriodEndsAt,
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
        /*
         * Obtenemos primero el mes calendario siguiente.
         *
         * Usamos día 1 temporalmente para evitar que un
         * 28/30/31 altere el cálculo del mes destino.
         */
        $targetMonth = $newPeriodStartsAt
            ->copy()
            ->startOfMonth()
            ->addMonth();

        /*
         * El día original de facturación siempre viene de
         * starts_at. No usamos current_period_starts_at,
         * porque podría haber sido ajustado previamente
         * por un febrero corto.
         */
        $anchorDay =
            $subscription->starts_at->day;

        $targetDay = min(
            $anchorDay,
            $targetMonth->daysInMonth
        );

        return $targetMonth->copy()->setDate(
            $targetMonth->year,
            $targetMonth->month,
            $targetDay
        )->setTime(
            $subscription->starts_at->hour,
            $subscription->starts_at->minute,
            $subscription->starts_at->second
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

        /*
         * Creamos primero el día 1 del mes original para
         * poder calcular con seguridad febrero/bisiestos.
         */
        $targetMonth = $newPeriodStartsAt
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

        return $targetMonth->setDate(
            $targetYear,
            $anchor->month,
            $targetDay
        )->setTime(
            $anchor->hour,
            $anchor->minute,
            $anchor->second
        );
    }
}
