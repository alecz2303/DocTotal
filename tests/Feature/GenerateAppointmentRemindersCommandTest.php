<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Communication;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerateAppointmentRemindersCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_generates_reminder_for_future_appointment(): void
    {
        [,, $appointment] =
            $this->createAppointmentContext();

        app(TenantContext::class)->clear();

        $this->artisan(
            'communications:generate-appointment-reminders'
        )
            ->expectsOutput(
                'Recordatorios: 1 generados, 0 existentes, 0 omitidos, 0 errores.'
            )
            ->assertSuccessful();

        $this->assertDatabaseHas(
            'communications',
            [
                'appointment_id' => $appointment->id,
                'type' => Communication::TYPE_APPOINTMENT_REMINDER,
                'channel' => Communication::CHANNEL_WHATSAPP,
                'status' => Communication::STATUS_PENDING,
            ]
        );
    }

    public function test_running_command_twice_does_not_duplicate_reminder(): void
    {
        $this->createAppointmentContext();

        app(TenantContext::class)->clear();

        $this->artisan(
            'communications:generate-appointment-reminders'
        )->assertSuccessful();

        $this->artisan(
            'communications:generate-appointment-reminders'
        )
            ->expectsOutput(
                'Recordatorios: 0 generados, 1 existentes, 0 omitidos, 0 errores.'
            )
            ->assertSuccessful();

        $this->assertDatabaseCount(
            'communications',
            1
        );
    }

    public function test_command_processes_multiple_tenants_without_mixing_them(): void
    {
        [$tenantA,, $appointmentA] =
            $this->createAppointmentContext(
                'Tenant A',
                'tenant-a',
                'doctor-a@example.com'
            );

        [$tenantB,, $appointmentB] =
            $this->createAppointmentContext(
                'Tenant B',
                'tenant-b',
                'doctor-b@example.com'
            );

        app(TenantContext::class)->clear();

        $this->artisan(
            'communications:generate-appointment-reminders'
        )
            ->expectsOutput(
                'Recordatorios: 2 generados, 0 existentes, 0 omitidos, 0 errores.'
            )
            ->assertSuccessful();

        $this->assertDatabaseHas(
            'communications',
            [
                'tenant_id' => $tenantA->id,
                'appointment_id' => $appointmentA->id,
            ]
        );

        $this->assertDatabaseHas(
            'communications',
            [
                'tenant_id' => $tenantB->id,
                'appointment_id' => $appointmentB->id,
            ]
        );

        app(TenantContext::class)->set($tenantA);

        $this->assertSame(
            1,
            Communication::query()->count()
        );

        app(TenantContext::class)->set($tenantB);

        $this->assertSame(
            1,
            Communication::query()->count()
        );
    }

    public function test_cancelled_appointment_is_not_processed(): void
    {
        [,, $appointment] =
            $this->createAppointmentContext();

        $appointment->cancel(
            'Paciente canceló'
        );

        app(TenantContext::class)->clear();

        $this->artisan(
            'communications:generate-appointment-reminders'
        )
            ->expectsOutput(
                'Recordatorios: 0 generados, 0 existentes, 0 omitidos, 0 errores.'
            )
            ->assertSuccessful();

        $this->assertDatabaseCount(
            'communications',
            0
        );
    }

    public function test_appointment_without_whatsapp_is_skipped(): void
    {
        [, $patient] =
            $this->createAppointmentContext();

        $patient->update([
            'whatsapp' => null,
        ]);

        app(TenantContext::class)->clear();

        $this->artisan(
            'communications:generate-appointment-reminders'
        )
            ->expectsOutput(
                'Recordatorios: 0 generados, 0 existentes, 1 omitidos, 0 errores.'
            )
            ->assertSuccessful();

        $this->assertDatabaseCount(
            'communications',
            0
        );
    }

    public function test_appointment_outside_requested_window_is_not_processed(): void
    {
        [,, $appointment] =
            $this->createAppointmentContext();

        $appointment->update([
            'starts_at' => now()->addDays(10),
            'ends_at' => now()
                ->addDays(10)
                ->addMinutes(30),
        ]);

        app(TenantContext::class)->clear();

        $this->artisan(
            'communications:generate-appointment-reminders',
            [
                '--days' => 7,
            ]
        )
            ->expectsOutput(
                'Recordatorios: 0 generados, 0 existentes, 0 omitidos, 0 errores.'
            )
            ->assertSuccessful();

        $this->assertDatabaseCount(
            'communications',
            0
        );
    }

    public function test_email_channel_generates_email_reminder(): void
    {
        [, $patient, $appointment] =
            $this->createAppointmentContext();

        app(TenantContext::class)->clear();

        $this->artisan(
            'communications:generate-appointment-reminders',
            [
                '--channel' => Communication::CHANNEL_EMAIL,
            ]
        )
            ->expectsOutput(
                'Recordatorios: 1 generados, 0 existentes, 0 omitidos, 0 errores.'
            )
            ->assertSuccessful();

        $this->assertDatabaseHas(
            'communications',
            [
                'appointment_id' => $appointment->id,
                'channel' => Communication::CHANNEL_EMAIL,
                'recipient' => $patient->email,
            ]
        );
    }

    public function test_invalid_channel_returns_failure(): void
    {
        $this->createAppointmentContext();

        app(TenantContext::class)->clear();

        $this->artisan(
            'communications:generate-appointment-reminders',
            [
                '--channel' => 'carrier-pigeon',
            ]
        )
            ->expectsOutput(
                'Canal no soportado: carrier-pigeon'
            )
            ->assertFailed();

        $this->assertDatabaseCount(
            'communications',
            0
        );
    }

    public function test_tenant_context_is_cleared_after_command_finishes(): void
    {
        $this->createAppointmentContext();

        app(TenantContext::class)->clear();

        $this->artisan(
            'communications:generate-appointment-reminders'
        )->assertSuccessful();

        $this->assertFalse(
            app(TenantContext::class)->has()
        );
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
            'email' => sprintf(
                'paciente-%s@example.com',
                $tenantSlug
            ),
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
