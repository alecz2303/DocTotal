<?php

namespace Tests\Feature\Consultations;

use App\Models\Consultation;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsultationModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_consultation_generates_uuid_and_belongs_to_tenant_patient_and_doctor(): void
    {
        [$tenant, $user, $doctor, $patient] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $consultation = Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now(),
            'reason' => 'Dolor de cabeza',
            'status' => 'completed',
        ]);

        $this->assertNotNull($consultation->uuid);
        $this->assertSame($tenant->id, $consultation->tenant_id);
        $this->assertTrue($consultation->patient->is($patient));
        $this->assertTrue($consultation->doctorProfile->is($doctor));
    }

    public function test_consultations_are_isolated_between_tenants(): void
    {
        [$tenantA,, $doctorA, $patientA] = $this->createContext(
            'Tenant A',
            'tenant-a',
            'a@example.com'
        );

        [$tenantB,, $doctorB, $patientB] = $this->createContext(
            'Tenant B',
            'tenant-b',
            'b@example.com'
        );

        app(TenantContext::class)->set($tenantA);

        Consultation::create([
            'patient_id' => $patientA->id,
            'doctor_profile_id' => $doctorA->id,
            'consultation_at' => now(),
            'reason' => 'Consulta A',
        ]);

        app(TenantContext::class)->set($tenantB);

        Consultation::create([
            'patient_id' => $patientB->id,
            'doctor_profile_id' => $doctorB->id,
            'consultation_at' => now(),
            'reason' => 'Consulta B',
        ]);

        app(TenantContext::class)->set($tenantA);

        $consultations = Consultation::all();

        $this->assertCount(1, $consultations);
        $this->assertSame(
            'Consulta A',
            $consultations->first()->reason
        );
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

        $doctor = DoctorProfile::create([
            'user_id' => $user->id,
            'first_name' => 'Doctor',
            'last_name' => 'Test',
        ]);

        $patient = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Test',
        ]);

        return [
            $tenant,
            $user,
            $doctor,
            $patient,
        ];
    }
}
