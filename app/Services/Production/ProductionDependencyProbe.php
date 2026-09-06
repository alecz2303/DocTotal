<?php

namespace App\Services\Production;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Throwable;

class ProductionDependencyProbe
{
    /**
     * @return array<int, array{key: string, message: string}>
     */
    public function failures(): array
    {
        $failures = [];

        try {
            DB::connection()->getPdo();
        } catch (Throwable) {
            $failures[] = [
                'key' => 'database.connection',
                'message' => 'No fue posible conectar con la base de datos configurada.',
            ];
        }

        $lock = null;

        try {
            $lock = Cache::lock(
                'doctotal:production-readiness:'.Str::uuid(),
                10
            );

            if (! $lock->get()) {
                $failures[] = [
                    'key' => 'cache.lock',
                    'message' => 'El cache configurado no pudo adquirir un lock operativo.',
                ];
            }
        } catch (Throwable) {
            $failures[] = [
                'key' => 'cache.lock',
                'message' => 'No fue posible comprobar locks en el cache configurado.',
            ];
        } finally {
            try {
                $lock?->release();
            } catch (Throwable) {
                // El probe nunca debe exponer detalles internos durante la limpieza.
            }
        }

        try {
            Queue::connection()->size();
        } catch (Throwable) {
            $failures[] = [
                'key' => 'queue.connection',
                'message' => 'No fue posible consultar el backend de colas configurado.',
            ];
        }

        return $failures;
    }
}
