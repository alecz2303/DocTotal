<?php

namespace App\Console\Commands;

use App\Actions\Billing\AttemptSubscriptionPayment;
use App\Models\Scopes\TenantScope;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Throwable;

class ProcessSubscriptionPaymentRetries extends Command
{
    protected $signature =
    'billing:process-retries';

    protected $description =
    'Procesa los reintentos de cobro pendientes de suscripciones vencidas.';

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
                Subscription::STATUS_PAST_DUE
            )
            ->whereNotNull(
                'past_due_since'
            )
            ->whereNotNull(
                'next_retry_at'
            )
            ->where(
                'next_retry_at',
                '<=',
                now()
            )
            ->whereNotNull(
                'grace_ends_at'
            )
            ->where(
                'grace_ends_at',
                '>',
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
                            'subscription:%d:recovery:%s:retry:%d',
                            $subscription->id,
                            $subscription
                                ->past_due_since
                                ->format('YmdHis'),
                            $subscription->retry_count + 1
                        );

                        try {
                            $action->execute(
                                $subscription,
                                now(),
                                $idempotencyKey,
                                isRetry: true,
                            );

                            $processed++;
                        } catch (Throwable $exception) {
                            $errors++;

                            report($exception);

                            $this->error(
                                sprintf(
                                    'No pudo procesarse el reintento de la suscripción %d. El proceso continuará con las demás.',
                                    $subscription->id
                                )
                            );
                        }
                    }
                }
            );

        $this->info(
            sprintf(
                'Reintentos de cobro procesados: %d',
                $processed
            )
        );

        if ($errors > 0) {
            $this->warn(
                sprintf(
                    'Reintentos con error inesperado: %d',
                    $errors
                )
            );
        }

        return self::SUCCESS;
    }
}
