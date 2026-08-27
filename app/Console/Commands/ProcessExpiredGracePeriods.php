<?php

namespace App\Console\Commands;

use App\Actions\Billing\ProcessExpiredGracePeriod;
use App\Models\Scopes\TenantScope;
use App\Models\Subscription;
use Illuminate\Console\Command;

class ProcessExpiredGracePeriods extends Command
{
    protected $signature =
    'billing:process-expired-grace-periods';

    protected $description =
    'Suspende tenants cuyo periodo de gracia de pago ha expirado.';

    public function handle(
        ProcessExpiredGracePeriod $action
    ): int {
        $processed = 0;

        Subscription::query()
            ->withoutGlobalScope(
                TenantScope::class
            )
            ->where(
                'status',
                Subscription::STATUS_PAST_DUE
            )
            ->whereNotNull(
                'grace_ends_at'
            )
            ->where(
                'grace_ends_at',
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
                'Periodos de gracia procesados: %d',
                $processed
            )
        );

        return self::SUCCESS;
    }
}
