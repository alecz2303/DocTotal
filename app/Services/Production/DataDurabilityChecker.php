<?php

namespace App\Services\Production;

class DataDurabilityChecker
{
    /**
     * @return array<int, array{key: string, message: string}>
     */
    public function failures(): array
    {
        $failures = [];

        $backup = (array) config('data_durability.backup', []);
        $restore = (array) config('data_durability.restore', []);
        $retention = (array) config('data_durability.retention', []);

        $this->require(
            $failures,
            ($backup['enabled'] ?? false) === true,
            'durability.backup.enabled',
            'DOCTOTAL_BACKUP_ENABLED debe estar habilitado en producción.'
        );

        $this->require(
            $failures,
            ($backup['database'] ?? false) === true,
            'durability.backup.database',
            'La estrategia de backup debe incluir la base de datos.'
        );

        $this->require(
            $failures,
            ($backup['private_files'] ?? false) === true,
            'durability.backup.private_files',
            'La estrategia de backup debe incluir los archivos clínicos privados.'
        );

        $this->require(
            $failures,
            filled($backup['provider'] ?? null),
            'durability.backup.provider',
            'DOCTOTAL_BACKUP_PROVIDER debe identificar el mecanismo operativo de respaldo.'
        );

        $frequencyHours = (int) ($backup['frequency_hours'] ?? 0);

        $this->require(
            $failures,
            $frequencyHours >= 1 && $frequencyHours <= 168,
            'durability.backup.frequency_hours',
            'La frecuencia de backup debe estar entre 1 y 168 horas.'
        );

        $this->require(
            $failures,
            (int) ($backup['retention_copies'] ?? 0) >= 2,
            'durability.backup.retention_copies',
            'La estrategia debe conservar al menos dos copias de respaldo.'
        );

        $runbook = (string) ($restore['runbook'] ?? '');

        $this->require(
            $failures,
            $runbook !== '' && is_file(base_path($runbook)),
            'durability.restore.runbook',
            'Debe existir un runbook de restauración versionado y accesible.'
        );

        $this->require(
            $failures,
            ($restore['verification_required'] ?? false) === true,
            'durability.restore.verification_required',
            'La restauración debe exigir verificación operativa posterior.'
        );

        $retentionMode = (string) ($retention['mode'] ?? '');

        $this->require(
            $failures,
            in_array($retentionMode, ['manual', 'policy'], true),
            'durability.retention.mode',
            'DOCTOTAL_RETENTION_MODE debe ser manual o policy.'
        );

        $this->require(
            $failures,
            ($retention['automatic_deletion_enabled'] ?? false) === false,
            'durability.retention.automatic_deletion',
            'El borrado automático de datos clínicos debe permanecer deshabilitado hasta aprobar una política legal y operativa.'
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
