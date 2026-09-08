<?php

namespace Tests;

use App\Services\Communications\Transports\LaravelMailCommunicationTransport;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Historical DocTotal feature tests predate mandatory email verification.
     *
     * By default, a user authenticated through actingAs() is treated as an
     * already-verified account. This keeps legacy tests focused on the feature
     * they were written for instead of being intercepted by the `verified`
     * middleware introduced in DT-28.
     *
     * Tests that intentionally exercise unverified accounts can opt out by
     * setting this property to true.
     */
    protected bool $preserveUnverifiedUsers = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Production foundations added after the historical feature suite must
        // start from a safe baseline. Tests for unsafe states override these
        // values explicitly.
        config([
            'observability.enabled' => true,
            'observability.channel' => 'stack',
            'observability.alerting_enabled' => true,
            'observability.runbook' => 'docs/OPERATIONS_INCIDENT_RESPONSE.md',
            'queue_operations.mode' => 'scheduler_only',
            'queue_operations.worker.enabled' => false,
            'queue_operations.failed_jobs.monitoring_enabled' => true,
            'queue_operations.failed_jobs.alert_threshold' => 1,
            'queue_operations.runbook' => 'docs/OPERATIONS_QUEUE_WORKERS.md',
            'communications.transports.email' => LaravelMailCommunicationTransport::class,
            'mail.mailers.smtp.host' => 'smtp.test',
            'mail.mailers.smtp.port' => 587,
        ]);
    }

    public function actingAs(Authenticatable $user, $guard = null)
    {
        if (! $this->preserveUnverifiedUsers
            && method_exists($user, 'hasVerifiedEmail')
            && ! $user->hasVerifiedEmail()
            && method_exists($user, 'forceFill')
            && method_exists($user, 'saveQuietly')) {
            // Test-only compatibility: do not dispatch Verified or model events,
            // because historical audit/event tests must remain unaffected.
            $user->forceFill([
                'email_verified_at' => now(),
            ])->saveQuietly();
        }

        return parent::actingAs($user, $guard);
    }
}
