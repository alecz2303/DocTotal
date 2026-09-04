<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Communications\AppointmentPublicLinkService;
use App\Support\TenantContext;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentPublicLinkServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_issues_a_public_management_link_for_a_future_appointment(): void
    {
        $appointment = $this->createAppointment();

        $result = app(AppointmentPublicLinkService::class)
            ->issue($appointment);

        $this->assertStringContainsString(
            '/a/',
            $result['url']
        );

        $this->assertStringContainsString(
            $result['url'],
            $result['message']
        );

        $this->assertStringContainsString(
            'Dr. Doctor Prueba',
            $result['message']
        );

        $this->assertStringContainsString(
            'Tenant A',
            $result['message']
        );

        $this->assertStringContainsString(
            'hrs.',
            $result['message']
        );

        $this->assertStringContainsString(
            'Fecha:',
            $result['message']
        );

        $this->assertStringContainsString(
            'Hora:',
            $result['message']
        );

        $this->assertStringNotContainsString(
            '\u{FFFD}',
            $result['message']
        );

        $this->assertSame(
            'Recordatorio de cita - Tenant A',
            $result['subject']
        );

        $appointment->refresh();

        $this->assertNotNull(
            $appointment->public_access_token_hash
        );

        $this->assertNotNull(
            $appointment->public_access_token_generated_at
        );

        $this->assertStringNotContainsString(
            $appointment->public_access_token_hash,
            $result['url']
        );
    }

    public function test_generating_a_new_link_invalidates_the_previous_token(): void
    {
        $appointment = $this->createAppointment();
        $service = app(AppointmentPublicLinkService::class);

        $first = $service->issue($appointment);

        $appointment->refresh();
        $firstHash = $appointment->public_access_token_hash;

        $second = $service->issue($appointment);

        $appointment->refresh();

        $this->assertNotSame(
            $first['url'],
            $second['url']
        );

        $this->assertNotSame(
            $firstHash,
            $appointment->public_access_token_hash
        );
    }

    public function test_terminal_appointment_cannot_issue_a_public_management_link(): void
    {
        $appointment = $this->createAppointment();

        $appointment->update([
            'status' => Appointment::STATUS_COMPLETED,
        ]);

        $this->expectException(DomainException::class);

        app(AppointmentPublicLinkService::class)
            ->issue($appointment->fresh());
    }

    private function createAppointment(): Appointment
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
            'whatsapp' => '529612222222',
        ]);

        $user = User::factory()->create([
            'email' => 'doctor@example.com',
        ]);

        $doctor = DoctorProfile::create([
            'user_id' => $user->id,
            'first_name' => 'Doctor',
            'last_name' => 'Prueba',
        ]);

        return Appointment::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(2)->addMinutes(30),
            'status' => Appointment::STATUS_SCHEDULED,
        ]);
    }
}
