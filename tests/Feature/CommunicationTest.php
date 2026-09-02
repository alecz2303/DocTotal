<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Communication;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_communication_belongs_to_current_tenant_patient_and_appointment(): void
    {
        [$tenant, $patient, $appointment] = $this->createAppointmentContext();

        $communication = Communication::create([
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'type' => Communication::TYPE_APPOINTMENT_REMINDER,
            'channel' => Communication::CHANNEL_WHATSAPP,
            'recipient' => '9610000000',
            'body' => 'Recordatorio de cita.',
            'idempotency_key' => 'appointment-reminder-' . $appointment->id,
        ]);

        $this->assertSame(
            $tenant->id,
            $communication->tenant_id
        );

        $this->assertTrue(
            $communication->patient->is($patient)
        );

        $this->assertTrue(
            $communication->appointment->is($appointment)
        );

        $this->assertSame(
            Communication::STATUS_PENDING,
            $communication->status
        );

        $this->assertTrue($communication->isPending());
        $this->assertFalse($communication->isSent());
        $this->assertFalse($communication->isFailed());
    }

    public function test_patient_and_appointment_have_communications(): void
    {
        [, $patient, $appointment] = $this->createAppointmentContext();

        Communication::create([
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'type' => Communication::TYPE_APPOINTMENT_CONFIRMATION,
            'channel' => Communication::CHANNEL_EMAIL,
            'recipient' => 'paciente@example.com',
            'subject' => 'Confirmación de cita',
            'body' => 'Su cita ha sido registrada.',
            'idempotency_key' => 'confirmation-' . $appointment->id,
        ]);

        Communication::create([
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'type' => Communication::TYPE_APPOINTMENT_REMINDER,
            'channel' => Communication::CHANNEL_WHATSAPP,
            'recipient' => '9610000000',
            'body' => 'Recordatorio de cita.',
            'idempotency_key' => 'reminder-' . $appointment->id,
        ]);

        $this->assertCount(
            2,
            $patient->communications
        );

        $this->assertCount(
            2,
            $appointment->communications
        );
    }

    public function test_communication_can_be_marked_as_sent(): void
    {
        [, $patient, $appointment] = $this->createAppointmentContext();

        $communication = $this->createCommunication(
            $patient,
            $appointment
        );

        $communication->markSent();
        $communication->refresh();

        $this->assertSame(
            Communication::STATUS_SENT,
            $communication->status
        );

        $this->assertNotNull($communication->sent_at);
        $this->assertNull($communication->failed_at);
        $this->assertNull($communication->last_error);

        $this->assertTrue($communication->isSent());
        $this->assertFalse($communication->isPending());
        $this->assertFalse($communication->isFailed());
    }

    public function test_communication_can_be_marked_as_failed(): void
    {
        [, $patient, $appointment] = $this->createAppointmentContext();

        $communication = $this->createCommunication(
            $patient,
            $appointment
        );

        $communication->markFailed('Provider unavailable');
        $communication->refresh();

        $this->assertSame(
            Communication::STATUS_FAILED,
            $communication->status
        );

        $this->assertNotNull($communication->failed_at);

        $this->assertSame(
            'Provider unavailable',
            $communication->last_error
        );

        $this->assertTrue($communication->isFailed());
        $this->assertFalse($communication->isPending());
        $this->assertFalse($communication->isSent());
    }

    public function test_communication_attempts_can_be_registered(): void
    {
        [, $patient, $appointment] = $this->createAppointmentContext();

        $communication = $this->createCommunication(
            $patient,
            $appointment
        );

        $this->assertSame(
            0,
            $communication->attempt_count
        );

        $communication->registerAttempt();
        $communication->registerAttempt();

        $communication->refresh();

        $this->assertSame(
            2,
            $communication->attempt_count
        );
    }

    public function test_idempotency_key_must_be_unique_inside_same_tenant(): void
    {
        [, $patient, $appointment] = $this->createAppointmentContext();

        $key = 'appointment-reminder-' . $appointment->id;

        $this->createCommunication(
            $patient,
            $appointment,
            $key
        );

        $this->expectException(QueryException::class);

        $this->createCommunication(
            $patient,
            $appointment,
            $key
        );
    }

    public function test_same_idempotency_key_can_exist_in_different_tenants(): void
    {
        [, $patientA, $appointmentA] =
            $this->createAppointmentContext(
                'Tenant A',
                'tenant-a',
                'doctor-a@example.com'
            );

        $key = 'shared-reminder-key';

        $communicationA = $this->createCommunication(
            $patientA,
            $appointmentA,
            $key
        );

        [$tenantB, $patientB, $appointmentB] =
            $this->createAppointmentContext(
                'Tenant B',
                'tenant-b',
                'doctor-b@example.com'
            );

        $communicationB = $this->createCommunication(
            $patientB,
            $appointmentB,
            $key
        );

        $this->assertNotSame(
            $communicationA->tenant_id,
            $communicationB->tenant_id
        );

        $this->assertSame(
            $tenantB->id,
            $communicationB->tenant_id
        );
    }

    public function test_communications_are_isolated_between_tenants(): void
    {
        [$tenantA, $patientA, $appointmentA] =
            $this->createAppointmentContext(
                'Tenant A',
                'tenant-a',
                'doctor-a@example.com'
            );

        $this->createCommunication(
            $patientA,
            $appointmentA,
            'tenant-a-reminder'
        );

        [, $patientB, $appointmentB] =
            $this->createAppointmentContext(
                'Tenant B',
                'tenant-b',
                'doctor-b@example.com'
            );

        $this->createCommunication(
            $patientB,
            $appointmentB,
            'tenant-b-reminder'
        );

        app(TenantContext::class)->set($tenantA);

        $communications = Communication::query()->get();

        $this->assertCount(
            1,
            $communications
        );

        $this->assertSame(
            'tenant-a-reminder',
            $communications->first()->idempotency_key
        );

        $this->assertSame(
            $tenantA->id,
            $communications->first()->tenant_id
        );
    }

    public function test_communication_cannot_be_created_without_tenant_context(): void
    {
        [, $patient, $appointment] = $this->createAppointmentContext();

        app(TenantContext::class)->clear();

        $this->expectException(\RuntimeException::class);

        $this->expectExceptionMessage(
            'No tenant has been resolved for the current request.'
        );

        Communication::create([
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'type' => Communication::TYPE_APPOINTMENT_REMINDER,
            'channel' => Communication::CHANNEL_WHATSAPP,
            'recipient' => '9610000000',
            'body' => 'Recordatorio de cita.',
            'idempotency_key' => 'without-tenant',
        ]);
    }

    private function createCommunication(
        Patient $patient,
        Appointment $appointment,
        ?string $idempotencyKey = null,
    ): Communication {
        return Communication::create([
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'type' => Communication::TYPE_APPOINTMENT_REMINDER,
            'channel' => Communication::CHANNEL_WHATSAPP,
            'recipient' => $patient->whatsapp ?: '9610000000',
            'body' => 'Recordatorio de cita.',
            'idempotency_key' => $idempotencyKey
                ?: 'reminder-' . $appointment->id . '-' . uniqid(),
        ]);
    }

    private function createAppointmentContext(
        string $tenantName = 'Tenant A',
        string $tenantSlug = 'tenant-a',
        string $doctorEmail = 'doctor@example.com',
    ): array {
        $tenant = Tenant::create([
            'name' => $tenantName,
            'slug' => $tenantSlug,
        ]);

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => $tenantName,
            'email' => 'paciente@example.com',
            'phone' => '9611111111',
            'whatsapp' => '9612222222',
        ]);

        $user = User::factory()->create([
            'email' => $doctorEmail,
        ]);

        $doctor = DoctorProfile::create([
            'user_id' => $user->id,
            'first_name' => 'Doctor',
            'last_name' => 'Prueba',
        ]);

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addMinutes(30),
            'status' => Appointment::STATUS_SCHEDULED,
        ]);

        return [
            $tenant,
            $patient,
            $appointment,
        ];
    }
}
