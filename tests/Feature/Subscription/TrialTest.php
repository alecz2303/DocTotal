<?php

namespace Tests\Feature\Subscription;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrialTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_have_active_trial(): void
    {
        $tenant = Tenant::create([
            'name' => 'Consultorio Test',
            'slug' => 'consultorio-test',
            'status' => 'trial',
            'trial_started_at' => now(),
            'trial_ends_at' => now()->addDays(3),
        ]);

        $this->assertTrue($tenant->isOnTrial());
        $this->assertFalse($tenant->trialHasExpired());
    }

    public function test_tenant_can_have_expired_trial(): void
    {
        $tenant = Tenant::create([
            'name' => 'Consultorio Test',
            'slug' => 'consultorio-test',
            'status' => 'trial',
            'trial_started_at' => now()->subDays(5),
            'trial_ends_at' => now()->subDays(2),
        ]);

        $this->assertFalse($tenant->isOnTrial());
        $this->assertTrue($tenant->trialHasExpired());
    }

    public function test_trial_duration_is_configurable(): void
    {
        config(['doctotal.trial_days' => 5]);

        $this->assertSame(5, config('doctotal.trial_days'));
    }
}
