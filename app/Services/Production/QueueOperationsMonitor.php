<?php

namespace App\Services\Production;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class QueueOperationsMonitor
{
    /**
     * @return array{mode: string, pending_jobs: int, failed_jobs: int, alert_threshold: int}
     */
    public function snapshot(): array
    {
        return [
            'mode' => (string) config('queue_operations.mode'),
            'pending_jobs' => Queue::connection()->size(),
            'failed_jobs' => DB::table(
                (string) config('queue.failed.table', 'failed_jobs')
            )->count(),
            'alert_threshold' => (int) config(
                'queue_operations.failed_jobs.alert_threshold',
                1
            ),
        ];
    }

    public function hasFailedJobAlert(array $snapshot): bool
    {
        return $snapshot['failed_jobs'] >= $snapshot['alert_threshold'];
    }
}
