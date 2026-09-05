<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AuditEvent;
use App\Models\Communication;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\Schedule;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Communications\AppointmentReminderService;
use App\Services\Communications\AppointmentReminderValidator;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PublicAppointmentRescheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_link_exposes_available_reschedule_slots(): void
    {
        $this->travelTo(Carbon::parse('2026-09-07 08:00:00'));
        [, , $doctor, , $token] = $this->context();
        $this->schedule($doctor, 2, '09:00', '12:00');
        app(TenantContext::class)->clear();

        $this->get(route('public.appointments.show', ['token' => $token]))
            ->assertOk()->assertSee('Reprogramar mi cita');

        $this->get(route('public.appointments.show', ['token' => $token, 'reschedule' => 1, 'date' => '2026-09-08']))
            ->assertOk()->assertSee('Horario actual')->assertSee('09:00')->assertSee('09:30');
    }

    public function test_patient_can_reschedule_and_old_token_is_revoked(): void
    {
        $this->travelTo(Carbon::parse('2026-09-07 08:00:00'));
        [, , $doctor, $appointment, $token] = $this->context();
        $this->schedule($doctor, 2, '09:00', '12:00');
        app(TenantContext::class)->clear();

        $this->post(route('public.appointments.cancel', ['token' => $token]), [
            '_action' => 'reschedule', 'date' => '2026-09-08', 'time' => '10:00',
        ])->assertRedirect();

        $appointment->refresh();
        $this->assertSame('2026-09-08 10:00:00', $appointment->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-08 10:30:00', $appointment->ends_at->format('Y-m-d H:i:s'));
        $this->assertSame(Appointment::STATUS_SCHEDULED, $appointment->status);
        $this->assertNotSame(hash('sha256', $token), $appointment->public_access_token_hash);

        app(TenantContext::class)->clear();
        $this->get(route('public.appointments.show', ['token' => $token]))->assertNotFound();
    }

    public function test_confirmed_appointment_returns_to_scheduled_after_reschedule(): void
    {
        $this->travelTo(Carbon::parse('2026-09-07 08:00:00'));
        [, , $doctor, $appointment, $token] = $this->context();
        $this->schedule($doctor, 2, '09:00', '12:00');
        $appointment->confirm();
        app(TenantContext::class)->clear();

        $this->post(route('public.appointments.cancel', ['token' => $token]), [
            '_action' => 'reschedule', 'date' => '2026-09-08', 'time' => '10:30',
        ])->assertRedirect();

        $appointment->refresh();
        $this->assertSame(Appointment::STATUS_SCHEDULED, $appointment->status);
        $this->assertNull($appointment->confirmed_at);
    }

    public function test_occupied_slot_is_rejected(): void
    {
        $this->travelTo(Carbon::parse('2026-09-07 08:00:00'));
        [$tenant, $patient, $doctor, $appointment, $token] = $this->context();
        $this->schedule($doctor, 2, '09:00', '12:00');
        Appointment::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'starts_at' => '2026-09-08 10:00:00',
            'ends_at' => '2026-09-08 10:30:00',
            'status' => Appointment::STATUS_SCHEDULED,
        ]);
        app(TenantContext::class)->clear();

        $this->post(route('public.appointments.cancel', ['token' => $token]), [
            '_action' => 'reschedule', 'date' => '2026-09-08', 'time' => '10:00',
        ])->assertSessionHasErrors('time');

        app(TenantContext::class)->set($tenant);
        $appointment->refresh();
        $this->assertNotSame('2026-09-08 10:00:00', $appointment->starts_at->format('Y-m-d H:i:s'));
    }

    public function test_public_reschedule_rejects_past_or_out_of_schedule_slots(): void
    {
        $this->travelTo(Carbon::parse('2026-09-07 08:00:00'));
        [, , $doctor, $appointment, $token] = $this->context();
        $this->schedule($doctor, 2, '09:00', '12:00');
        app(TenantContext::class)->clear();

        $this->post(route('public.appointments.cancel', ['token' => $token]), [
            '_action' => 'reschedule', 'date' => '2026-09-07', 'time' => '08:00',
        ])->assertSessionHasErrors('time');

        $this->post(route('public.appointments.cancel', ['token' => $token]), [
            '_action' => 'reschedule', 'date' => '2026-09-08', 'time' => '15:00',
        ])->assertSessionHasErrors('time');

        $appointment->refresh();
        $this->assertSame('2026-09-08 09:00:00', $appointment->starts_at->format('Y-m-d H:i:s'));
    }

    public function test_clinical_and_terminal_statuses_cannot_be_rescheduled_publicly(): void
    {
        $this->travelTo(Carbon::parse('2026-09-07 08:00:00'));
        [, , $doctor, $appointment, $token] = $this->context();
        $this->schedule($doctor, 2, '09:00', '12:00');
        app(TenantContext::class)->clear();

        foreach ([
            Appointment::STATUS_CHECKED_IN,
            Appointment::STATUS_IN_PROGRESS,
            Appointment::STATUS_COMPLETED,
            Appointment::STATUS_CANCELLED,
            Appointment::STATUS_NO_SHOW,
        ] as $status) {
            $appointment->update(['status' => $status]);

            $this->post(route('public.appointments.cancel', ['token' => $token]), [
                '_action' => 'reschedule', 'date' => '2026-09-08', 'time' => '10:00',
            ])->assertStatus(409);
        }
    }

    public function test_browser_parameters_cannot_switch_patient_doctor_or_tenant(): void
    {
        $this->travelTo(Carbon::parse('2026-09-07 08:00:00'));
        [$tenant, $patient, $doctor, $appointment, $token] = $this->context();
        $this->schedule($doctor, 2, '09:00', '12:00');

        $otherTenant = Tenant::create(['name' => 'Tenant B', 'slug' => 'tenant-b']);
        app(TenantContext::class)->set($otherTenant);
        $otherPatient = Patient::create([
            'first_name' => 'Otro', 'last_name' => 'Paciente', 'email' => 'otro@example.com',
            'phone' => '9613333333', 'whatsapp' => '9614444444',
        ]);
        $otherUser = User::factory()->create(['email' => 'otro-doctor@example.com']);
        $otherDoctor = DoctorProfile::create([
            'user_id' => $otherUser->id, 'first_name' => 'Otro', 'last_name' => 'Doctor',
        ]);

        app(TenantContext::class)->clear();

        $this->post(route('public.appointments.cancel', ['token' => $token]), [
            '_action' => 'reschedule',
            'date' => '2026-09-08',
            'time' => '10:00',
            'patient_id' => $otherPatient->id,
            'doctor_profile_id' => $otherDoctor->id,
            'tenant_id' => $otherTenant->id,
        ])->assertRedirect();

        app(TenantContext::class)->set($tenant);
        $appointment->refresh();

        $this->assertSame($patient->id, $appointment->patient_id);
        $this->assertSame($doctor->id, $appointment->doctor_profile_id);
        $this->assertSame($tenant->id, $appointment->tenant_id);
    }

    public function test_old_reminder_becomes_stale_and_new_schedule_can_generate_new_reminder(): void
    {
        $this->travelTo(Carbon::parse('2026-09-07 08:00:00'));
        [$tenant, , $doctor, $appointment] = $this->context();
        $this->schedule($doctor, 2, '09:00', '12:00');

        $reminderService = app(AppointmentReminderService::class);
        $validator = app(AppointmentReminderValidator::class);
        $oldReminder = $reminderService->create($appointment);
        $this->assertNotNull($oldReminder);
        $this->assertTrue($validator->isCurrent($oldReminder));

        $token = $appointment->issuePublicAccessToken();
        app(TenantContext::class)->clear();

        $this->post(route('public.appointments.cancel', ['token' => $token]), [
            '_action' => 'reschedule', 'date' => '2026-09-08', 'time' => '11:00',
        ])->assertRedirect();

        app(TenantContext::class)->set($tenant);
        $appointment->refresh();
        $oldReminder->refresh();

        $this->assertFalse($validator->isCurrent($oldReminder));

        $newReminder = $reminderService->create($appointment);
        $this->assertNotNull($newReminder);
        $this->assertNotSame($oldReminder->id, $newReminder->id);
        $this->assertNotSame($oldReminder->idempotency_key, $newReminder->idempotency_key);
        $this->assertSame(
            $appointment->starts_at->toIso8601String(),
            data_get($newReminder->metadata, 'appointment_starts_at')
        );
    }

    public function test_public_reschedule_is_audited_without_token(): void
    {
        $this->travelTo(Carbon::parse('2026-09-07 08:00:00'));
        [$tenant, , $doctor, , $token] = $this->context();
        $this->schedule($doctor, 2, '09:00', '12:00');
        app(TenantContext::class)->clear();

        $this->post(route('public.appointments.cancel', ['token' => $token]), [
            '_action' => 'reschedule', 'date' => '2026-09-08', 'time' => '11:00',
        ])->assertRedirect();

        app(TenantContext::class)->set($tenant);
        $event = AuditEvent::query()->where('action', 'appointment.public_rescheduled')->firstOrFail();
        $this->assertSame('patient_self_service', $event->metadata['source']);
        $this->assertStringNotContainsString($token, json_encode($event->metadata) ?: '');
    }

    private function context(): array
    {
        $tenant = Tenant::create(['name' => 'Tenant A', 'slug' => 'tenant-a']);
        app(TenantContext::class)->set($tenant);
        $patient = Patient::create([
            'first_name' => 'Paciente', 'last_name' => 'Prueba', 'email' => 'paciente@example.com',
            'phone' => '9611111111', 'whatsapp' => '9612222222',
        ]);
        $user = User::factory()->create(['email' => 'doctor@example.com']);
        $doctor = DoctorProfile::create(['user_id' => $user->id, 'first_name' => 'Doctor', 'last_name' => 'Prueba']);
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'starts_at' => '2026-09-08 09:00:00',
            'ends_at' => '2026-09-08 09:30:00',
            'status' => Appointment::STATUS_SCHEDULED,
        ]);
        $token = $appointment->issuePublicAccessToken();

        return [$tenant, $patient, $doctor, $appointment, $token];
    }

    private function schedule(DoctorProfile $doctor, int $day, string $start, string $end): Schedule
    {
        return Schedule::create([
            'doctor_profile_id' => $doctor->id,
            'day_of_week' => $day,
            'start_time' => $start,
            'end_time' => $end,
            'appointment_duration' => 30,
            'buffer_before' => 0,
            'buffer_after' => 0,
            'active' => true,
        ]);
    }
}
