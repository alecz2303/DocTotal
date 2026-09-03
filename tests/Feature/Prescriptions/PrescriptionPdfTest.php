<?php

namespace Tests\Feature\Prescriptions;

use App\Models\Consultation;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\PracticeProfile;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Specialty;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrescriptionPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_download_prescription_pdf(): void
    {
        [
            $tenant,
            $user,
            $doctor,
            $patient,
            $consultation,
            $practice,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $prescription = $this->createPrescription(
            $doctor,
            $patient,
            $consultation
        );

        $response = $this->actingAs($user)
            ->get(
                route('prescriptions.pdf', [
                    'uuid' => $prescription->uuid,
                ])
            );

        $response->assertOk();

        $this->assertStringStartsWith(
            'application/pdf',
            (string) $response->headers->get('Content-Type')
        );
    }

    public function test_pdf_download_uses_prescription_uuid(): void
    {
        [
            $tenant,
            $user,
            $doctor,
            $patient,
            $consultation,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $prescription = $this->createPrescription(
            $doctor,
            $patient,
            $consultation
        );

        $url = route('prescriptions.pdf', [
            'uuid' => $prescription->uuid,
        ]);

        $this->assertStringEndsWith(
            '/prescriptions/' . $prescription->uuid . '/pdf',
            $url
        );

        $this->actingAs($user)
            ->get($url)
            ->assertOk();
    }

    public function test_pdf_download_has_expected_filename(): void
    {
        [
            $tenant,
            $user,
            $doctor,
            $patient,
            $consultation,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $prescription = $this->createPrescription(
            $doctor,
            $patient,
            $consultation,
            '2026-08-23 10:30:00'
        );

        $response = $this->actingAs($user)
            ->get(
                route('prescriptions.pdf', [
                    'uuid' => $prescription->uuid,
                ])
            );

        $response->assertOk();

        $contentDisposition = (string) $response->headers->get(
            'Content-Disposition'
        );

        $this->assertStringContainsString(
            'receta-paciente-test-2026-08-23.pdf',
            $contentDisposition
        );
    }

    public function test_pdf_can_be_generated_with_medications_and_general_instructions(): void
    {
        [
            $tenant,
            $user,
            $doctor,
            $patient,
            $consultation,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $prescription = $this->createPrescription(
            $doctor,
            $patient,
            $consultation
        );

        PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medication_name' => 'Paracetamol',
            'presentation' => 'Tabletas 500 mg',
            'dose' => '1 tableta',
            'frequency' => 'Cada 8 horas',
            'duration' => '3 d├¡as',
            'instructions' => 'Tomar despu├®s de alimentos.',
            'sort_order' => 1,
        ]);

        PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medication_name' => 'Ibuprofeno',
            'presentation' => 'Tabletas 400 mg',
            'dose' => '1 tableta',
            'frequency' => 'Cada 12 horas',
            'duration' => '3 d├¡as',
            'instructions' => 'Tomar con alimentos.',
            'sort_order' => 2,
        ]);

        $response = $this->actingAs($user)
            ->get(
                route('prescriptions.pdf', [
                    'uuid' => $prescription->uuid,
                ])
            );

        $response->assertOk();

        $this->assertNotEmpty(
            $response->getContent()
        );

        $this->assertGreaterThan(
            1000,
            strlen($response->getContent())
        );
    }

    public function test_user_cannot_download_prescription_pdf_from_another_tenant(): void
    {
        [
            $tenantA,
            $userA,
        ] = $this->createUser(
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

        app(TenantContext::class)->set($tenantB);

        $prescriptionB = $this->createPrescription(
            $doctorB,
            $patientB,
            $consultationB
        );

        app(TenantContext::class)->set($tenantA);

        $this->actingAs($userA)
            ->get(
                route('prescriptions.pdf', [
                    'uuid' => $prescriptionB->uuid,
                ])
            )
            ->assertNotFound();
    }

    public function test_user_cannot_download_nonexistent_prescription_pdf(): void
    {
        [$tenant, $user] = $this->createUser();

        app(TenantContext::class)->set($tenant);

        $this->actingAs($user)
            ->get(
                route('prescriptions.pdf', [
                    'uuid' => '00000000-0000-0000-0000-000000000000',
                ])
            )
            ->assertNotFound();
    }

    public function test_pdf_works_without_logo_or_signature(): void
    {
        [
            $tenant,
            $user,
            $doctor,
            $patient,
            $consultation,
            $practice,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $doctor->update([
            'signature_path' => null,
        ]);

        $practice->update([
            'logo_path' => null,
        ]);

        $prescription = $this->createPrescription(
            $doctor,
            $patient,
            $consultation
        );

        $this->actingAs($user)
            ->get(
                route('prescriptions.pdf', [
                    'uuid' => $prescription->uuid,
                ])
            )
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_print_view_is_only_available_for_current_tenant(): void
    {
        [
            $tenantA,
            $userA,
        ] = $this->createUser(
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

        app(TenantContext::class)->set($tenantB);

        $prescriptionB = $this->createPrescription(
            $doctorB,
            $patientB,
            $consultationB
        );

        app(TenantContext::class)->set($tenantA);

        $this->actingAs($userA)
            ->get(
                route('prescriptions.print', [
                    'uuid' => $prescriptionB->uuid,
                ])
            )
            ->assertNotFound();
    }

    private function createPrescription(
        DoctorProfile $doctor,
        Patient $patient,
        Consultation $consultation,
        string $prescribedAt = '2026-08-23 10:30:00',
    ): Prescription {
        return Prescription::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_id' => $consultation->id,
            'prescribed_at' => $prescribedAt,
            'general_instructions' =>
            'Mantener hidrataci├│n y acudir a revisi├│n si los s├¡ntomas empeoran.',
            'status' => 'active',
        ]);
    }

    private function createContext(
        string $tenantName = 'Consultorio Test',
        string $tenantSlug = 'consultorio-test',
        string $email = 'doctor@example.com',
    ): array {
        [
            $tenant,
            $user,
        ] = $this->createUser(
            tenantName: $tenantName,
            tenantSlug: $tenantSlug,
            email: $email
        );

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

        $practice = PracticeProfile::create([
            'public_name' => $tenantName,
            'phone' => '9611234567',
            'email' => 'consultorio@example.com',
            'address_line_1' => 'Calle Prueba 123',
            'neighborhood' => 'Centro',
            'city' => 'Tuxtla Guti├®rrez',
            'state' => 'Chiapas',
            'postal_code' => '29000',
            'country' => 'MX',
            'print_footer' => 'Citas: 9611234567',
        ]);

        $patient = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Test',
            'birth_date' => '1990-01-15',
        ]);

        $consultation = Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => '2026-08-23 09:00:00',
        ]);

        return [
            $tenant,
            $user,
            $doctor,
            $patient,
            $consultation,
            $practice,
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
            'trial_started_at' => now(),
            'trial_ends_at' => now()->addDays(30),
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
