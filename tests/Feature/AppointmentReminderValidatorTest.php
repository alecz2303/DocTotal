<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Communication;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Communications\AppointmentReminderValidator;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentReminderValidatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_scheduled_reminder_is_valid(): void
    {
        [$appointment, $communication] =
            $this->createAppointmentAndReminder();

        $validator = app(
            AppointmentReminderValidator::class
        );

        $this->assertTrue(
            $validator->isCurrent($communication)
        );
    }

    public function test_confirmed_appointment_reminder_is_valid(): void
    {
        [$appointment, $communication] =
            $this->createAppointmentAndReminder();

        $appointment->confirm();

        $validator = app(
            AppointmentReminderValidator::class
        );

        $this->assertTrue(
            $validator->isCurrent($communication)
        );
    }

    public function test_cancelled_appointment_reminder_is_stale(): void
    {
        [$appointment, $communication] =
            $this->createAppointmentAndReminder();

        $appointment->cancel(
            'Paciente canceló la cita.'
        );

        $validator = app(
            AppointmentReminderValidator::class
        );

        $this->assertFalse(
            $validator->isCurrent($communication)
        );
    }

    public function test_rescheduled_appointment_makes_old_reminder_stale(): void
    {
        [$appointment, $communication] =
            $this->createAppointmentAndReminder();

        $appointment->reschedule(
            $appointment->starts_at
                ->copy()
                ->addDay(),
            $appointment->ends_at
                ->copy()
                ->addDay()
        );

        $validator = app(
            AppointmentReminderValidator::class
        );

        $this->assertFalse(
            $validator->isCurrent($communication)
        );
    }

    public function test_completed_appointment_reminder_is_stale(): void
    {
        [$appointment, $communication] =
            $this->createAppointmentAndReminder();

        $appointment->update([
            'status' => Appointment::STATUS_COMPLETED,
        ]);

        $validator = app(
            AppointmentReminderValidator::class
        );

        $this->assertFalse(
            $validator->isCurrent($communication)
        );
    }

    public function test_no_show_appointment_reminder_is_stale(): void
    {
        [$appointment, $communication] =
            $this->createAppointmentAndReminder();

        $appointment->update([
            'status' => Appointment::STATUS_NO_SHOW,
        ]);

        $validator = app(
            AppointmentReminderValidator::class
        );

        $this->assertFalse(
            $validator->isCurrent($communication)
        );
    }

    public function test_reminder_without_appointment_is_stale(): void
    {
        [, $communication] =
            $this->createAppointmentAndReminder();

        $communication->update([
            'appointment_id' => null,
        ]);

        $communication->unsetRelation(
            'appointment'
        );

        $validator = app(
            AppointmentReminderValidator::class
        );

        $this->assertFalse(
            $validator->isCurrent($communication)
        );
    }

    public function test_reminder_without_original_schedule_metadata_is_stale(): void
    {
        [, $communication] =
            $this->createAppointmentAndReminder();

        $communication->update([
            'metadata' => [],
        ]);

        $validator = app(
            AppointmentReminderValidator::class
        );

        $this->assertFalse(
            $validator->isCurrent($communication)
        );
    }

    public function test_non_appointment_communication_is_not_rejected(): void
    {
        [, $communication] =
            $this->createAppointmentAndReminder();

        $communication->update([
            'type' => Communication::TYPE_APPOINTMENT_CONFIRMATION,
        ]);

        $validator = app(
            AppointmentReminderValidator::class
        );

        $this->assertTrue(
            $validator->isCurrent($communication)
        );
    }

    private function createAppointmentAndReminder(): array
    {
        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
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
            'last_name' => 'Prueba',
            'email' => 'paciente@example.com',
            'phone' => '9611111111',
            'whatsapp' => '9612222222',
        ]);

        $startsAt = now()->addDays(2);

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt
                ->copy()
                ->addHour(),
            'status' => Appointment::STATUS_SCHEDULED,
        ]);

        $communication = Communication::create([
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'type' => Communication::TYPE_APPOINTMENT_REMINDER,
            'channel' => Communication::CHANNEL_WHATSAPP,
            'recipient' => $patient->whatsapp,
            'body' => 'Recordatorio de cita.',
            'status' => Communication::STATUS_PENDING,
            'scheduled_for' => now(),
            'idempotency_key' => 'reminder-' . uniqid(),
            'metadata' => [
                'appointment_uuid' => $appointment->uuid,
                'appointment_starts_at' =>
                $startsAt->toIso8601String(),
            ],
        ]);

        return [
            $appointment,
            $communication,
        ];
    }
}
