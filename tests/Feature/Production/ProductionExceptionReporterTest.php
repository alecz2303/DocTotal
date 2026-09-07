<?php

namespace Tests\Feature\Production;

use App\Services\Production\ProductionExceptionReporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

class ProductionExceptionReporterTest extends TestCase
{
    public function test_reporter_does_nothing_outside_production(): void
    {
        Log::spy();
        config()->set('app.env', 'testing');
        config()->set('observability.enabled', true);

        app(ProductionExceptionReporter::class)->report(new RuntimeException('patient secret'));

        Log::shouldNotHaveReceived('channel');
    }

    public function test_reporter_uses_only_minimal_safe_context(): void
    {
        config()->set('app.env', 'production');
        config()->set('observability.enabled', true);
        config()->set('observability.channel', 'stack');
        config()->set('observability.include_exception_location', false);

        $request = Request::create(
            '/patients/123?token=secret-token',
            'POST',
            [
                'patient_name' => 'Sensitive Patient',
                'clinical_note' => 'Sensitive clinical content',
                'password' => 'secret-password',
            ]
        );

        $capturedMessage = null;
        $capturedContext = null;

        $logger = \Mockery::mock();
        $logger->shouldReceive('error')
            ->once()
            ->andReturnUsing(function (string $message, array $context) use (&$capturedMessage, &$capturedContext): void {
                $capturedMessage = $message;
                $capturedContext = $context;
            });

        Log::shouldReceive('channel')
            ->once()
            ->with('stack')
            ->andReturn($logger);

        app(ProductionExceptionReporter::class)->report(
            new RuntimeException('patient secret'),
            $request
        );

        $this->assertSame('DocTotal production exception.', $capturedMessage);
        $this->assertIsArray($capturedContext);
        $this->assertSame(RuntimeException::class, $capturedContext['exception_class'] ?? null);
        $this->assertSame('POST', $capturedContext['http_method'] ?? null);
        $this->assertArrayHasKey('fingerprint', $capturedContext);
        $this->assertArrayNotHasKey('source_file', $capturedContext);
        $this->assertArrayNotHasKey('source_line', $capturedContext);

        $serialized = json_encode($capturedContext, JSON_THROW_ON_ERROR);

        $this->assertStringNotContainsString('Sensitive Patient', $serialized);
        $this->assertStringNotContainsString('Sensitive clinical content', $serialized);
        $this->assertStringNotContainsString('secret-password', $serialized);
        $this->assertStringNotContainsString('secret-token', $serialized);
        $this->assertStringNotContainsString('patient secret', $serialized);
    }
}
