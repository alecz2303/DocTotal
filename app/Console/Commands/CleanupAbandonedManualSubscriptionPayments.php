<?php

namespace App\Console\Commands;

use App\Actions\Billing\CleanupAbandonedManualSubscriptionPayments as CleanupAction;
use Illuminate\Console\Command;
use LogicException;

class CleanupAbandonedManualSubscriptionPayments extends Command
{
    protected $signature =
    'billing:cleanup-abandoned-checkouts';

    protected $description =
    'Cancela o reconcilia checkouts manuales iniciales que permanecen pendientes después de su tiempo de expiración.';

    public function handle(
        CleanupAction $action
    ): int {
        $expirationHours =
            (int) config(
                'billing.manual_checkout_expiration_hours',
                24
            );

        if ($expirationHours <= 0) {
            throw new LogicException(
                'El tiempo de expiración de checkouts manuales debe ser mayor a cero.'
            );
        }

        $now =
            now();

        $cutoff =
            $now->copy()
            ->subHours(
                $expirationHours
            );

        $result =
            $action->execute(
                $cutoff,
                $now
            );

        $this->info(
            sprintf(
                'Checkouts procesados: %d',
                $result['processed']
            )
        );

        $this->info(
            sprintf(
                'Checkouts cancelados: %d',
                $result['canceled']
            )
        );

        $this->info(
            sprintf(
                'Checkouts reconciliados: %d',
                $result['reconciled']
            )
        );

        if ($result['errors'] > 0) {
            $this->warn(
                sprintf(
                    'Checkouts con error: %d',
                    $result['errors']
                )
            );
        }

        return self::SUCCESS;
    }
}
