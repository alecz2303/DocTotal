<?php

namespace Tests\Feature\Production;

use App\Services\Production\ObservabilityChecker;
use App\Services\Production\ProductionReadinessChecker;
use Tests\TestCase;

class ObservabilityCheckerTest extends TestCase
{
    public function test_complete_observability_strategy_is_ready(): void
    {
        config([
            'observability.enabled' => true,
            'observability.channel' => 'single',
            'observability.alerting_enabled' => true,
            'observability.runbook' => 'docs/OPERATIONS_INCIDENT_RESPONSE.md',
        ]);

        $checker = app(ObservabilityChecker::class);

        $this->assertTrue($checker->isReady());
        $this->assertSame([], $checker->failures());
    }

    public function test_incomplete_observability_strategy_reports_failures(): void
    {
        config([
            'observability.enabled' => false,
            'observability.channel' => 'missing-channel',
            'observability.alerting_enabled' => false,
            'observability.runbook' => 'docs/MISSING_INCIDENT_RUNBOOK.md',
        ]);

        $failures = app(ObservabilityChecker::class)->failures();
        $keys = array_column($failures, 'key');

        $this->assertContains('observability.enabled', $keys);
        $this->assertContains('observability.channel', $keys);
        $this->assertContains('observability.alerting', $keys);
        $this->assertContains('observability.runbook', $keys);
    }

    public function test_production_readiness_includes_observability_failures(): void
    {
        config([
            'observability.enabled' => false,
            'observability.channel' => 'single',
            'observability.alerting_enabled' => false,
            'observability.runbook' => 'docs/OPERATIONS_INCIDENT_RESPONSE.md',
        ]);

        $keys = array_column(
            app(ProductionReadinessChecker::class)->failures(),
            'key'
        );

        $this->assertContains('observability.enabled', $keys);
        $this->assertContains('observability.alerting', $keys);
    }
}
