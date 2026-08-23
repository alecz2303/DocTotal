<?php

namespace Tests\Feature\Appointments;

use App\Models\Appointment;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\Specialty;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_appointment_generates_uuid_automatically(): void
    {
        [
            $tenant,,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $appointment = $this->createAppointment(
            $doctor,
            $patient
        );

        $this->assertNotNull($appointment->uuid);

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $appointment->uuid
        );
    }

    public function test_appointment_uses_uuid_as_route_key(): void
    {
        $appointment = new Appointment;

        $this->assertSame(
            'uuid',
            $appointment->getRouteKeyName()
        );
    }

    public function test_appointment_belongs_to_patient(): void
    {
        [
            $tenant,,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $appointment = $this->createAppointment(
            $doctor,
            $patient
        );

        $this->assertTrue(
            $appointment->patient->is($patient)
        );
    }

    public function test_appointment_belongs_to_doctor_profile(): void
    {
        [
            $tenant,,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $appointment = $this->createAppointment(
            $doctor,
            $patient
        );

        $this->assertTrue(
            $appointment->doctorProfile->is($doctor)
        );
    }

    public function test_patient_has_appointments(): void
    {
        [
            $tenant,,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $appointment = $this->createAppointment(
            $doctor,
            $patient
        );

        $this->assertTrue(
            $patient->appointments->contains($appointment)
        );
    }

    public function test_doctor_profile_has_appointments(): void
    {
        [
            $tenant,,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $appointment = $this->createAppointment(
            $doctor,
            $patient
        );

        $this->assertTrue(
            $doctor->appointments->contains($appointment)
        );
    }

    public function test_appointment_dates_are_cast_to_datetime(): void
    {
        [
            $tenant,,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $appointment = $this->createAppointment(
            $doctor,
            $patient
        );

        $this->assertInstanceOf(
            \Illuminate\Support\Carbon::class,
            $appointment->starts_at
        );

        $this->assertInstanceOf(
            \Illuminate\Support\Carbon::class,
            $appointment->ends_at
        );
    }

    public function test_new_appointment_has_scheduled_status_by_default(): void
    {
        [
            $tenant,,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'starts_at' => '2026-08-24 10:00:00',
            'ends_at' => '2026-08-24 10:30:00',
            'reason' => 'Consulta general',
        ]);

        $appointment->refresh();

        $this->assertSame(
            'scheduled',
            $appointment->status
        );
    }

    public function test_appointments_are_isolated_by_tenant(): void
    {
        [
            $tenantA,,
            $doctorA,
            $patientA,
        ] = $this->createContext(
            tenantName: 'Tenant A',
            tenantSlug: 'tenant-a',
            email: 'a@example.com'
        );

        app(TenantContext::class)->set($tenantA);

        $appointmentA = $this->createAppointment(
            $doctorA,
            $patientA,
            '2026-08-24 10:00:00'
        );

        [
            $tenantB,,
            $doctorB,
            $patientB,
        ] = $this->createContext(
            tenantName: 'Tenant B',
            tenantSlug: 'tenant-b',
            email: 'b@example.com'
        );

        app(TenantContext::class)->set($tenantB);

        $appointmentB = $this->createAppointment(
            $doctorB,
            $patientB,
            '2026-08-24 11:00:00'
        );

        app(TenantContext::class)->set($tenantA);

        $appointments = Appointment::query()->get();

        $this->assertTrue(
            $appointments->contains($appointmentA)
        );

        $this->assertFalse(
            $appointments->contains($appointmentB)
        );

        $this->assertCount(1, $appointments);
    }

    private function createContext(
        string $tenantName = 'Consultorio Test',
        string $tenantSlug = 'consultorio-test',
        string $email = 'doctor@example.com',
    ): array {
        $tenant = Tenant::create([
            'name' => $tenantName,
            'slug' => $tenantSlug,
            'onboarding_completed_at' => now(),
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Dr. Test',
            'email' => $email,
            'password' => 'password123',
            'role' => 'owner',
        ]);

        app(TenantContext::class)->set($tenant);

        $specialty = Specialty::firstOrCreate(
            [
                'slug' => 'medicina-general',
            ],
            [
                'name' => 'Medicina General',
            ]
        );

        $doctor = DoctorProfile::create([
            'user_id' => $user->id,
            'specialty_id' => $specialty->id,
            'first_name' => 'Doctor',
            'last_name' => 'Test',
            'professional_license' => '12345678',
        ]);

        $patient = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Prueba',
            'second_last_name' => 'Test',
            'birth_date' => '1990-01-15',
        ]);

        return [
            $tenant,
            $user,
            $doctor,
            $patient,
        ];
    }

    private function createAppointment(
        DoctorProfile $doctor,
        Patient $patient,
        string $startsAt = '2026-08-24 10:00:00',
    ): Appointment {
        $startsAt = \Illuminate\Support\Carbon::parse(
            $startsAt
        );

        return Appointment::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes(30),
            'status' => 'scheduled',
            'reason' => 'Consulta general',
        ]);
    }
}
