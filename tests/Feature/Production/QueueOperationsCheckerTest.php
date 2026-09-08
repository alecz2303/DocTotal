<?php

namespace Tests\Feature\Production;

use App\Services\Production\ProductionReadinessChecker;
use App\Services\Production\QueueOperationsChecker;
use Tests\TestCase;

class QueueOperationsCheckerTest extends TestCase
{
    public function test_scheduler_only_topology_is_ready(): void
    {
        config([
            'queue_operations.mode' => 'scheduler_only',
            'queue_operations.worker.enabled' => false,
            'queue_operations.failed_jobs.monitoring_enabled' => true,
            'queue_operations.failed_jobs.alert_threshold' => 1,
            'queue_operations.runbook' => 'docs/OPERATIONS_QUEUE_WORKERS.md',
        ]);

        $checker = app(QueueOperationsChecker::class);

        $this->assertTrue($checker->isReady());
        $this->assertSame([], $checker->failures());
    }

    public function test_scheduler_only_rejects_active_worker(): void
    {
        config([
            'queue_operations.mode' => 'scheduler_only',
            'queue_operations.worker.enabled' => true,
        ]);

        $keys = array_column(
            app(QueueOperationsChecker::class)->failures(),
            'key'
        );

        $this->assertContains('queue.worker.enabled', $keys);
    }

    public function test_workers_mode_requires_safe_worker_configuration(): void
    {
        config([
            'queue_operations.mode' => 'workers',
            'queue_operations.worker.enabled' => false,
            'queue_operations.worker.queue' => '',
            'queue_operations.worker.tries' => 0,
            'queue_operations.worker.timeout' => 90,
            'queue.default' => 'database',
            'queue.connections.database.retry_after' => 90,
        ]);

        $keys = array_column(
            app(QueueOperationsChecker::class)->failures(),
            'key'
        );

        $this->assertContains('queue.worker.enabled', $keys);
        $this->assertContains('queue.worker.queue', $keys);
        $this->assertContains('queue.worker.tries', $keys);
        $this->assertContains('queue.worker.retry_after', $keys);
    }

    public function test_failed_job_monitoring_is_required(): void
    {
        config([
            'queue_operations.failed_jobs.monitoring_enabled' => false,
        ]);

        $keys = array_column(
            app(QueueOperationsChecker::class)->failures(),
            'key'
        );

        $this->assertContains('queue.failed.monitoring', $keys);
    }

    public function test_production_readiness_includes_queue_operations_failures(): void
    {
        config([
            'queue_operations.failed_jobs.monitoring_enabled' => false,
        ]);

        $keys = array_column(
            app(ProductionReadinessChecker::class)->failures(),
            'key'
        );

        $this->assertContains('queue.failed.monitoring', $keys);
    }
}
