<?php

return [
    'mode' => env('DOCTOTAL_QUEUE_MODE', 'scheduler_only'),

    'worker' => [
        'enabled' => (bool) env('DOCTOTAL_QUEUE_WORKER_ENABLED', false),
        'queue' => env('DOCTOTAL_QUEUE_WORKER_QUEUE', 'default'),
        'tries' => (int) env('DOCTOTAL_QUEUE_WORKER_TRIES', 3),
        'timeout' => (int) env('DOCTOTAL_QUEUE_WORKER_TIMEOUT', 60),
    ],

    'failed_jobs' => [
        'monitoring_enabled' => (bool) env(
            'DOCTOTAL_FAILED_JOB_MONITORING_ENABLED',
            false
        ),
        'alert_threshold' => (int) env(
            'DOCTOTAL_FAILED_JOB_ALERT_THRESHOLD',
            1
        ),
    ],

    'runbook' => env(
        'DOCTOTAL_QUEUE_RUNBOOK',
        'docs/OPERATIONS_QUEUE_WORKERS.md'
    ),
];
