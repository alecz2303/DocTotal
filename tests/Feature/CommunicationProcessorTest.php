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

class CommunicationProcessorTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_communication_can_be_sent_successfully(): void
    {
        $communication = $this->createCommunication();

        $transport = new class implements CommunicationTransport
        {
            public int $calls = 0;

            public function send(
                Communication $communication
            ): void {
                $this->calls++;
            }
        };

        $result = app(CommunicationProcessor::class)
            ->process(
                $communication,
                $transport
            );

        $communication->refresh();

        $this->assertTrue($result);

        $this->assertSame(
            Communication::STATUS_SENT,
            $communication->status
        );

        $this->assertSame(
            1,
            $communication->attempt_count
        );

        $this->assertNotNull(
            $communication->sent_at
        );

        $this->assertNull(
            $communication->failed_at
        );

        $this->assertNull(
            $communication->last_error
        );

        $this->assertSame(
            1,
            $transport->calls
        );
    }

    public function test_transport_failure_is_recorded_without_being_rethrown(): void
    {
        $communication = $this->createCommunication();

        $transport = new class implements CommunicationTransport
        {
            public function send(
                Communication $communication
            ): void {
                throw new RuntimeException(
                    'Transport unavailable'
                );
            }
        };

        $result = app(CommunicationProcessor::class)
            ->process(
                $communication,
                $transport
            );

        $communication->refresh();

        $this->assertFalse($result);

        $this->assertSame(
            Communication::STATUS_FAILED,
            $communication->status
        );

        $this->assertSame(
            1,
            $communication->attempt_count
        );

        $this->assertNotNull(
            $communication->failed_at
        );

        $this->assertNull(
            $communication->sent_at
        );

        $this->assertSame(
            'Transport unavailable',
            $communication->last_error
        );
    }

    public function test_failed_communication_can_be_retried_when_retry_time_arrives(): void
    {
        $communication = $this->createCommunication();

        $failingTransport = new class implements CommunicationTransport
        {
            public function send(
                Communication $communication
            ): void {
                throw new RuntimeException(
                    'Temporary failure'
                );
            }
        };

        $processor = app(CommunicationProcessor::class);

        $firstResult = $processor->process(
            $communication,
            $failingTransport
        );

        $communication->refresh();

        $this->assertFalse($firstResult);

        $this->assertSame(
            1,
            $communication->attempt_count
        );

        $this->assertNotNull(
            $communication->next_attempt_at
        );

        /*
        * Antes de los cinco minutos no debe permitirse
        * otro intento.
        */
        $successfulTransport = new class implements CommunicationTransport
        {
            public int $calls = 0;

            public function send(
                Communication $communication
            ): void {
                $this->calls++;
            }
        };

        $earlyResult = $processor->process(
            $communication,
            $successfulTransport
        );

        $communication->refresh();

        $this->assertFalse($earlyResult);

        $this->assertSame(
            0,
            $successfulTransport->calls
        );

        $this->assertSame(
            1,
            $communication->attempt_count
        );

        /*
        * Una vez transcurrido el intervalo, el reintento
        * sí puede ejecutarse.
        */
        $this->travel(5)->minutes();

        $secondResult = $processor->process(
            $communication,
            $successfulTransport
        );

        $communication->refresh();

        $this->assertTrue($secondResult);

        $this->assertSame(
            1,
            $successfulTransport->calls
        );

        $this->assertSame(
            Communication::STATUS_SENT,
            $communication->status
        );

        $this->assertSame(
            2,
            $communication->attempt_count
        );

        $this->assertNotNull(
            $communication->sent_at
        );

        $this->assertNull(
            $communication->failed_at
        );

        $this->assertNull(
            $communication->next_attempt_at
        );

        $this->assertNull(
            $communication->last_error
        );
    }

    public function test_sent_communication_is_not_sent_again(): void
    {
        $communication = $this->createCommunication();

        $communication->markSent();
        $communication->refresh();

        $transport = new class implements CommunicationTransport
        {
            public int $calls = 0;

            public function send(
                Communication $communication
            ): void {
                $this->calls++;
            }
        };

        $result = app(CommunicationProcessor::class)
            ->process(
                $communication,
                $transport
            );

        $communication->refresh();

        $this->assertTrue($result);

        $this->assertSame(
            0,
            $transport->calls
        );

        $this->assertSame(
            0,
            $communication->attempt_count
        );

        $this->assertSame(
            Communication::STATUS_SENT,
            $communication->status
        );
    }

    public function test_each_failed_retry_uses_controlled_backoff(): void
    {
        $communication = $this->createCommunication();

        $transport = new class implements CommunicationTransport
        {
            public function send(
                Communication $communication
            ): void {
                throw new RuntimeException(
                    'Still unavailable'
                );
            }
        };

        $processor = app(CommunicationProcessor::class);

        /*
        * Intento 1.
        */
        $processor->process(
            $communication,
            $transport
        );

        $communication->refresh();

        $this->assertSame(
            1,
            $communication->attempt_count
        );

        $this->assertEqualsWithDelta(
            now()->addMinutes(5)->timestamp,
            $communication->next_attempt_at->timestamp,
            1
        );

        /*
        * Intento 2.
        */
        $this->travel(5)->minutes();

        $processor->process(
            $communication,
            $transport
        );

        $communication->refresh();

        $this->assertSame(
            2,
            $communication->attempt_count
        );

        $this->assertEqualsWithDelta(
            now()->addMinutes(15)->timestamp,
            $communication->next_attempt_at->timestamp,
            1
        );

        /*
        * Intento 3.
        */
        $this->travel(15)->minutes();

        $processor->process(
            $communication,
            $transport
        );

        $communication->refresh();

        $this->assertSame(
            Communication::STATUS_FAILED,
            $communication->status
        );

        $this->assertSame(
            3,
            $communication->attempt_count
        );

        $this->assertSame(
            'Still unavailable',
            $communication->last_error
        );

        /*
        * Al agotarse los intentos ya no existe otro
        * reintento programado.
        */
        $this->assertNull(
            $communication->next_attempt_at
        );
    }

    public function test_communication_that_exhausted_attempts_is_not_processed_again(): void
    {
        $communication = $this->createCommunication();

        $communication->update([
            'status' => Communication::STATUS_FAILED,
            'attempt_count' => CommunicationProcessor::MAX_ATTEMPTS,
            'failed_at' => now(),
            'next_attempt_at' => null,
            'last_error' => 'Permanent failure',
        ]);

        $transport = new class implements CommunicationTransport
        {
            public int $calls = 0;

            public function send(
                Communication $communication
            ): void {
                $this->calls++;
            }
        };

        $result = app(CommunicationProcessor::class)
            ->process(
                $communication,
                $transport
            );

        $communication->refresh();

        $this->assertFalse($result);

        $this->assertSame(
            0,
            $transport->calls
        );

        $this->assertSame(
            CommunicationProcessor::MAX_ATTEMPTS,
            $communication->attempt_count
        );

        $this->assertSame(
            Communication::STATUS_FAILED,
            $communication->status
        );
    }

    private function createCommunication(): Communication
    {
        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Prueba',
            'email' => 'paciente@example.com',
            'phone' => '9611111111',
            'whatsapp' => '9612222222',
        ]);

        return Communication::create([
            'patient_id' => $patient->id,
            'type' => Communication::TYPE_APPOINTMENT_REMINDER,
            'channel' => Communication::CHANNEL_WHATSAPP,
            'recipient' => $patient->whatsapp,
            'body' => 'Recordatorio de cita.',
            'idempotency_key' => 'processor-test-' . uniqid(),
        ]);
    }
}
