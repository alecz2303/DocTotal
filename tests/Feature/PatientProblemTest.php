<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\PatientProblem;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientProblemTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_problem_belongs_to_patient_and_current_tenant(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
        ]);

        $problem = PatientProblem::create([
            'patient_id' => $patient->id,
            'code' => 'I10',
            'description' => 'Hipertensión arterial',
            'started_at' => '2026-08-01',
            'notes' => 'En seguimiento.',
        ]);

        $this->assertSame(
            $tenant->id,
            $problem->tenant_id
        );

        $this->assertTrue(
            $problem->patient->is($patient)
        );

        $this->assertSame(
            PatientProblem::STATUS_ACTIVE,
            $problem->status
        );

        $this->assertTrue(
            $problem->isActive()
        );

        $this->assertFalse(
            $problem->isResolved()
        );
    }

    public function test_patient_has_many_problems(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'María',
            'last_name' => 'López',
        ]);

        PatientProblem::create([
            'patient_id' => $patient->id,
            'description' => 'Diabetes mellitus tipo 2',
        ]);

        PatientProblem::create([
            'patient_id' => $patient->id,
            'description' => 'Hipertensión arterial',
        ]);

        $this->assertCount(
            2,
            $patient->problems
        );
    }

    public function test_patient_problem_can_be_resolved(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Pedro',
            'last_name' => 'Ramírez',
        ]);

        $problem = PatientProblem::create([
            'patient_id' => $patient->id,
            'description' => 'Infección respiratoria',
            'started_at' => '2026-08-15',
        ]);

        $problem->resolve('2026-08-30');

        $problem->refresh();

        $this->assertSame(
            PatientProblem::STATUS_RESOLVED,
            $problem->status
        );

        $this->assertFalse(
            $problem->isActive()
        );

        $this->assertTrue(
            $problem->isResolved()
        );

        $this->assertSame(
            '2026-08-30',
            $problem->resolved_at->format('Y-m-d')
        );
    }

    public function test_patient_problem_can_be_reopened(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Ana',
            'last_name' => 'Torres',
        ]);

        $problem = PatientProblem::create([
            'patient_id' => $patient->id,
            'description' => 'Dolor lumbar',
            'status' => PatientProblem::STATUS_RESOLVED,
            'resolved_at' => '2026-08-20',
        ]);

        $problem->reopen();

        $problem->refresh();

        $this->assertSame(
            PatientProblem::STATUS_ACTIVE,
            $problem->status
        );

        $this->assertNull(
            $problem->resolved_at
        );

        $this->assertTrue(
            $problem->isActive()
        );
    }

    public function test_patient_problems_are_isolated_between_tenants(): void
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

        $patientA = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'A',
        ]);

        PatientProblem::create([
            'patient_id' => $patientA->id,
            'description' => 'Problema Tenant A',
        ]);

        app(TenantContext::class)->set($tenantB);

        $patientB = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'B',
        ]);

        PatientProblem::create([
            'patient_id' => $patientB->id,
            'description' => 'Problema Tenant B',
        ]);

        app(TenantContext::class)->set($tenantA);

        $problems = PatientProblem::query()->get();

        $this->assertCount(
            1,
            $problems
        );

        $this->assertSame(
            'Problema Tenant A',
            $problems->first()->description
        );

        $this->assertSame(
            $tenantA->id,
            $problems->first()->tenant_id
        );
    }

    public function test_patient_problem_cannot_be_created_without_tenant_context(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Sin Tenant',
        ]);

        app(TenantContext::class)->clear();

        $this->expectException(\RuntimeException::class);

        $this->expectExceptionMessage(
            'No tenant has been resolved for the current request.'
        );

        PatientProblem::create([
            'patient_id' => $patient->id,
            'description' => 'Problema sin tenant',
        ]);
    }
}
