<?php

return [

    'backup' => [
        'enabled' => (bool) env('DOCTOTAL_BACKUP_ENABLED', false),
        'database' => (bool) env('DOCTOTAL_BACKUP_DATABASE', false),
        'private_files' => (bool) env('DOCTOTAL_BACKUP_PRIVATE_FILES', false),
        'provider' => env('DOCTOTAL_BACKUP_PROVIDER'),
        'frequency_hours' => (int) env('DOCTOTAL_BACKUP_FREQUENCY_HOURS', 24),
        'retention_copies' => (int) env('DOCTOTAL_BACKUP_RETENTION_COPIES', 7),
    ],

    'restore' => [
        'runbook' => env(
            'DOCTOTAL_RESTORE_RUNBOOK',
            'docs/OPERATIONS_DATA_DURABILITY.md'
        ),
        'verification_required' => (bool) env(
            'DOCTOTAL_RESTORE_VERIFICATION_REQUIRED',
            true
        ),
    ],

    'retention' => [
        'mode' => env('DOCTOTAL_RETENTION_MODE', 'manual'),
        'automatic_deletion_enabled' => (bool) env(
            'DOCTOTAL_RETENTION_AUTOMATIC_DELETION_ENABLED',
            false
        ),
    ],

];
