<?php

namespace App\Services\Production;

use LogicException;

class ProductionRuntimeGuard
{
    public function __construct(
        private readonly ProductionReadinessChecker $checker
    ) {
    }

    public function assertReady(): void
    {
        $failures = $this->checker->failures();

        if ($failures === []) {
            return;
        }

        $keys = implode(
            ', ',
            array_column($failures, 'key')
        );

        throw new LogicException(
            'DocTotal bloqueó el arranque HTTP de producción por configuración insegura: '
            .$keys
            .'. Ejecute php artisan doctotal:check-production-readiness para revisar el entorno.'
        );
    }
}
