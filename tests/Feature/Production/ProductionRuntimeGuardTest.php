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
            'logging.channels.single.level' => 'info',
            'queue.default' => 'database',
            'queue.failed.driver' => 'database-uuids',
            'billing.automatic_charging_enabled' => false,
            'billing.payment_gateway' => 'stripe',
            'services.stripe.key' => 'pk_test_doctotal',
            'services.stripe.secret' => 'sk_test_doctotal',
            'services.stripe.webhook_secret' => 'whsec_doctotal',
        ]);
    }
}
