<?php

namespace App\Console\Commands;

use App\Actions\Billing\AttemptSubscriptionPayment;
use App\Models\Scopes\TenantScope;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Throwable;

class ProcessSubscriptionRenewals extends Command
{
    protected $signature =
    'billing:process-renewals';

    protected $description =
    'Procesa los cobros recurrentes de suscripciones que alcanzaron su fecha de renovación.';

    public function handle(
        AttemptSubscriptionPayment $action
    ): int {
        $processed = 0;
        $errors = 0;

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
                false
            )
            ->whereNotNull(
                'next_billing_at'
            )
            ->where(
                'next_billing_at',
                '<=',
                now()
            )
            ->orderBy('id')
            ->chunkById(
                100,
                function ($subscriptions) use (
                    $action,
                    &$processed,
                    &$errors,
                ): void {
                    foreach ($subscriptions as $subscription) {
                        $idempotencyKey = sprintf(
                            'subscription:%d:renewal:%s',
                            $subscription->id,
                            $subscription
                                ->next_billing_at
                                ->format('YmdHis')
                        );

                        try {
                            $action->execute(
                                $subscription,
                                now(),
                                $idempotencyKey,
                            );

                            $processed++;
                        } catch (Throwable $exception) {
                            $errors++;

                            report($exception);

                            $this->error(
                                sprintf(
                                    'No pudo procesarse la renovación de la suscripción %d. El proceso continuará con las demás.',
                                    $subscription->id
                                )
                            );
                        }
                    }
                }
            );

        $this->info(
            sprintf(
                'Renovaciones procesadas: %d',
                $processed
            )
        );

        if ($errors > 0) {
            $this->warn(
                sprintf(
                    'Renovaciones con error inesperado: %d',
                    $errors
                )
            );
        }

        return self::SUCCESS;
    }
}
