<?php

namespace App\Console\Commands;

use App\Services\Production\ProductionReadinessChecker;
use Illuminate\Console\Command;

class CheckProductionReadiness extends Command
{
    protected $signature =
    'doctotal:check-production-readiness';

    protected $description =
    'Valida configuración crítica antes de operar DocTotal en producción.';

    public function handle(
        ProductionReadinessChecker $checker
    ): int {
        $failures = $checker->failures();

        if ($failures === []) {
            $this->info(
                'Configuración crítica de producción: OK.'
            );

            return self::SUCCESS;
        }

        $this->error(
            'DocTotal no está listo para producción.'
        );

        foreach ($failures as $failure) {
            $this->line(
                sprintf(
                    '- [%s] %s',
                    $failure['key'],
                    $failure['message']
                )
            );
        }

        return self::FAILURE;
    }
}
