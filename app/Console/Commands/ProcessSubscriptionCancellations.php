<?php

namespace App\Console\Commands;

use App\Actions\Subscription\ProcessSubscriptionExpiration;
use App\Models\Scopes\TenantScope;
use App\Models\Subscription;
use Illuminate\Console\Command;

class ProcessSubscriptionCancellations extends Command
{
    protected $signature =
    'billing:process-cancellations';

    protected $description =
    'Procesa suscripciones programadas para cancelarse al final de su periodo.';

    public function handle(
        ProcessSubscriptionExpiration $action
    ): int {
        $processed = 0;

        Subscription::query()
            ->withoutGlobalScope(
                TenantScope::class
            )
            ->where(
                'status',
                Subscription::STATUS_ACTIVE
            )
            ->where(
                'cancel_at_period_end',
                true
            )
            ->where(
                'current_period_ends_at',
                '<=',
                now()
            )
            ->orderBy('id')
            ->chunkById(
                100,
                function ($subscriptions) use (
                    $action,
                    &$processed,
                ): void {
                    foreach ($subscriptions as $subscription) {
                        $action->execute(
                            $subscription
                        );

                        $processed++;
                    }
                }
            );

        $this->info(
            sprintf(
                'Cancelaciones procesadas: %d',
                $processed
            )
        );

        return self::SUCCESS;
    }
}
