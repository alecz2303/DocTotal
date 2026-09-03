<?php

namespace Tests\Unit\Models;

use App\Models\Tenant;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TenantTrialPresentationTest extends TestCase
{
    public function test_it_calculates_trial_duration_and_remaining_days(): void
    {
        Carbon::setTestNow('2026-09-02 12:00:00');

        $tenant = new Tenant([
            'status' => 'trial',
            'trial_started_at' => '2026-08-20 10:00:00',
            'trial_ends_at' => '2026-09-19 10:00:00',
        ]);

        $this->assertSame(30, $tenant->trialDurationInDays());
        $this->assertSame(17, $tenant->trialDaysRemaining());
        $this->assertNull($tenant->trialDaysExpired());

        Carbon::setTestNow();
    }

    public function test_it_calculates_days_since_trial_expired(): void
    {
        Carbon::setTestNow('2026-09-02 12:00:00');

        $tenant = new Tenant([
            'status' => 'trial',
            'trial_started_at' => '2026-08-01 10:00:00',
            'trial_ends_at' => '2026-08-28 10:00:00',
        ]);

        $this->assertSame(27, $tenant->trialDurationInDays());
        $this->assertSame(0, $tenant->trialDaysRemaining());
        $this->assertSame(5, $tenant->trialDaysExpired());

        Carbon::setTestNow();
    }

    public function test_trial_calculations_are_null_without_required_dates(): void
    {
        $tenant = new Tenant([
            'status' => 'trial',
        ]);

        $this->assertNull($tenant->trialDurationInDays());
        $this->assertNull($tenant->trialDaysRemaining());
        $this->assertNull($tenant->trialDaysExpired());
    }
}
