<?php

namespace Tests\Feature\Consultations;

use App\Models\Consultation;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ConsultationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_consultation_for_patient(): void
    {
        [$tenant, $user, $doctor, $patient] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $component = Livewire::actingAs($user)
            ->test('pages::consultations.create', [
                'uuid' => $patient->uuid,
            ])
            ->set('consultation_at', '2026-08-22T23:49')
            ->set('reason', 'Dolor de cabeza y mareo')
            ->set('subjective', 'Paciente refiere cefalea.')
            ->set('objective', 'Paciente estable.')
            ->set('assessment', 'Cefalea en estudio.')
            ->set('plan', 'Vigilancia y seguimiento.')
            ->set('weight_kg', '78.5')
            ->set('height_cm', '175')
            ->set('systolic_bp', '125')
            ->set('diastolic_bp', '80')
            ->set('heart_rate', '76')
            ->set('respiratory_rate', '18')
            ->set('temperature_c', '36.7')
            ->set('oxygen_saturation', '98')
            ->call('saveConsultation');

        $consultation = Consultation::query()
            ->where('patient_id', $patient->id)
            ->latest('id')
            ->firstOrFail();

        $component->assertRedirect(
            route('consultations.show', [
                'uuid' => $consultation->uuid,
            ])
        );

        $this->assertSame($tenant->id, $consultation->tenant_id);
        $this->assertSame($patient->id, $consultation->patient_id);
        $this->assertSame($doctor->id, $consultation->doctor_profile_id);
        $this->assertNotNull($consultation->uuid);

        $this->assertSame(
            'Dolor de cabeza y mareo',
            $consultation->reason
        );

        $this->assertSame(
            'Paciente refiere cefalea.',
            $consultation->subjective
        );

        $this->assertSame(
            'Paciente estable.',
            $consultation->objective
        );

        $this->assertSame(
            'Cefalea en estudio.',
            $consultation->assessment
        );

        $this->assertSame(
            'Vigilancia y seguimiento.',
            $consultation->plan
        );

        $this->assertSame('78.50', $consultation->weight_kg);
        $this->assertSame('175.00', $consultation->height_cm);
        $this->assertSame(125, $consultation->systolic_bp);
        $this->assertSame(80, $consultation->diastolic_bp);
        $this->assertSame(76, $consultation->heart_rate);
        $this->assertSame(18, $consultation->respiratory_rate);
        $this->assertSame('36.7', $consultation->temperature_c);
        $this->assertSame(98, $consultation->oxygen_saturation);
        $this->assertSame('completed', $consultation->status);
    }

    public function test_consultation_requires_date(): void
    {
        [$tenant, $user,, $patient] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::consultations.create', [
                'uuid' => $patient->uuid,
            ])
            ->set('consultation_at', '')
            ->call('saveConsultation')
            ->assertHasErrors([
                'consultation_at',
            ]);

        $this->assertDatabaseCount('consultations', 0);
    }

    public function test_consultation_rejects_invalid_vital_signs(): void
    {
        [$tenant, $user,, $patient] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::consultations.create', [
                'uuid' => $patient->uuid,
            ])
            ->set('consultation_at', now()->format('Y-m-d\TH:i'))
            ->set('systolic_bp', 500)
            ->set('diastolic_bp', 300)
            ->set('heart_rate', 500)
            ->set('respiratory_rate', 200)
            ->set('temperature_c', 60)
            ->set('oxygen_saturation', 150)
            ->call('saveConsultation')
            ->assertHasErrors([
                'systolic_bp',
                'diastolic_bp',
                'heart_rate',
                'respiratory_rate',
                'temperature_c',
                'oxygen_saturation',
            ]);

        $this->assertDatabaseCount('consultations', 0);
    }

    public function test_consultation_is_associated_with_authenticated_doctor(): void
    {
        [$tenant, $user, $doctor, $patient] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::consultations.create', [
                'uuid' => $patient->uuid,
            ])
            ->set('consultation_at', now()->format('Y-m-d\TH:i'))
            ->call('saveConsultation');

        $consultation = Consultation::firstOrFail();

        $this->assertSame(
            $doctor->id,
            $consultation->doctor_profile_id
        );

        $this->assertTrue(
            $consultation->doctorProfile->is($doctor)
        );
    }

    public function test_patient_show_page_displays_consultation_history(): void
    {
        [$tenant, $user, $doctor, $patient] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now(),
            'reason' => 'Dolor abdominal',
            'heart_rate' => 72,
            'temperature_c' => 36.5,
            'status' => 'completed',
        ]);

        $this->actingAs($user)
            ->get(
                route('patients.show', [
                    'uuid' => $patient->uuid,
                ])
            )
            ->assertOk()
            ->assertSee('Historial de consultas')
            ->assertSee('Dolor abdominal')
            ->assertSee('72')
            ->assertSee('36.5');
    }

    public function test_user_can_view_consultation_detail(): void
    {
        [$tenant, $user, $doctor, $patient] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $consultation = Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now(),
            'reason' => 'Dolor de cabeza',
            'subjective' => 'Cefalea de dos días.',
            'objective' => 'Paciente orientado.',
            'assessment' => 'Cefalea.',
            'plan' => 'Seguimiento.',
            'systolic_bp' => 120,
            'diastolic_bp' => 80,
        ]);

        $this->actingAs($user)
            ->get(
                route('consultations.show', [
                    'uuid' => $consultation->uuid,
                ])
            )
            ->assertOk()
            ->assertSee('Dolor de cabeza')
            ->assertSee('Cefalea de dos días.')
            ->assertSee('Paciente orientado.')
            ->assertSee('Cefalea.')
            ->assertSee('Seguimiento.')
            ->assertSee('120/80');
    }

    public function test_consultation_detail_uses_uuid_route(): void
    {
        [$tenant, $user, $doctor, $patient] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $consultation = Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now(),
        ]);

        $this->assertSame(
            'uuid',
            $consultation->getRouteKeyName()
        );

        $this->assertSame(
            $consultation->uuid,
            $consultation->getRouteKey()
        );

        $url = route('consultations.show', [
            'uuid' => $consultation->uuid,
        ]);

        $this->assertStringEndsWith(
            '/consultations/' . $consultation->uuid,
            $url
        );
    }

    public function test_user_cannot_create_consultation_for_patient_from_another_tenant(): void
    {
        [$tenantA, $userA] = $this->createUser(
            tenantName: 'Tenant A',
            tenantSlug: 'tenant-a',
            email: 'a@example.com'
        );

        [$tenantB,, $doctorB, $patientB] = $this->createContext(
            tenantName: 'Tenant B',
            tenantSlug: 'tenant-b',
            email: 'b@example.com'
        );

        app(TenantContext::class)->set($tenantA);

        $this->actingAs($userA)
            ->get(
                route('consultations.create', [
                    'uuid' => $patientB->uuid,
                ])
            )
            ->assertNotFound();

        $this->assertDatabaseCount('consultations', 0);
    }

    public function test_user_cannot_view_consultation_from_another_tenant(): void
    {
        [$tenantA, $userA] = $this->createUser(
            tenantName: 'Tenant A',
            tenantSlug: 'tenant-a',
            email: 'a@example.com'
        );

        [$tenantB,, $doctorB, $patientB] = $this->createContext(
            tenantName: 'Tenant B',
            tenantSlug: 'tenant-b',
            email: 'b@example.com'
        );

        app(TenantContext::class)->set($tenantB);

        $consultationB = Consultation::create([
            'patient_id' => $patientB->id,
            'doctor_profile_id' => $doctorB->id,
            'consultation_at' => now(),
            'reason' => 'Consulta privada',
        ]);

        app(TenantContext::class)->set($tenantA);

        $this->actingAs($userA)
            ->get(
                route('consultations.show', [
                    'uuid' => $consultationB->uuid,
                ])
            )
            ->assertNotFound();
    }

    public function test_patient_history_does_not_show_consultations_from_other_patients(): void
    {
        [$tenant, $user, $doctor, $patientA] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $patientB = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'B',
        ]);

        Consultation::create([
            'patient_id' => $patientA->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now(),
            'reason' => 'Consulta Paciente A',
        ]);

        Consultation::create([
            'patient_id' => $patientB->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now(),
            'reason' => 'Consulta Paciente B',
        ]);

        $this->actingAs($user)
            ->get(
                route('patients.show', [
                    'uuid' => $patientA->uuid,
                ])
            )
            ->assertOk()
            ->assertSee('Consulta Paciente A')
            ->assertDontSee('Consulta Paciente B');
    }

    private function createContext(
        string $tenantName = 'Consultorio Test',
        string $tenantSlug = 'consultorio-test',
        string $email = 'doctor@example.com',
    ): array {
        [$tenant, $user] = $this->createUser(
            tenantName: $tenantName,
            tenantSlug: $tenantSlug,
            email: $email
        );

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

    private function createUser(
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

        return [
            $tenant,
            $user,
        ];
    }
}
