<?php

namespace Tests\Feature\Production;

use App\Services\Production\ProductionRuntimeGuard;
use LogicException;
use Tests\TestCase;

class ProductionRuntimeGuardTest extends TestCase
{
    public function test_ready_configuration_allows_runtime_start(): void
    {
        $this->configureReadyProduction();

        app(ProductionRuntimeGuard::class)->assertReady();

        $this->assertTrue(true);
    }

    public function test_insecure_configuration_blocks_runtime_without_exposing_values(): void
    {
        $this->configureReadyProduction();

        config([
            'app.debug' => true,
            'services.stripe.secret' => 'super-secret-value',
            'services.stripe.webhook_secret' => null,
        ]);

        try {
            app(ProductionRuntimeGuard::class)->assertReady();
            $this->fail('Se esperaba que el guard bloqueara el runtime.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString(
                'app.debug',
                $exception->getMessage()
            );

            $this->assertStringContainsString(
                'services.stripe.webhook_secret',
                $exception->getMessage()
            );

            $this->assertStringNotContainsString(
                'super-secret-value',
                $exception->getMessage()
            );
        }
    }

    private function configureReadyProduction(): void
    {
        config([
            'app.env' => 'production',
            'app.name' => 'DocTotal',
            'app.debug' => false,
            'app.key' => 'base64:'.base64_encode(str_repeat('k', 32)),
            'app.url' => 'https://doctotal.test',
            'database.default' => 'mysql',
            'session.driver' => 'database',
            'session.secure' => true,
            'session.http_only' => true,
            'session.serialization' => 'json',
            'cache.default' => 'database',
            'mail.default' => 'smtp',
            'mail.from.address' => 'no-reply@doctotal.test',
            'logging.default' => 'single',
            'logging.channels.single.level' => 'info',
            'queue.default' => 'database',
            'queue.failed.driver' => 'database-uuids',
            'billing.automatic_charging_enabled' => false,
            'billing.payment_gateway' => 'stripe',
            'services.stripe.key' => 'pk_test_doctotal',
            'services.stripe.secret' => 'sk_test_doctotal',
            'services.stripe.webhook_secret' => 'whsec_doctotal',
            'data_durability.backup.enabled' => true,
            'data_durability.backup.database' => true,
            'data_durability.backup.private_files' => true,
            'data_durability.backup.provider' => 'managed-backup',
            'data_durability.backup.frequency_hours' => 24,
            'data_durability.backup.retention_copies' => 7,
            'data_durability.restore.runbook' => 'docs/OPERATIONS_DATA_DURABILITY.md',
            'data_durability.restore.verification_required' => true,
            'data_durability.retention.mode' => 'manual',
            'data_durability.retention.automatic_deletion_enabled' => false,
        ]);
    }
}
