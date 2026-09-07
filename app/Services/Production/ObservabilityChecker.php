<?php

namespace App\Services\Production;

class ObservabilityChecker
{
    /**
     * @return array<int, array{key: string, message: string}>
     */
    public function failures(): array
    {
        $failures = [];

        $this->require(
            $failures,
            config('observability.enabled') === true,
            'observability.enabled',
            'La observabilidad de producción debe estar habilitada.'
        );

        $channel = (string) config('observability.channel');

        $this->require(
            $failures,
            $channel !== '' && is_array(config("logging.channels.{$channel}")),
            'observability.channel',
            'DOCTOTAL_OBSERVABILITY_CHANNEL debe apuntar a un canal de logging válido.'
        );

        $this->require(
            $failures,
            config('observability.alerting_enabled') === true,
            'observability.alerting',
            'La estrategia de observabilidad debe declarar alertamiento operativo habilitado.'
        );

        $runbook = (string) config('observability.runbook');

        $this->require(
            $failures,
            $runbook !== '' && is_file(base_path($runbook)),
            'observability.runbook',
            'Debe existir un runbook versionado de respuesta a incidentes.'
        );

        return $failures;
    }

    public function isReady(): bool
    {
        return $this->failures() === [];
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
