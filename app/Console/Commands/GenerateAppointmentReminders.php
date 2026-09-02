<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\Communication;
use App\Models\Tenant;
use App\Services\Communications\AppointmentReminderService;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Throwable;

class GenerateAppointmentReminders extends Command
{
    protected $signature = 'communications:generate-appointment-reminders
        {--channel=whatsapp : Canal de comunicación}
        {--days=7 : Número de días futuros a revisar}';

    protected $description =
    'Genera comunicaciones pendientes para recordatorios de citas futuras.';

    public function handle(
        AppointmentReminderService $reminderService,
        TenantContext $tenantContext,
    ): int {
        $channel = (string) $this->option('channel');
        $days = max(
            1,
            (int) $this->option('days')
        );

        if (! $this->isSupportedChannel($channel)) {
            $this->error(
                sprintf(
                    'Canal no soportado: %s',
                    $channel
                )
            );

            return self::FAILURE;
        }

        $generated = 0;
        $existing = 0;
        $skipped = 0;
        $errors = 0;

        $from = now();
        $until = now()->addDays($days);

        /*
         * Tenant se consulta fuera de cualquier contexto porque
         * representa precisamente la raíz del aislamiento.
         */
        Tenant::query()
            ->orderBy('id')
            ->each(function (Tenant $tenant) use (
                $tenantContext,
                $reminderService,
                $channel,
                $from,
                $until,
                &$generated,
                &$existing,
                &$skipped,
                &$errors,
            ): void {
                $tenantContext->set($tenant);

                try {
                    Appointment::query()
                        ->with('patient')
                        ->whereIn('status', [
                            Appointment::STATUS_SCHEDULED,
                            Appointment::STATUS_CONFIRMED,
                        ])
                        ->where('starts_at', '>', $from)
                        ->where('starts_at', '<=', $until)
                        ->orderBy('starts_at')
                        ->each(function (
                            Appointment $appointment
                        ) use (
                            $reminderService,
                            $channel,
                            &$generated,
                            &$existing,
                            &$skipped,
                            &$errors,
                        ): void {
                            try {
                                $communication =
                                    $reminderService->create(
                                        $appointment,
                                        $channel
                                    );

                                if (! $communication) {
                                    $skipped++;

                                    return;
                                }

                                if ($communication->wasRecentlyCreated) {
                                    $generated++;
                                } else {
                                    $existing++;
                                }
                            } catch (Throwable $exception) {
                                /*
                                 * Una cita defectuosa no debe detener
                                 * recordatorios pertenecientes a otras
                                 * citas o tenants.
                                 */
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
                'Recordatorios: %d generados, %d existentes, %d omitidos, %d errores.',
                $generated,
                $existing,
                $skipped,
                $errors
            )
        );

        return $errors > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    private function isSupportedChannel(
        string $channel
    ): bool {
        return in_array(
            $channel,
            [
                Communication::CHANNEL_EMAIL,
                Communication::CHANNEL_WHATSAPP,
                Communication::CHANNEL_SMS,
            ],
            true
        );
    }
}
