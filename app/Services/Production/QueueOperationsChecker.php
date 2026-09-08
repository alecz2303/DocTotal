<?php

namespace App\Services\Production;

class QueueOperationsChecker
{
    /**
     * @return array<int, array{key: string, message: string}>
     */
    public function failures(): array
    {
        $failures = [];
        $mode = (string) config('queue_operations.mode');

        $this->require(
            $failures,
            in_array($mode, ['scheduler_only', 'workers'], true),
            'queue.operations.mode',
            'DOCTOTAL_QUEUE_MODE debe ser scheduler_only o workers.'
        );

        $this->require(
            $failures,
            config('queue_operations.failed_jobs.monitoring_enabled') === true,
            'queue.failed.monitoring',
            'El monitoreo operativo de failed jobs debe estar habilitado.'
        );

        $threshold = (int) config(
            'queue_operations.failed_jobs.alert_threshold'
        );

        $this->require(
            $failures,
            $threshold >= 1,
            'queue.failed.alert_threshold',
            'El umbral de alerta de failed jobs debe ser al menos 1.'
        );

        $runbook = (string) config('queue_operations.runbook');

        $this->require(
            $failures,
            $runbook !== '' && is_file(base_path($runbook)),
            'queue.operations.runbook',
            'Debe existir un runbook versionado de colas, workers y failed jobs.'
        );

        if ($mode === 'scheduler_only') {
            $this->require(
                $failures,
                config('queue_operations.worker.enabled') === false,
                'queue.worker.enabled',
                'No debe declararse un worker activo mientras el modo sea scheduler_only.'
            );
        }

        if ($mode === 'workers') {
            $this->validateWorkerMode($failures);
        }

        return $failures;
    }

    public function isReady(): bool
    {
        return $this->failures() === [];
    }

    /**
     * @param array<int, array{key: string, message: string}> $failures
     */
    private function validateWorkerMode(array &$failures): void
    {
        $this->require(
            $failures,
            config('queue_operations.worker.enabled') === true,
            'queue.worker.enabled',
            'El modo workers requiere declarar el worker habilitado.'
        );

        $this->require(
            $failures,
            filled(config('queue_operations.worker.queue')),
            'queue.worker.queue',
            'El worker debe declarar al menos una cola explícita.'
        );

        $tries = (int) config('queue_operations.worker.tries');
        $timeout = (int) config('queue_operations.worker.timeout');

        $this->require(
            $failures,
            $tries >= 1 && $tries <= 10,
            'queue.worker.tries',
            'Los intentos del worker deben estar entre 1 y 10.'
        );

        $this->require(
            $failures,
            $timeout >= 10 && $timeout <= 3600,
            'queue.worker.timeout',
            'El timeout del worker debe estar entre 10 y 3600 segundos.'
        );

        $connection = (string) config('queue.default');
        $retryAfter = (int) config("queue.connections.{$connection}.retry_after");

        if ($retryAfter > 0) {
            $this->require(
                $failures,
                $retryAfter > $timeout,
                'queue.worker.retry_after',
                'QUEUE retry_after debe ser mayor que el timeout del worker para reducir ejecuciones duplicadas.'
            );
        }
    }

    /**
     * @param array<int, array{key: string, message: string}> $failures
     */
    private function require(
        array &$failures,
        bool $condition,
        string $key,
        string $message
    ): void {
        if ($condition) {
            return;
        }

        $failures[] = [
            'key' => $key,
            'message' => $message,
        ];
    }
}
