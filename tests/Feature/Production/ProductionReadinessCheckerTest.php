<?php

namespace Tests\Feature\Production;

use App\Services\Production\ProductionReadinessChecker;
use Tests\TestCase;

class ProductionReadinessCheckerTest extends TestCase
{
    public function test_ready_configuration_has_no_failures(): void
    {
        $this->configureReadyProduction();

        $checker = app(
            ProductionReadinessChecker::class
        );

        $this->assertTrue(
            $checker->isReady()
        );

        $this->assertSame(
            [],
            $checker->failures()
        );
    }

    public function test_insecure_runtime_configuration_is_reported(): void
    {
        $this->configureReadyProduction();

        config([
            'app.env' => 'local',
            'app.name' => 'Laravel',
            'app.debug' => true,
            'app.url' => 'http://doctotal.test',
            'database.default' => 'sqlite',
            'session.secure' => false,
            'session.http_only' => false,
            'mail.default' => 'log',
            'mail.from.address' => 'hello@example.com',
            'logging.channels.single.level' => 'debug',
            'queue.default' => 'sync',
        ]);

        $keys = collect(
            app(ProductionReadinessChecker::class)->failures()
        )->pluck('key');

        $this->assertTrue($keys->contains('app.env'));
        $this->assertTrue($keys->contains('app.name'));
        $this->assertTrue($keys->contains('app.debug'));
        $this->assertTrue($keys->contains('app.url'));
        $this->assertTrue($keys->contains('database.default'));
        $this->assertTrue($keys->contains('session.secure'));
        $this->assertTrue($keys->contains('session.http_only'));
        $this->assertTrue($keys->contains('mail.default'));
        $this->assertTrue($keys->contains('mail.from.address'));
        $this->assertTrue($keys->contains('logging.level'));
        $this->assertTrue($keys->contains('queue.default'));
    }

    public function test_production_requires_real_stripe_configuration(): void
    {
        $this->configureReadyProduction();

        config([
            'billing.payment_gateway' => 'fake',
            'services.stripe.key' => null,
            'services.stripe.secret' => null,
            'services.stripe.webhook_secret' => null,
        ]);

        $keys = collect(
            app(ProductionReadinessChecker::class)->failures()
        )->pluck('key');

        $this->assertTrue(
            $keys->contains('billing.payment_gateway')
        );

        $this->assertTrue(
            $keys->contains('services.stripe.key')
        );

        $this->assertTrue(
            $keys->contains('services.stripe.secret')
        );

        $this->assertTrue(
            $keys->contains('services.stripe.webhook_secret')
        );
    }

    public function test_automatic_charging_can_remain_disabled_in_ready_production(): void
    {
        $this->configureReadyProduction();

        config([
            'billing.automatic_charging_enabled' => false,
        ]);

        $this->assertTrue(
            app(ProductionReadinessChecker::class)->isReady()
        );
    }

    public function test_command_succeeds_for_ready_configuration(): void
    {
        $this->configureReadyProduction();

        $this->artisan(
            'doctotal:check-production-readiness'
        )
            ->expectsOutput(
                'Configuración crítica de producción: OK.'
            )
            ->assertExitCode(0);
    }

    public function test_command_fails_for_insecure_configuration(): void
    {
        $this->configureReadyProduction();

        config([
            'app.debug' => true,
        ]);

        $this->artisan(
            'doctotal:check-production-readiness'
        )
            ->expectsOutput(
                'DocTotal no está listo para producción.'
            )
            ->assertExitCode(1);
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
            'session.secure' => true,
            'session.http_only' => true,
            'session.serialization' => 'json',
            'mail.default' => 'smtp',
            'mail.from.address' => 'no-reply@doctotal.test',
            'logging.channels.single.level' => 'info',
            'queue.default' => 'database',
            'billing.automatic_charging_enabled' => false,
            'billing.payment_gateway' => 'stripe',
            'services.stripe.key' => 'pk_test_doctotal',
            'services.stripe.secret' => 'sk_test_doctotal',
            'services.stripe.webhook_secret' => 'whsec_doctotal',
        ]);
    }
}
