<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AuditEvent;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicAppointmentSelfServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_open_valid_public_appointment_link_without_login(): void
    {
        [, , $appointment, $token] = $this->context();

        app(TenantContext::class)->clear();

        $this->get(route('public.appointments.show', ['token' => $token]))
            ->assertOk()
            ->assertSee('Gestiona tu cita')
            ->assertSee($appointment->starts_at->format('d/m/Y H:i'))
            ->assertDontSee('Historia clínica');
    }

    public function test_invalid_token_returns_404(): void
    {
        $this->context();
        app(TenantContext::class)->clear();

        $this->get(route('public.appointments.show', [
            'token' => str_repeat('x', 64),
        ]))->assertNotFound();
    }

    public function test_patient_can_confirm_scheduled_appointment_and_confirmation_is_idempotent(): void
    {
        [, , $appointment, $token] = $this->context();
        app(TenantContext::class)->clear();

        $route = route('public.appointments.confirm', ['token' => $token]);

        $this->post($route)->assertRedirect();
        $this->post($route)->assertRedirect();

        $appointment->refresh();

        $this->assertSame(Appointment::STATUS_CONFIRMED, $appointment->status);
        $this->assertNotNull($appointment->confirmed_at);
    }

    public function test_patient_can_cancel_confirmed_appointment(): void
    {
        [, , $appointment, $token] = $this->context();
        $appointment->confirm();
        app(TenantContext::class)->clear();

        $this->post(
            route('public.appointments.cancel', ['token' => $token]),
            ['cancellation_reason' => 'No podré asistir']
        )->assertRedirect();

        $appointment->refresh();

        $this->assertSame(Appointment::STATUS_CANCELLED, $appointment->status);
        $this->assertNotNull($appointment->cancelled_at);
        $this->assertSame('No podré asistir', $appointment->cancellation_reason);
    }

    public function test_terminal_or_clinical_status_cannot_be_confirmed_publicly(): void
    {
        [, , $appointment, $token] = $this->context();
        $appointment->update([
            'status' => Appointment::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
        app(TenantContext::class)->clear();

        $this->post(
            route('public.appointments.confirm', ['token' => $token])
        )->assertStatus(409);
    }

    public function test_reschedule_invalidates_previous_public_token(): void
    {
        [, , $appointment, $token] = $this->context();

        $appointment->reschedule(
            now()->addDays(4),
            now()->addDays(4)->addMinutes(30),
        );

        app(TenantContext::class)->clear();

        $this->get(
            route('public.appointments.show', ['token' => $token])
        )->assertNotFound();
    }

    public function test_public_action_is_audited_without_token(): void
    {
        [$tenant, , $appointment, $token] = $this->context();
        app(TenantContext::class)->clear();

        $this->post(
            route('public.appointments.confirm', ['token' => $token])
        )->assertRedirect();

        app(TenantContext::class)->set($tenant);

        $event = AuditEvent::query()
            ->where('action', 'appointment.public_confirmed')
            ->firstOrFail();

        $encoded = json_encode($event->metadata);

        $this->assertStringNotContainsString($token, $encoded ?: '');
        $this->assertSame('patient_self_service', $event->metadata['source']);
    }

    private function context(): array
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
            'ends_at' => now()->addDays(2)->addMinutes(30),
            'status' => Appointment::STATUS_SCHEDULED,
        ]);

        $token = $appointment->issuePublicAccessToken();

        return [$tenant, $patient, $appointment, $token];
    }
}
