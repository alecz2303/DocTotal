<?php

namespace App\Services\Commercial;

use App\Models\GlobalSetting;
use App\Models\User;

class TrialSettings
{
    public const KEY = 'commercial.trial_days';
    public const MIN_DAYS = 1;
    public const MAX_DAYS = 90;

    public function days(): int
    {
        $stored = GlobalSetting::query()
            ->where('key', self::KEY)
            ->value('value');

        if (is_numeric($stored)) {
            $days = (int) $stored;

            if ($days >= self::MIN_DAYS && $days <= self::MAX_DAYS) {
                return $days;
            }
        }

        $fallback = (int) config('doctotal.trial_days', 3);

        return min(
            self::MAX_DAYS,
            max(self::MIN_DAYS, $fallback)
        );
    }

    public function update(int $days, ?User $user = null): GlobalSetting
    {
        $setting = GlobalSetting::query()->updateOrCreate(
            ['key' => self::KEY],
            [
                'value' => (string) $days,
                'updated_by_user_id' => $user?->id,
            ]
        );

        config(['doctotal.trial_days' => $days]);

        return $setting;
    }
}
