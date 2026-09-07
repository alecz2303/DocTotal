<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Services\Production\DataDurabilityChecker;
use Illuminate\View\View;

class InternalDataDurabilityController extends Controller
{
    public function __invoke(DataDurabilityChecker $checker): View
    {
        $failures = $checker->failures();
        $failureKeys = collect($failures)->pluck('key');

        $backup = (array) config('data_durability.backup', []);
        $restore = (array) config('data_durability.restore', []);
        $retention = (array) config('data_durability.retention', []);
        $runbook = (string) ($restore['runbook'] ?? '');

        $unsafe = $failureKeys->contains('durability.retention.automatic_deletion')
            || $failureKeys->contains('durability.retention.mode');

        return view('internal.data-durability.index', [
            'status' => $checker->isReady()
                ? 'ready'
                : ($unsafe ? 'unsafe' : 'incomplete'),
            'failures' => $failures,
            'backup' => [
                'enabled' => ($backup['enabled'] ?? false) === true,
                'database' => ($backup['database'] ?? false) === true,
                'private_files' => ($backup['private_files'] ?? false) === true,
                'provider' => filled($backup['provider'] ?? null)
                    ? (string) $backup['provider']
                    : null,
                'frequency_hours' => (int) ($backup['frequency_hours'] ?? 0),
                'retention_copies' => (int) ($backup['retention_copies'] ?? 0),
            ],
            'restore' => [
                'runbook' => $runbook,
                'runbook_exists' => $runbook !== '' && is_file(base_path($runbook)),
                'verification_required' => ($restore['verification_required'] ?? false) === true,
            ],
            'retention' => [
                'mode' => (string) ($retention['mode'] ?? ''),
                'automatic_deletion_enabled' => ($retention['automatic_deletion_enabled'] ?? false) === true,
            ],
        ]);
    }
}
