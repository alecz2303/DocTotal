<?php

namespace Tests\Feature\Production;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckQueueOperationsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_succeeds_without_failed_jobs(): void
    {
        DB::table('jobs')->delete();
        DB::table('failed_jobs')->delete();

        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame(0, DB::table('failed_jobs')->count());

        $this->artisan('doctotal:check-queue-operations')
            ->expectsOutput('Topología de colas/workers: OK.')
            ->expectsOutput('Modo: scheduler_only')
            ->expectsOutput('Jobs pendientes: 0')
            ->expectsOutput('Jobs fallidos: 0')
            ->assertExitCode(0);
    }

    public function test_command_fails_when_failed_job_threshold_is_reached(): void
    {
        DB::table('jobs')->delete();
        DB::table('failed_jobs')->delete();

        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => 'do-not-print-this-payload',
            'exception' => 'do-not-print-this-exception',
            'failed_at' => now(),
        ]);

        $this->artisan('doctotal:check-queue-operations')
            ->expectsOutput('Topología de colas/workers: OK.')
            ->expectsOutput('Jobs fallidos: 1')
            ->expectsOutput(
                'Se alcanzó el umbral operativo de failed jobs; requiere revisión antes de reintentar.'
            )
            ->doesntExpectOutput('do-not-print-this-payload')
            ->doesntExpectOutput('do-not-print-this-exception')
            ->assertExitCode(1);
    }

    public function test_command_fails_for_unsafe_topology_before_querying_payloads(): void
    {
        config([
            'queue_operations.failed_jobs.monitoring_enabled' => false,
        ]);

        $this->artisan('doctotal:check-queue-operations')
            ->expectsOutput('La estrategia de colas/workers está incompleta o es insegura.')
            ->assertExitCode(1);
    }
}
