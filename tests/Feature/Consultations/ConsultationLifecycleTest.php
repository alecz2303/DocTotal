<?php

namespace Tests\Feature\Consultations;

use App\Models\Consultation;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\Specialty;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use LogicException;
use Tests\TestCase;
use App\Models\AuditEvent;
use Livewire\Livewire;


class ConsultationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_consultation_defaults_to_draft(): void
    {
        [
            $tenant,
            $user,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $consultation = Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now(),
            'reason' => 'Consulta inicial',
        ]);

        $consultation->refresh();

        $this->assertSame(
            Consultation::STATUS_DRAFT,
            $consultation->status
        );

        $this->assertTrue(
            $consultation->isDraft()
        );

        $this->assertFalse(
            $consultation->isCompleted()
        );
    }

    public function test_draft_consultation_can_be_completed(): void
    {
        $this->travelTo(
            Carbon::parse('2026-08-25 10:30:00')
        );

        [
            $tenant,
            $user,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $consultation = $this->createConsultation(
            $doctor,
            $patient
        );

        $consultation->complete();

        $consultation->refresh();

        $this->assertSame(
            Consultation::STATUS_COMPLETED,
            $consultation->status
        );

        $this->assertNotNull(
            $consultation->completed_at
        );

        $this->assertTrue(
            $consultation->completed_at->equalTo(now())
        );
    }

    public function test_completed_consultation_is_terminal(): void
    {
        [
            $tenant,
            $user,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $consultation = $this->createConsultation(
            $doctor,
            $patient
        );

        $consultation->complete();

        $consultation->refresh();

        $this->assertFalse(
            $consultation->canEdit()
        );

        $this->assertFalse(
            $consultation->canComplete()
        );

        $this->assertTrue(
            $consultation->isCompleted()
        );
    }

    public function test_draft_consultation_can_be_edited(): void
    {
        [
            $tenant,
            $user,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $consultation = $this->createConsultation(
            $doctor,
            $patient
        );

        $this->assertTrue(
            $consultation->canEdit()
        );

        $this->assertTrue(
            $consultation->canComplete()
        );
    }

    public function test_completed_consultation_cannot_be_completed_again(): void
    {
        [
            $tenant,
            $user,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $consultation = $this->createConsultation(
            $doctor,
            $patient
        );

        $consultation->complete();

        $this->expectException(
            LogicException::class
        );

        $consultation->complete();
    }

    public function test_explicit_completed_status_is_still_supported(): void
    {
        [
            $tenant,
            $user,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $consultation = Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now(),
            'reason' => 'Consulta histórica',
            'status' => Consultation::STATUS_COMPLETED,
        ]);

        $consultation->refresh();

        $this->assertSame(
            Consultation::STATUS_COMPLETED,
            $consultation->status
        );

        $this->assertTrue(
            $consultation->isCompleted()
        );
    }

    public function test_consultation_uuid_is_generated_automatically(): void
    {
        [
            $tenant,
            $user,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $consultation = $this->createConsultation(
            $doctor,
            $patient
        );

        $this->assertNotNull(
            $consultation->uuid
        );

        $this->assertNotSame(
            '',
            $consultation->uuid
        );
    }

    public function test_completing_consultation_creates_audit_event(): void
    {
        [
            $tenant,
            $user,
            $doctor,
            $patient,
        ] = $this->createContext();

        app(TenantContext::class)->set($tenant);

        $this->actingAs($user);

        Livewire::test('pages::consultations.create', [
            'uuid' => $patient->uuid,
        ])
            ->set(
                'consultation_at',
                '2026-09-02T10:00'
            )
            ->set(
                'reason',
                'Consulta general'
            )
            ->set(
                'subjective',
                'Paciente refiere malestar general.'
            )
            ->call('completeConsultation');

        $consultation = Consultation::query()
            ->where(
                'patient_id',
                $patient->id
            )
            ->latest('id')
            ->firstOrFail();

        $event = AuditEvent::query()
            ->where(
                'action',
                'consultation.completed'
            )
            ->firstOrFail();

        $this->assertSame(
            $tenant->id,
            $event->tenant_id
        );

        $this->assertSame(
            $user->id,
            $event->user_id
        );

        $this->assertSame(
            Consultation::class,
            $event->auditable_type
        );

        $this->assertSame(
            $consultation->id,
            $event->auditable_id
        );

        $this->assertNull(
            $event->metadata['appointment_id']
        );

        $this->assertArrayNotHasKey(
            'subjective',
            $event->metadata
        );

        $this->assertArrayNotHasKey(
            'objective',
            $event->metadata
        );

        $this->assertArrayNotHasKey(
            'assessment',
            $event->metadata
        );

        $this->assertArrayNotHasKey(
            'plan',
            $event->metadata
        );
    }

    private function createContext(): array
    {
        $tenant = Tenant::create([
            'name' => 'Consultorio Test',
            'slug' => 'consultorio-' . str()->random(10),
            'onboarding_completed_at' => now(),
        ]);

        app(TenantContext::class)->set($tenant);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Dr. Test',
            'email' => str()->random(10) . '@example.com',
            'password' => 'password123',
            'role' => 'owner',
        ]);

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
        ]);

        $patient = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Test',
            'birth_date' => '1990-01-15',
        ]);

        return [
            $tenant,
            $user,
            $doctor,
            $patient,
        ];
    }

    private function createConsultation(
        DoctorProfile $doctor,
        Patient $patient,
    ): Consultation {
        return Consultation::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'consultation_at' => now(),
            'reason' => 'Consulta general',
        ]);
    }
}
