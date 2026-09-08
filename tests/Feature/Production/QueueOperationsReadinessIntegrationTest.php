<?php

namespace Tests\Feature\Production;

use App\Services\Production\ProductionReadinessChecker;
use Tests\TestCase;

class QueueOperationsReadinessIntegrationTest extends TestCase
{
    public function test_worker_mode_with_unsafe_retry_after_blocks_readiness(): void
    {
        config([
            'queue_operations.mode' => 'workers',
            'queue_operations.worker.enabled' => true,
            'queue_operations.worker.queue' => 'default',
            'queue_operations.worker.tries' => 3,
            'queue_operations.worker.timeout' => 90,
            'queue.default' => 'database',
            'queue.connections.database.retry_after' => 90,
        ]);

        $keys = array_column(
            app(ProductionReadinessChecker::class)->failures(),
            'key'
        );

        $this->assertContains('queue.worker.retry_after', $keys);
    }
}
