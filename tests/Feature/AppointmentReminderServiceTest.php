<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Communication;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Communications\AppointmentReminderService;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentReminderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_pending_whatsapp_reminder_for_future_appointment(): void
    {
        [, $patient, $appointment] =
            $this->createAppointmentContext();

        $service = app(AppointmentReminderService::class);

        $communication = $service->create($appointment);

        $this->assertNotNull($communication);

        $this->assertSame(
            Communication::TYPE_APPOINTMENT_REMINDER,
            $communication->type
        );

        $this->assertSame(
            Communication::CHANNEL_WHATSAPP,
            $communication->channel
        );

        $this->assertSame(
            $patient->whatsapp,
            $communication->recipient
        );

        $this->assertSame(
            Communication::STATUS_PENDING,
            $communication->status
        );

        $this->assertSame(
            $appointment->id,
            $communication->appointment_id
        );

        $this->assertSame(
            $patient->id,
            $communication->patient_id
        );

        $this->assertNotNull(
            $communication->scheduled_for
        );

        $this->assertStringContainsString(
            '/appointment/',
            $communication->body
        );

        $appointment->refresh();

        $this->assertNotNull(
            $appointment->public_access_token_hash
        );
    }

    public function test_same_reminder_is_not_created_twice(): void
    {
        [,, $appointment] =
            $this->createAppointmentContext();

        $service = app(AppointmentReminderService::class);

        $first = $service->create($appointment);
        $second = $service->create($appointment);

        $this->assertNotNull($first);
        $this->assertNotNull($second);

        $this->assertSame(
            $first->id,
            $second->id
        );

        $this->assertSame(
            1,
            Communication::query()->count()
        );
    }

    public function test_rescheduled_appointment_can_generate_new_reminder(): void
    {
        [,, $appointment] =
            $this->createAppointmentContext();

        $service = app(AppointmentReminderService::class);

        $original = $service->create($appointment);

        $appointment->refresh();
        $originalTokenHash = $appointment->public_access_token_hash;

        $appointment->reschedule(
            now()->addDays(4),
            now()->addDays(4)->addMinutes(30),
        );

        $appointment->refresh();

        $rescheduled = $service->create($appointment);

        $this->assertNotNull($original);
        $this->assertNotNull($rescheduled);

        $this->assertNotSame(
            $original->id,
            $rescheduled->id
        );

        $this->assertNotSame(
            $original->idempotency_key,
            $rescheduled->idempotency_key
        );

        $this->assertSame(
            2,
            Communication::query()->count()
        );

        $appointment->refresh();

        $this->assertNotNull($appointment->public_access_token_hash);
        $this->assertNotSame(
            $originalTokenHash,
            $appointment->public_access_token_hash
        );
    }

    public function test_cancelled_appointment_does_not_generate_reminder(): void
    {
        [,, $appointment] =
            $this->createAppointmentContext();

        $appointment->cancel('Paciente canceló');

        $service = app(AppointmentReminderService::class);

        $communication = $service->create($appointment);

        $this->assertNull($communication);

        $this->assertSame(
            0,
            Communication::query()->count()
        );
    }

    public function test_appointment_without_whatsapp_does_not_generate_whatsapp_reminder(): void
    {
        [, $patient, $appointment] =
            $this->createAppointmentContext();

        $patient->update([
            'whatsapp' => null,
        ]);

        $service = app(AppointmentReminderService::class);

        $communication = $service->create($appointment);

        $this->assertNull($communication);

        $this->assertSame(
            0,
            Communication::query()->count()
        );
    }

    public function test_email_channel_uses_patient_email(): void
    {
        [, $patient, $appointment] =
            $this->createAppointmentContext();

        $service = app(AppointmentReminderService::class);

        $communication = $service->create(
            $appointment,
            Communication::CHANNEL_EMAIL
        );

        $this->assertNotNull($communication);

        $this->assertSame(
            Communication::CHANNEL_EMAIL,
            $communication->channel
        );

        $this->assertSame(
            $patient->email,
            $communication->recipient
        );

        $this->assertSame(
            'Recordatorio de cita',
            $communication->subject
        );
    }

    private function createAppointmentContext(): array
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

        $user = User::factory()->create([
            'email' => 'doctor@example.com',
        ]);

        $doctor = DoctorProfile::create([
            'user_id' => $user->id,
            'first_name' => 'Doctor',
            'last_name' => 'Prueba',
        ]);

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'starts_at' => now()->addDays(2),
            'ends_at' => now()
                ->addDays(2)
                ->addMinutes(30),
            'status' => Appointment::STATUS_SCHEDULED,
        ]);

        return [
            $tenant,
            $patient,
            $appointment,
        ];
    }
}
