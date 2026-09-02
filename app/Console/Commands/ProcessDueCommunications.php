<?php

namespace App\Console\Commands;

use App\Models\Communication;
use App\Models\Tenant;
use App\Services\Communications\CommunicationProcessor;
use App\Services\Communications\CommunicationTransportManager;
use App\Services\Communications\AppointmentReminderValidator;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Throwable;

class ProcessDueCommunications extends Command
{
    protected $signature = 'communications:process-due';

    protected $description =
    'Procesa comunicaciones pendientes o fallidas cuyo momento de envío o reintento ya llegó.';

    public function handle(
        CommunicationProcessor $processor,
        CommunicationTransportManager $transportManager,
        AppointmentReminderValidator $reminderValidator,
        TenantContext $tenantContext,
    ): int {
        $processed = 0;
        $sent = 0;
        $failed = 0;
        $skipped = 0;
        $errors = 0;

        Tenant::query()
            ->orderBy('id')
            ->each(function (Tenant $tenant) use (
                $tenantContext,
                $processor,
                $transportManager,
                $reminderValidator,
                &$processed,
                &$sent,
                &$failed,
                &$skipped,
                &$errors,
            ): void {
                $tenantContext->set($tenant);

                try {
                    Communication::query()
                        ->where(function ($query): void {
                            $query
                                ->where(function ($query): void {
                                    $query
                                        ->where(
                                            'status',
                                            Communication::STATUS_PENDING
                                        )
                                        ->where(function ($query): void {
                                            $query
                                                ->whereNull('scheduled_for')
                                                ->orWhere(
                                                    'scheduled_for',
                                                    '<=',
                                                    now()
                                                );
                                        });
                                })
                                ->orWhere(function ($query): void {
                                    $query
                                        ->where(
                                            'status',
                                            Communication::STATUS_FAILED
                                        )
                                        ->whereNotNull('next_attempt_at')
                                        ->where(
                                            'next_attempt_at',
                                            '<=',
                                            now()
                                        );
                                });
                        })
                        ->where(
                            'attempt_count',
                            '<',
                            CommunicationProcessor::MAX_ATTEMPTS
                        )
                        ->orderBy('id')
                        ->each(function (
                            Communication $communication
                        ) use (
                            $processor,
                            $transportManager,
                            $reminderValidator,
                            &$processed,
                            &$sent,
                            &$failed,
                            &$skipped,
                            &$errors,
                        ): void {
                            $processed++;

                            try {
                                if (
                                    $communication->type
                                    === Communication::TYPE_APPOINTMENT_REMINDER
                                    && $communication->appointment_id !== null
                                    && ! $reminderValidator->isCurrent(
                                        $communication
                                    )
                                ) {
                                    $communication->markCancelled(
                                        'El recordatorio ya no corresponde al estado o fecha actual de la cita.'
                                    );

                                    $skipped++;

                                    return;
                                }
                                $transport = $transportManager->resolve(
                                    $communication->channel
                                );

                                if (! $transport) {
                                    $skipped++;

                                    return;
                                }

                                $processor->process(
                                    $communication,
                                    $transport
                                );

                                $communication->refresh();

                                if ($communication->isSent()) {
                                    $sent++;

                                    return;
                                }

                                if ($communication->isFailed()) {
                                    $failed++;

                                    return;
                                }

                                $skipped++;
                            } catch (Throwable $exception) {
                                $errors++;

                                report($exception);
                            }
                        });
                } finally {
                    $tenantContext->clear();
                }
            });

        $this->info(
            sprintf(
                'Comunicaciones: %d procesadas, %d enviadas, %d fallidas, %d omitidas, %d errores.',
                $processed,
                $sent,
                $failed,
                $skipped,
                $errors
            )
        );

        return $errors > 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}
