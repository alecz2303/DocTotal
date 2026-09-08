<?php

namespace App\Console\Commands;

use App\Services\Production\QueueOperationsChecker;
use App\Services\Production\QueueOperationsMonitor;
use Illuminate\Console\Command;
use Throwable;

class CheckQueueOperations extends Command
{
    protected $signature = 'doctotal:check-queue-operations';

    protected $description =
        'Valida la topología declarada de scheduler/workers y el estado operativo de las colas.';

    public function handle(
        QueueOperationsChecker $checker,
        QueueOperationsMonitor $monitor
    ): int {
        $failures = $checker->failures();

        if ($failures !== []) {
            $this->error('La estrategia de colas/workers está incompleta o es insegura.');

            foreach ($failures as $failure) {
                $this->line(sprintf(
                    '- [%s] %s',
                    $failure['key'],
                    $failure['message']
                ));
            }

            return self::FAILURE;
        }

        try {
            $snapshot = $monitor->snapshot();
        } catch (Throwable) {
            $this->error('No fue posible consultar el estado operativo de las colas.');

            return self::FAILURE;
        }

        $this->info('Topología de colas/workers: OK.');
        $this->line('Modo: '.$snapshot['mode']);
        $this->line('Jobs pendientes: '.$snapshot['pending_jobs']);
        $this->line('Jobs fallidos: '.$snapshot['failed_jobs']);

        if ($monitor->hasFailedJobAlert($snapshot)) {
            $this->error(
                'Se alcanzó el umbral operativo de failed jobs; requiere revisión antes de reintentar.'
            );

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
