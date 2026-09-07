<?php

return [
    'enabled' => (bool) env('DOCTOTAL_OBSERVABILITY_ENABLED', false),
    'channel' => env('DOCTOTAL_OBSERVABILITY_CHANNEL', 'stack'),
    'alerting_enabled' => (bool) env('DOCTOTAL_OBSERVABILITY_ALERTING_ENABLED', false),
    'runbook' => env(
        'DOCTOTAL_INCIDENT_RUNBOOK',
        'docs/OPERATIONS_INCIDENT_RESPONSE.md'
    ),
    'include_exception_location' => (bool) env(
        'DOCTOTAL_OBSERVABILITY_INCLUDE_EXCEPTION_LOCATION',
        true
    ),
];
