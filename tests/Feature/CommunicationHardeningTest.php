<?php

namespace Tests\Feature;

use App\Contracts\Communications\CommunicationTransport;
use App\Models\Communication;
use App\Models\Patient;
use App\Models\Tenant;
use App\Services\Communications\CommunicationProcessor;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class CommunicationHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_processing_communication_cannot_be_claimed_twice(): void
    {
        $communication = $this->communication();
        $communication->markProcessing();

        $transport = new class implements CommunicationTransport {
            public int $calls = 0;
            public function send(Communication $communication): void { $this->calls++; }
        };

        $result = app(CommunicationProcessor::class)->process($communication, $transport);

        $this->assertFalse($result);
        $this->assertSame(0, $transport->calls);
        $this->assertSame(1, $communication->fresh()->attempt_count);
        $this->assertSame(Communication::STATUS_PROCESSING, $communication->fresh()->status);
    }

    public function test_transport_errors_are_redacted_before_persistence(): void
    {
        $communication = $this->communication();

        $transport = new class implements CommunicationTransport {
            public function send(Communication $communication): void
            {
                throw new RuntimeException('provider secret=sk_live_supersecret token=abc123');
            }
        };

        app(CommunicationProcessor::class)->process($communication, $transport);

        $error = $communication->fresh()->last_error;

        $this->assertStringNotContainsString('sk_live_supersecret', $error);
        $this->assertStringNotContainsString('abc123', $error);
        $this->assertStringContainsString('[REDACTED]', $error);
    }

    private function communication(): Communication
    {
        $tenant = Tenant::create(['name' => 'Tenant A', 'slug' => 'tenant-a']);
        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Prueba',
            'whatsapp' => '9612222222',
        ]);

        return Communication::create([
            'patient_id' => $patient->id,
            'type' => Communication::TYPE_APPOINTMENT_REMINDER,
            'channel' => Communication::CHANNEL_WHATSAPP,
            'recipient' => $patient->whatsapp,
            'body' => 'Recordatorio de cita.',
            'idempotency_key' => 'hardening-'.uniqid(),
        ]);
    }
}
