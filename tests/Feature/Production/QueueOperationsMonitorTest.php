<?php

namespace Tests\Feature\Production;

use App\Services\Production\QueueOperationsMonitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class QueueOperationsMonitorTest extends TestCase
{
    use RefreshDatabase;

    public function test_snapshot_exposes_only_aggregate_operational_counts(): void
    {
        config([
            'queue.default' => 'database',
        ]);

        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => 'sensitive-patient-payload',
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);

        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => 'secret-clinical-payload',
            'exception' => 'private exception details',
            'failed_at' => now(),
        ]);

        $snapshot = app(QueueOperationsMonitor::class)->snapshot();

        $this->assertSame('scheduler_only', $snapshot['mode']);
        $this->assertSame(1, $snapshot['pending_jobs']);
        $this->assertSame(1, $snapshot['failed_jobs']);
        $this->assertSame(1, $snapshot['alert_threshold']);

        $serialized = json_encode($snapshot);

        $this->assertIsString($serialized);
        $this->assertStringNotContainsString('sensitive-patient-payload', $serialized);
        $this->assertStringNotContainsString('secret-clinical-payload', $serialized);
        $this->assertStringNotContainsString('private exception details', $serialized);
    }

    public function test_failed_job_alert_uses_configured_threshold(): void
    {
        config([
            'queue_operations.failed_jobs.alert_threshold' => 2,
        ]);

        $monitor = app(QueueOperationsMonitor::class);

        $this->assertFalse($monitor->hasFailedJobAlert([
            'mode' => 'scheduler_only',
            'pending_jobs' => 0,
            'failed_jobs' => 1,
            'alert_threshold' => 2,
        ]));

        $this->assertTrue($monitor->hasFailedJobAlert([
            'mode' => 'scheduler_only',
            'pending_jobs' => 0,
            'failed_jobs' => 2,
            'alert_threshold' => 2,
        ]));
    }
}
