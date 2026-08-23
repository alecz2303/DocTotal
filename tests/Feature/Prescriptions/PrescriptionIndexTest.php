<?php

namespace Tests\Feature\Prescriptions;

use App\Models\Consultation;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PrescriptionIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_prescription_index(): void
    {
        [$tenant, $user] = $this->createUser();

        app(TenantContext::class)->set($tenant);

        $this->actingAs($user)
            ->get(route('prescriptions.index'))
            ->assertOk()
            ->assertSee('Recetas');
    }

    public function test_prescription_index_displays_current_tenant_prescriptions(): void
    {
        [
            $tenant,
            $user,
            $doctor,
            $patient,
            $consultation,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $prescription = Prescription::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_id' => $consultation->id,
            'prescribed_at' => now(),
        ]);

        PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medication_name' => 'Paracetamol',
        ]);

        $this->actingAs($user)
            ->get(route('prescriptions.index'))
            ->assertOk()
            ->assertSee('Paciente')
            ->assertSee('Test')
            ->assertSee('Doctor')
            ->assertSee('Ver receta');
    }

    public function test_prescription_index_searches_by_patient_first_name(): void
    {
        [$tenant, $user, $doctor] = $this->createBaseContext();

        app(TenantContext::class)->set($tenant);

        $patientA = Patient::create([
            'first_name' => 'Alejandro',
            'last_name' => 'Rueda',
        ]);

        $patientB = Patient::create([
            'first_name' => 'María',
            'last_name' => 'López',
        ]);

        $this->createPrescription(
            $doctor,
            $patientA,
            now()
        );

        $this->createPrescription(
            $doctor,
            $patientB,
            now()
        );

        Livewire::actingAs($user)
            ->test('pages::prescriptions.index')
            ->set('search', 'Alejandro')
            ->assertSee('Alejandro')
            ->assertDontSee('María');
    }

    public function test_prescription_index_searches_by_patient_last_name(): void
    {
        [$tenant, $user, $doctor] = $this->createBaseContext();

        app(TenantContext::class)->set($tenant);

        $patientA = Patient::create([
            'first_name' => 'Alejandro',
            'last_name' => 'Rueda',
        ]);

        $patientB = Patient::create([
            'first_name' => 'María',
            'last_name' => 'López',
        ]);

        $this->createPrescription(
            $doctor,
            $patientA,
            now()
        );

        $this->createPrescription(
            $doctor,
            $patientB,
            now()
        );

        Livewire::actingAs($user)
            ->test('pages::prescriptions.index')
            ->set('search', 'López')
            ->assertSee('María')
            ->assertSee('López')
            ->assertDontSee('Alejandro');
    }

    public function test_prescription_index_searches_by_patient_email(): void
    {
        [$tenant, $user, $doctor] = $this->createBaseContext();

        app(TenantContext::class)->set($tenant);

        $patientA = Patient::create([
            'first_name' => 'Alejandro',
            'last_name' => 'Rueda',
            'email' => 'alejandro@example.com',
        ]);

        $patientB = Patient::create([
            'first_name' => 'María',
            'last_name' => 'López',
            'email' => 'maria@example.com',
        ]);

        $this->createPrescription(
            $doctor,
            $patientA,
            now()
        );

        $this->createPrescription(
            $doctor,
            $patientB,
            now()
        );

        Livewire::actingAs($user)
            ->test('pages::prescriptions.index')
            ->set('search', 'maria@example.com')
            ->assertSee('María')
            ->assertDontSee('Alejandro');
    }

    public function test_prescription_index_filters_from_date(): void
    {
        [$tenant, $user, $doctor] = $this->createBaseContext();

        app(TenantContext::class)->set($tenant);

        $patientA = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Antiguo',
        ]);

        $patientB = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Reciente',
        ]);

        $this->createPrescription(
            $doctor,
            $patientA,
            '2026-08-01 10:00:00'
        );

        $this->createPrescription(
            $doctor,
            $patientB,
            '2026-08-20 10:00:00'
        );

        Livewire::actingAs($user)
            ->test('pages::prescriptions.index')
            ->set('dateFrom', '2026-08-15')
            ->assertSee('Reciente')
            ->assertDontSee('Antiguo');
    }

    public function test_prescription_index_filters_to_date(): void
    {
        [$tenant, $user, $doctor] = $this->createBaseContext();

        app(TenantContext::class)->set($tenant);

        $patientA = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Antiguo',
        ]);

        $patientB = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Reciente',
        ]);

        $this->createPrescription(
            $doctor,
            $patientA,
            '2026-08-01 10:00:00'
        );

        $this->createPrescription(
            $doctor,
            $patientB,
            '2026-08-20 10:00:00'
        );

        Livewire::actingAs($user)
            ->test('pages::prescriptions.index')
            ->set('dateTo', '2026-08-10')
            ->assertSee('Antiguo')
            ->assertDontSee('Reciente');
    }

    public function test_prescription_index_filters_between_dates(): void
    {
        [$tenant, $user, $doctor] = $this->createBaseContext();

        app(TenantContext::class)->set($tenant);

        $patientA = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Primero',
        ]);

        $patientB = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Medio',
        ]);

        $patientC = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Último',
        ]);

        $this->createPrescription(
            $doctor,
            $patientA,
            '2026-08-01 10:00:00'
        );

        $this->createPrescription(
            $doctor,
            $patientB,
            '2026-08-15 10:00:00'
        );

        $this->createPrescription(
            $doctor,
            $patientC,
            '2026-08-30 10:00:00'
        );

        Livewire::actingAs($user)
            ->test('pages::prescriptions.index')
            ->set('dateFrom', '2026-08-10')
            ->set('dateTo', '2026-08-20')
            ->assertSee('Medio')
            ->assertDontSee('Primero')
            ->assertDontSee('Último');
    }

    public function test_user_can_clear_prescription_filters(): void
    {
        [$tenant, $user] = $this->createUser();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::prescriptions.index')
            ->set('search', 'Alejandro')
            ->set('dateFrom', '2026-08-01')
            ->set('dateTo', '2026-08-31')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('dateFrom', '')
            ->assertSet('dateTo', '');
    }

    public function test_empty_prescription_index_displays_empty_state(): void
    {
        [$tenant, $user] = $this->createUser();

        app(TenantContext::class)->set($tenant);

        $this->actingAs($user)
            ->get(route('prescriptions.index'))
            ->assertOk()
            ->assertSee('No se encontraron recetas')
            ->assertSee(
                'Las recetas emitidas aparecerán aquí.'
            );
    }

    public function test_prescription_index_only_displays_current_tenant_records(): void
    {
        [
            $tenantA,
            $userA,
            $doctorA,
            $patientA,
            $consultationA,
        ] = $this->createContext(
            tenantName: 'Tenant A',
            tenantSlug: 'tenant-a',
            email: 'a@example.com'
        );

        [
            $tenantB,,
            $doctorB,
            $patientB,
            $consultationB,
        ] = $this->createContext(
            tenantName: 'Tenant B',
            tenantSlug: 'tenant-b',
            email: 'b@example.com'
        );

        app(TenantContext::class)->set($tenantA);

        $patientA->update([
            'first_name' => 'Paciente',
            'last_name' => 'TenantA',
        ]);

        app(TenantContext::class)->set($tenantB);

        $patientB->update([
            'first_name' => 'Paciente',
            'last_name' => 'TenantB',
        ]);

        app(TenantContext::class)->set($tenantA);

        Prescription::create([
            'patient_id' => $patientA->id,
            'doctor_profile_id' => $doctorA->id,
            'consultation_id' => $consultationA->id,
            'prescribed_at' => now(),
            'general_instructions' => 'Receta Tenant A',
        ]);


        app(TenantContext::class)->set($tenantB);

        Prescription::create([
            'patient_id' => $patientB->id,
            'doctor_profile_id' => $doctorB->id,
            'consultation_id' => $consultationB->id,
            'prescribed_at' => now(),
            'general_instructions' => 'Receta Tenant B',
        ]);

        app(TenantContext::class)->set($tenantA);

        $this->actingAs($userA)
            ->get(route('prescriptions.index'))
            ->assertOk()
            ->assertSee('TenantA')
            ->assertDontSee('TenantB');

        $this->assertSame(
            1,
            Prescription::query()->count()
        );
    }

    public function test_prescription_index_item_count_is_correct(): void
    {
        [
            $tenant,
            $user,
            $doctor,
            $patient,
            $consultation,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $prescription = Prescription::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_id' => $consultation->id,
            'prescribed_at' => now(),
        ]);

        PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medication_name' => 'Medicamento A',
        ]);

        PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medication_name' => 'Medicamento B',
        ]);

        $this->actingAs($user)
            ->get(route('prescriptions.index'))
            ->assertOk()
            ->assertSee('2');
    }

    private function createPrescription(
        DoctorProfile $doctor,
        Patient $patient,
        string|\DateTimeInterface $date,
    ): Prescription {
        $consultation = Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => $date,
        ]);

        return Prescription::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_id' => $consultation->id,
            'prescribed_at' => $date,
        ]);
    }

    private function createBaseContext(): array
    {
        [$tenant, $user] = $this->createUser();

        app(TenantContext::class)->set($tenant);

        $doctor = DoctorProfile::create([
            'user_id' => $user->id,
            'first_name' => 'Doctor',
            'last_name' => 'Test',
        ]);

        return [
            $tenant,
            $user,
            $doctor,
        ];
    }

    private function createContext(
        string $tenantName = 'Consultorio Test',
        string $tenantSlug = 'consultorio-test',
        string $email = 'doctor@example.com',
    ): array {
        [
            $tenant,
            $user,
            $doctor,
        ] = $this->createBaseContextFor(
            tenantName: $tenantName,
            tenantSlug: $tenantSlug,
            email: $email
        );

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Test',
        ]);

        $consultation = Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now(),
        ]);

        return [
            $tenant,
            $user,
            $doctor,
            $patient,
            $consultation,
        ];
    }

    private function createBaseContextFor(
        string $tenantName,
        string $tenantSlug,
        string $email,
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

        return [
            $tenant,
            $user,
            $doctor,
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
