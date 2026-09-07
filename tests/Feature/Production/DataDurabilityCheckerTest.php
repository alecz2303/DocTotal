<?php

namespace Tests\Feature\Production;

use App\Services\Production\DataDurabilityChecker;
use Tests\TestCase;

class DataDurabilityCheckerTest extends TestCase
{
    public function test_complete_strategy_is_ready(): void
    {
        $this->configureReadyDurability();

        $checker = app(DataDurabilityChecker::class);

        $this->assertTrue($checker->isReady());
        $this->assertSame([], $checker->failures());
    }

    public function test_incomplete_backup_scope_is_reported(): void
    {
        $this->configureReadyDurability();

        config([
            'data_durability.backup.database' => false,
            'data_durability.backup.private_files' => false,
            'data_durability.backup.provider' => null,
        ]);

        $keys = collect(
            app(DataDurabilityChecker::class)->failures()
        )->pluck('key');

        $this->assertTrue($keys->contains('durability.backup.database'));
        $this->assertTrue($keys->contains('durability.backup.private_files'));
        $this->assertTrue($keys->contains('durability.backup.provider'));
    }

    public function test_invalid_frequency_and_copy_count_are_reported(): void
    {
        $this->configureReadyDurability();

        config([
            'data_durability.backup.frequency_hours' => 0,
            'data_durability.backup.retention_copies' => 1,
        ]);

        $keys = collect(
            app(DataDurabilityChecker::class)->failures()
        )->pluck('key');

        $this->assertTrue($keys->contains('durability.backup.frequency_hours'));
        $this->assertTrue($keys->contains('durability.backup.retention_copies'));
    }

    public function test_missing_restore_runbook_is_reported(): void
    {
        $this->configureReadyDurability();

        config([
            'data_durability.restore.runbook' => 'docs/missing-runbook.md',
        ]);

        $keys = collect(
            app(DataDurabilityChecker::class)->failures()
        )->pluck('key');

        $this->assertTrue($keys->contains('durability.restore.runbook'));
    }

    public function test_automatic_clinical_deletion_is_rejected(): void
    {
        $this->configureReadyDurability();

        config([
            'data_durability.retention.automatic_deletion_enabled' => true,
        ]);

        $keys = collect(
            app(DataDurabilityChecker::class)->failures()
        )->pluck('key');

        $this->assertTrue(
            $keys->contains('durability.retention.automatic_deletion')
        );
    }

    public function test_durability_command_succeeds_for_complete_strategy(): void
    {
        $this->configureReadyDurability();

        $this->artisan('doctotal:check-data-durability')
            ->expectsOutput('Durabilidad de datos de producción: OK.')
            ->assertExitCode(0);
    }

    public function test_durability_command_fails_for_incomplete_strategy(): void
    {
        $this->configureReadyDurability();

        config([
            'data_durability.backup.enabled' => false,
        ]);

        $this->artisan('doctotal:check-data-durability')
            ->expectsOutput(
                'La estrategia de durabilidad de datos está incompleta o es insegura.'
            )
            ->expectsOutput(
                '- [durability.backup.enabled] DOCTOTAL_BACKUP_ENABLED debe estar habilitado en producción.'
            )
            ->assertExitCode(1);
    }

    private function configureReadyDurability(): void
    {
        config([
            'data_durability.backup.enabled' => true,
            'data_durability.backup.database' => true,
            'data_durability.backup.private_files' => true,
            'data_durability.backup.provider' => 'managed-test-backups',
            'data_durability.backup.frequency_hours' => 24,
            'data_durability.backup.retention_copies' => 7,
            'data_durability.restore.runbook' => 'docs/OPERATIONS_DATA_DURABILITY.md',
            'data_durability.restore.verification_required' => true,
            'data_durability.retention.mode' => 'manual',
            'data_durability.retention.automatic_deletion_enabled' => false,
        ]);
    }
}
