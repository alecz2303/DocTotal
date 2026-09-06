<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\Commercial\TrialSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InternalTrialSettingsController extends Controller
{
    public function index(TrialSettings $settings): View
    {
        return view('internal.settings.trial', [
            'trialDays' => $settings->days(),
            'fallbackDays' => (int) config('doctotal.trial_days', 3),
            'minDays' => TrialSettings::MIN_DAYS,
            'maxDays' => TrialSettings::MAX_DAYS,
        ]);
    }

    public function update(
        Request $request,
        TrialSettings $settings,
        AuditLogger $auditLogger
    ): RedirectResponse {
        $validated = $request->validate([
            'trial_days' => [
                'required',
                'integer',
                'min:'.TrialSettings::MIN_DAYS,
                'max:'.TrialSettings::MAX_DAYS,
            ],
        ]);

        $previousDays = $settings->days();
        $newDays = (int) $validated['trial_days'];
        $setting = $settings->update($newDays, $request->user());

        $auditLogger->safeLogGlobal(
            action: 'internal.settings.trial_days.updated',
            auditable: $setting,
            description: 'Duración predeterminada del periodo de prueba actualizada.',
            metadata: [
                'previous_days' => $previousDays,
                'new_days' => $newDays,
            ],
        );

        return redirect()
            ->route('internal.settings.trial')
            ->with('status', 'Duración del periodo de prueba actualizada.');
    }
}
