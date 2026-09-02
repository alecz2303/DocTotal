<?php

namespace Tests\Feature;

use App\Contracts\Communications\CommunicationTransport;
use App\Models\Communication;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\Appointment;
use App\Models\DoctorProfile;
use App\Models\User;
use App\Services\Communications\AppointmentReminderService;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ProcessDueCommunicationsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'communications.transports.whatsapp',
            ProcessDueSuccessfulCommunicationTransport::class
        );
    }

    public function test_due_pending_communication_is_processed(): void
    {
        $communication = $this->createCommunication([
            'scheduled_for' => now()->subMinute(),
        ]);

        app(TenantContext::class)->clear();

        $this->artisan(
            'communications:process-due'
        )
            ->expectsOutput(
                'Comunicaciones: 1 procesadas, 1 enviadas, 0 fallidas, 0 omitidas, 0 errores.'
            )
            ->assertSuccessful();

        $communication->refresh();

        $this->assertSame(
            Communication::STATUS_SENT,
            $communication->status
        );

        $this->assertSame(
            1,
            $communication->attempt_count
        );
    }

    public function test_future_pending_communication_is_not_processed(): void
    {
        $communication = $this->createCommunication([
            'scheduled_for' => now()->addHour(),
        ]);

        app(TenantContext::class)->clear();

        $this->artisan(
            'communications:process-due'
        )
            ->expectsOutput(
                'Comunicaciones: 0 procesadas, 0 enviadas, 0 fallidas, 0 omitidas, 0 errores.'
            )
            ->assertSuccessful();

        $communication->refresh();

        $this->assertSame(
            Communication::STATUS_PENDING,
            $communication->status
        );

        $this->assertSame(
            0,
            $communication->attempt_count
        );
    }

    public function test_failed_communication_is_retried_when_due(): void
    {
        $communication = $this->createCommunication([
            'status' => Communication::STATUS_FAILED,
            'attempt_count' => 1,
            'failed_at' => now()->subMinutes(10),
            'next_attempt_at' => now()->subMinute(),
            'last_error' => 'Temporary failure',
        ]);

        app(TenantContext::class)->clear();

        $this->artisan(
            'communications:process-due'
        )
            ->expectsOutput(
                'Comunicaciones: 1 procesadas, 1 enviadas, 0 fallidas, 0 omitidas, 0 errores.'
            )
            ->assertSuccessful();

        $communication->refresh();

        $this->assertSame(
            Communication::STATUS_SENT,
            $communication->status
        );

        $this->assertSame(
            2,
            $communication->attempt_count
        );

        $this->assertNull(
            $communication->next_attempt_at
        );
    }

    public function test_failed_communication_with_future_retry_is_not_processed(): void
    {
        $communication = $this->createCommunication([
            'status' => Communication::STATUS_FAILED,
            'attempt_count' => 1,
            'failed_at' => now(),
            'next_attempt_at' => now()->addMinutes(5),
            'last_error' => 'Temporary failure',
        ]);

        app(TenantContext::class)->clear();

        $this->artisan(
            'communications:process-due'
        )
            ->expectsOutput(
                'Comunicaciones: 0 procesadas, 0 enviadas, 0 fallidas, 0 omitidas, 0 errores.'
            )
            ->assertSuccessful();

        $communication->refresh();

        $this->assertSame(
            1,
            $communication->attempt_count
        );

        $this->assertSame(
            Communication::STATUS_FAILED,
            $communication->status
        );
    }

    public function test_transport_failure_is_recorded(): void
    {
        config()->set(
            'communications.transports.whatsapp',
            ProcessDueFailingCommunicationTransport::class
        );

        $communication = $this->createCommunication([
            'scheduled_for' => now()->subMinute(),
        ]);

        app(TenantContext::class)->clear();

        $this->artisan(
            'communications:process-due'
        )
            ->expectsOutput(
                'Comunicaciones: 1 procesadas, 0 enviadas, 1 fallidas, 0 omitidas, 0 errores.'
            )
            ->assertSuccessful();

        $communication->refresh();

        $this->assertSame(
            Communication::STATUS_FAILED,
            $communication->status
        );

        $this->assertSame(
            1,
            $communication->attempt_count
        );

        $this->assertSame(
            'Provider unavailable',
            $communication->last_error
        );

        $this->assertNotNull(
            $communication->next_attempt_at
        );
    }

    public function test_sent_communication_is_not_processed_again(): void
    {
        $communication = $this->createCommunication([
            'status' => Communication::STATUS_SENT,
            'sent_at' => now()->subMinute(),
            'scheduled_for' => now()->subHour(),
        ]);

        app(TenantContext::class)->clear();

        $this->artisan(
            'communications:process-due'
        )
            ->expectsOutput(
                'Comunicaciones: 0 procesadas, 0 enviadas, 0 fallidas, 0 omitidas, 0 errores.'
            )
            ->assertSuccessful();

        $communication->refresh();

        $this->assertSame(
            0,
            $communication->attempt_count
        );
    }

    public function test_multiple_tenants_are_processed_without_mixing_context(): void
    {
        $tenantA = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        app(TenantContext::class)->set($tenantA);

        $communicationA = $this->createCommunicationForCurrentTenant(
            'tenant-a-message'
        );

        $tenantB = Tenant::create([
            'name' => 'Tenant B',
            'slug' => 'tenant-b',
        ]);

        app(TenantContext::class)->set($tenantB);

        $communicationB = $this->createCommunicationForCurrentTenant(
            'tenant-b-message'
        );

        app(TenantContext::class)->clear();

        $this->artisan(
            'communications:process-due'
        )
            ->expectsOutput(
                'Comunicaciones: 2 procesadas, 2 enviadas, 0 fallidas, 0 omitidas, 0 errores.'
            )
            ->assertSuccessful();

        $this->assertDatabaseHas(
            'communications',
            [
                'id' => $communicationA->id,
                'tenant_id' => $tenantA->id,
                'status' => Communication::STATUS_SENT,
            ]
        );

        $this->assertDatabaseHas(
            'communications',
            [
                'id' => $communicationB->id,
                'tenant_id' => $tenantB->id,
                'status' => Communication::STATUS_SENT,
            ]
        );
    }

    public function test_tenant_context_is_cleared_after_command(): void
    {
        $this->createCommunication([
            'scheduled_for' => now()->subMinute(),
        ]);

        app(TenantContext::class)->clear();

        $this->artisan(
            'communications:process-due'
        )->assertSuccessful();

        $this->assertFalse(
            app(TenantContext::class)->has()
        );
    }

    public function test_unconfigured_channel_is_skipped_without_consuming_attempt(): void
    {
        config()->set(
            'communications.transports.whatsapp',
            null
        );

        $communication = $this->createCommunication([
            'scheduled_for' => now()->subMinute(),
        ]);

        app(TenantContext::class)->clear();

        $this->artisan(
            'communications:process-due'
        )
            ->expectsOutput(
                'Comunicaciones: 1 procesadas, 0 enviadas, 0 fallidas, 1 omitidas, 0 errores.'
            )
            ->assertSuccessful();

        $communication->refresh();

        $this->assertSame(
            Communication::STATUS_PENDING,
            $communication->status
        );

        $this->assertSame(
            0,
            $communication->attempt_count
        );

        $this->assertNull(
            $communication->sent_at
        );

        $this->assertNull(
            $communication->failed_at
        );

        $this->assertNull(
            $communication->last_error
        );
    }

    public function test_cancelled_appointment_makes_due_reminder_cancelled_without_sending(): void
    {
        [$appointment, $communication] =
            $this->createAppointmentReminder();

        $appointment->update([
            'status' => Appointment::STATUS_CANCELLED,
        ]);

        app(TenantContext::class)->clear();

        $this->artisan(
            'communications:process-due'
        )
            ->expectsOutput(
                'Comunicaciones: 1 procesadas, 0 enviadas, 0 fallidas, 1 omitidas, 0 errores.'
            )
            ->assertSuccessful();

        $communication->refresh();

        $this->assertTrue(
            $communication->isCancelled()
        );

        $this->assertNotNull(
            $communication->cancelled_at
        );

        $this->assertNotNull(
            $communication->cancellation_reason
        );

        $this->assertSame(
            0,
            $communication->attempt_count
        );

        $this->assertNull(
            $communication->sent_at
        );
    }

    public function test_rescheduled_appointment_cancels_old_due_reminder(): void
    {
        [$appointment, $communication] =
            $this->createAppointmentReminder();

        $appointment->update([
            'starts_at' => $appointment->starts_at
                ->copy()
                ->addDay(),
            'ends_at' => $appointment->ends_at
                ->copy()
                ->addDay(),
        ]);

        app(TenantContext::class)->clear();

        $this->artisan(
            'communications:process-due'
        )
            ->expectsOutput(
                'Comunicaciones: 1 procesadas, 0 enviadas, 0 fallidas, 1 omitidas, 0 errores.'
            )
            ->assertSuccessful();

        $communication->refresh();

        $this->assertTrue(
            $communication->isCancelled()
        );

        $this->assertSame(
            0,
            $communication->attempt_count
        );

        $this->assertNull(
            $communication->sent_at
        );
    }

    public function test_rescheduled_appointment_cancels_old_reminder_and_processes_new_one(): void
    {
        [$appointment, $oldCommunication] =
            $this->createAppointmentReminder();

        $appointment->update([
            'starts_at' => $appointment->starts_at
                ->copy()
                ->addDay(),
            'ends_at' => $appointment->ends_at
                ->copy()
                ->addDay(),
        ]);

        $newCommunication = app(
            AppointmentReminderService::class
        )->create(
            $appointment->fresh(),
            Communication::CHANNEL_WHATSAPP
        );

        $this->assertNotNull(
            $newCommunication
        );

        $this->assertNotSame(
            $oldCommunication->id,
            $newCommunication->id
        );

        /*
        * Forzamos ambos recordatorios como procesables.
        *
        * El antiguo deberá cancelarse por haber quedado
        * desfasado respecto a la nueva fecha de la cita.
        *
        * El nuevo deberá enviarse normalmente.
        */
        $oldCommunication->update([
            'scheduled_for' => now()->subMinute(),
        ]);

        $newCommunication->update([
            'scheduled_for' => now()->subMinute(),
        ]);

        app(TenantContext::class)->clear();

        $this->artisan(
            'communications:process-due'
        )
            ->expectsOutput(
                'Comunicaciones: 2 procesadas, 1 enviadas, 0 fallidas, 1 omitidas, 0 errores.'
            )
            ->assertSuccessful();

        $oldCommunication->refresh();
        $newCommunication->refresh();

        $this->assertTrue(
            $oldCommunication->isCancelled()
        );

        $this->assertSame(
            0,
            $oldCommunication->attempt_count
        );

        $this->assertNull(
            $oldCommunication->sent_at
        );

        $this->assertSame(
            Communication::STATUS_SENT,
            $newCommunication->status
        );

        $this->assertSame(
            1,
            $newCommunication->attempt_count
        );

        $this->assertNotNull(
            $newCommunication->sent_at
        );

        $this->assertFalse(
            $newCommunication->isCancelled()
        );
    }

    private function createCommunication(
        array $attributes = []
    ): Communication {
        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        app(TenantContext::class)->set($tenant);

        return $this->createCommunicationForCurrentTenant(
            'message-' . uniqid(),
            $attributes
        );
    }

    private function createCommunicationForCurrentTenant(
        string $key,
        array $attributes = []
    ): Communication {
        $patient = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Prueba',
            'email' => 'paciente-' . uniqid() . '@example.com',
            'phone' => '9611111111',
            'whatsapp' => '9612222222',
        ]);

        return Communication::create(
            array_merge(
                [
                    'patient_id' => $patient->id,
                    'type' => Communication::TYPE_APPOINTMENT_REMINDER,
                    'channel' => Communication::CHANNEL_WHATSAPP,
                    'recipient' => $patient->whatsapp,
                    'body' => 'Recordatorio de cita.',
                    'status' => Communication::STATUS_PENDING,
                    'scheduled_for' => now()->subMinute(),
                    'idempotency_key' => $key,
                ],
                $attributes
            )
        );
    }

    private function createAppointmentReminder(): array
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Reminder',
            'slug' => 'tenant-reminder-' . uniqid(),
        ]);

        app(TenantContext::class)->set(
            $tenant
        );

        $user = User::factory()->create();

        $doctor = DoctorProfile::create([
            'user_id' => $user->id,
            'first_name' => 'Doctor',
            'last_name' => 'Prueba',
        ]);

        $patient = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Recordatorio',
            'email' => 'recordatorio-' . uniqid() . '@example.com',
            'phone' => '9611111111',
            'whatsapp' => '9612222222',
        ]);

        $startsAt = now()->addHours(12);

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt
                ->copy()
                ->addHour(),
            'status' => Appointment::STATUS_SCHEDULED,
        ]);

        $communication = app(
            AppointmentReminderService::class
        )->create(
            $appointment,
            Communication::CHANNEL_WHATSAPP
        );

        $this->assertNotNull(
            $communication
        );

        /*
        * El servicio calcula normalmente el recordatorio
        * para un día antes. Para esta prueba necesitamos
        * que ya esté vencido y sea procesable.
        */
        $communication->update([
            'scheduled_for' => now()->subMinute(),
        ]);

        return [
            $appointment,
            $communication,
        ];
    }
}

class ProcessDueSuccessfulCommunicationTransport implements CommunicationTransport
{
    public function send(
        Communication $communication
    ): void {}
}

class ProcessDueFailingCommunicationTransport implements CommunicationTransport
{
    public function send(
        Communication $communication
    ): void {
        throw new RuntimeException(
            'Provider unavailable'
        );
    }
}
