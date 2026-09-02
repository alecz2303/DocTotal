<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AuditEvent;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class AuditEventTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(TenantContext::class)->clear();

        parent::tearDown();
    }

    public function test_audit_event_belongs_to_current_tenant(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        app(TenantContext::class)->set($tenant);

        $event = AuditEvent::create([
            'action' => 'patient.updated',
            'description' => 'Paciente actualizado.',
        ]);

        $this->assertSame(
            $tenant->id,
            $event->tenant_id
        );

        $this->assertTrue(
            $event->tenant->is($tenant)
        );
    }

    public function test_audit_event_can_belong_to_user(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        app(TenantContext::class)->set($tenant);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $event = AuditEvent::create([
            'user_id' => $user->id,
            'action' => 'patient.updated',
        ]);

        $this->assertTrue(
            $event->user->is($user)
        );
    }

    public function test_user_is_optional_for_system_events(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        app(TenantContext::class)->set($tenant);

        $event = AuditEvent::create([
            'action' => 'system.processed',
        ]);

        $this->assertNull($event->user_id);
        $this->assertNull($event->user);
    }

    public function test_metadata_is_cast_to_array(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        app(TenantContext::class)->set($tenant);

        $event = AuditEvent::create([
            'action' => 'appointment.updated',
            'metadata' => [
                'status_from' => 'scheduled',
                'status_to' => 'confirmed',
            ],
        ]);

        $event->refresh();

        $this->assertSame(
            [
                'status_from' => 'scheduled',
                'status_to' => 'confirmed',
            ],
            $event->metadata
        );
    }

    public function test_event_can_reference_a_patient(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Prueba',
            'second_last_name' => 'Test',
            'birth_date' => '1990-01-15',
        ]);

        $event = AuditEvent::create([
            'action' => 'patient.updated',
            'auditable_type' => Patient::class,
            'auditable_id' => $patient->id,
        ]);

        $this->assertInstanceOf(
            Patient::class,
            $event->auditable
        );

        $this->assertTrue(
            $event->auditable->is($patient)
        );
    }

    public function test_event_can_reference_different_resource_types(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Dr. Test',
            'email' => 'doctor@example.com',
            'password' => 'password123',
            'role' => 'owner',
        ]);

        app(TenantContext::class)->set($tenant);

        $specialty = \App\Models\Specialty::firstOrCreate(
            [
                'slug' => 'medicina-general',
            ],
            [
                'name' => 'Medicina General',
            ]
        );

        $doctor = \App\Models\DoctorProfile::create([
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

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $doctor->id,
            'starts_at' => '2026-09-02 10:00:00',
            'ends_at' => '2026-09-02 10:30:00',
            'status' => 'scheduled',
            'reason' => 'Consulta general',
        ]);

        $event = AuditEvent::create([
            'action' => 'appointment.updated',
            'auditable_type' => Appointment::class,
            'auditable_id' => $appointment->id,
        ]);

        $this->assertInstanceOf(
            Appointment::class,
            $event->auditable
        );

        $this->assertTrue(
            $event->auditable->is($appointment)
        );
    }

    public function test_global_scope_hides_events_from_other_tenants(): void
    {
        $tenantA = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        $tenantB = Tenant::create([
            'name' => 'Tenant B',
            'slug' => 'tenant-b',
        ]);

        app(TenantContext::class)->set($tenantA);

        AuditEvent::create([
            'action' => 'tenant-a.event',
        ]);

        app(TenantContext::class)->set($tenantB);

        AuditEvent::create([
            'action' => 'tenant-b.event',
        ]);

        $this->assertSame(
            1,
            AuditEvent::query()->count()
        );

        $this->assertSame(
            'tenant-b.event',
            AuditEvent::query()->first()->action
        );
    }

    public function test_event_cannot_be_created_without_tenant_context(): void
    {
        app(TenantContext::class)->clear();

        $this->expectException(RuntimeException::class);

        AuditEvent::create([
            'action' => 'invalid.event',
        ]);
    }

    public function test_audit_event_cannot_be_updated(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        app(TenantContext::class)->set($tenant);

        $event = AuditEvent::create([
            'action' => 'patient.updated',
            'description' => 'Evento original.',
        ]);

        $this->expectException(RuntimeException::class);

        $event->update([
            'description' => 'Evento alterado.',
        ]);
    }

    public function test_audit_event_cannot_be_deleted(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        app(TenantContext::class)->set($tenant);

        $event = AuditEvent::create([
            'action' => 'patient.updated',
            'description' => 'Evento original.',
        ]);

        $this->expectException(RuntimeException::class);

        $event->delete();
    }
}
