<?php

namespace App\Console\Commands;

use App\Services\Production\ProductionDependencyProbe;
use App\Services\Production\ProductionReadinessChecker;
use Illuminate\Console\Command;

class CheckProductionReadiness extends Command
{
    protected $signature =
    'doctotal:check-production-readiness
        {--probe : Comprueba conectividad de base de datos, cache locks y backend de colas}';

    protected $description =
    'Valida configuración crítica antes de operar DocTotal en producción.';

    public function handle(
        ProductionReadinessChecker $checker,
        ProductionDependencyProbe $probe
    ): int {
        $failures = $checker->failures();

        if ($failures !== []) {
            return $this->renderFailures($failures);
        }

        if ($this->option('probe')) {
            $probeFailures = $probe->failures();

            if ($probeFailures !== []) {
                return $this->renderFailures($probeFailures);
            }

            $this->info(
                'Configuración crítica y dependencias operativas de producción: OK.'
            );

            return self::SUCCESS;
        }

        $this->info(
            'Configuración crítica de producción: OK.'
        );

        return self::SUCCESS;
    }

    /**
     * @param array<int, array{key: string, message: string}> $failures
     */
    private function renderFailures(array $failures): int
    {
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
