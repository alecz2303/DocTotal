<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\PatientProblem;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PatientProblemFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_patient_problem(): void
    {
        [$tenant, $user, $patient] = $this->createUserAndPatient();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::patients.show', [
                'uuid' => $patient->uuid,
            ])
            ->call('openCreatePatientProblemModal')
            ->set('patient_problem_code', 'I10')
            ->set(
                'patient_problem_description',
                'Hipertensión esencial'
            )
            ->set(
                'patient_problem_status',
                PatientProblem::STATUS_ACTIVE
            )
            ->set(
                'patient_problem_started_at',
                '2026-08-15'
            )
            ->set(
                'patient_problem_notes',
                'Paciente en seguimiento.'
            )
            ->call('savePatientProblem')
            ->assertHasNoErrors();

        $problem = PatientProblem::firstOrFail();

        $this->assertSame(
            $tenant->id,
            $problem->tenant_id
        );

        $this->assertSame(
            $patient->id,
            $problem->patient_id
        );

        $this->assertSame(
            'I10',
            $problem->code
        );

        $this->assertSame(
            'Hipertensión esencial',
            $problem->description
        );

        $this->assertSame(
            PatientProblem::STATUS_ACTIVE,
            $problem->status
        );

        $this->assertSame(
            '2026-08-15',
            $problem->started_at?->format('Y-m-d')
        );

        $this->assertSame(
            'Paciente en seguimiento.',
            $problem->notes
        );

        $this->assertNull(
            $problem->resolved_at
        );
    }

    public function test_patient_problem_requires_description(): void
    {
        [$tenant, $user, $patient] = $this->createUserAndPatient();

        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::patients.show', [
                'uuid' => $patient->uuid,
            ])
            ->call('openCreatePatientProblemModal')
            ->set(
                'patient_problem_description',
                ''
            )
            ->call('savePatientProblem')
            ->assertHasErrors([
                'patient_problem_description',
            ]);

        $this->assertDatabaseCount(
            'patient_problems',
            0
        );
    }

    public function test_user_can_edit_patient_problem(): void
    {
        [$tenant, $user, $patient] = $this->createUserAndPatient();

        app(TenantContext::class)->set($tenant);

        $problem = PatientProblem::create([
            'patient_id' => $patient->id,
            'code' => 'I10',
            'description' => 'Hipertensión esencial',
            'status' => PatientProblem::STATUS_ACTIVE,
            'started_at' => '2026-08-15',
            'notes' => 'Nota inicial.',
        ]);

        Livewire::actingAs($user)
            ->test('pages::patients.show', [
                'uuid' => $patient->uuid,
            ])
            ->call(
                'openEditPatientProblemModal',
                $problem->id
            )
            ->set(
                'patient_problem_notes',
                'Control periódico cada 30 días.'
            )
            ->call('savePatientProblem')
            ->assertHasNoErrors();

        $problem->refresh();

        $this->assertSame(
            'Control periódico cada 30 días.',
            $problem->notes
        );
    }

    public function test_user_can_resolve_patient_problem(): void
    {
        [$tenant, $user, $patient] = $this->createUserAndPatient();

        app(TenantContext::class)->set($tenant);

        $problem = PatientProblem::create([
            'patient_id' => $patient->id,
            'description' => 'Dolor lumbar',
            'status' => PatientProblem::STATUS_ACTIVE,
        ]);

        Livewire::actingAs($user)
            ->test('pages::patients.show', [
                'uuid' => $patient->uuid,
            ])
            ->call(
                'resolvePatientProblem',
                $problem->id
            )
            ->assertHasNoErrors();

        $problem->refresh();

        $this->assertSame(
            PatientProblem::STATUS_RESOLVED,
            $problem->status
        );

        $this->assertNotNull(
            $problem->resolved_at
        );
    }

    public function test_user_can_reopen_patient_problem(): void
    {
        [$tenant, $user, $patient] = $this->createUserAndPatient();

        app(TenantContext::class)->set($tenant);

        $problem = PatientProblem::create([
            'patient_id' => $patient->id,
            'description' => 'Dolor lumbar',
            'status' => PatientProblem::STATUS_RESOLVED,
            'resolved_at' => '2026-08-30',
        ]);

        Livewire::actingAs($user)
            ->test('pages::patients.show', [
                'uuid' => $patient->uuid,
            ])
            ->call(
                'reopenPatientProblem',
                $problem->id
            )
            ->assertHasNoErrors();

        $problem->refresh();

        $this->assertSame(
            PatientProblem::STATUS_ACTIVE,
            $problem->status
        );

        $this->assertNull(
            $problem->resolved_at
        );
    }

    public function test_user_can_delete_patient_problem(): void
    {
        [$tenant, $user, $patient] = $this->createUserAndPatient();

        app(TenantContext::class)->set($tenant);

        $problem = PatientProblem::create([
            'patient_id' => $patient->id,
            'description' => 'Problema temporal',
        ]);

        Livewire::actingAs($user)
            ->test('pages::patients.show', [
                'uuid' => $patient->uuid,
            ])
            ->call(
                'deletePatientProblem',
                $problem->id
            )
            ->assertHasNoErrors();

        $this->assertSoftDeleted(
            'patient_problems',
            [
                'id' => $problem->id,
            ]
        );
    }

    public function test_patient_problem_cannot_be_manipulated_from_another_patient(): void
    {
        [$tenant, $user, $patient] = $this->createUserAndPatient();

        app(TenantContext::class)->set($tenant);

        $otherPatient = Patient::create([
            'first_name' => 'Otro',
            'last_name' => 'Paciente',
        ]);

        $problem = PatientProblem::create([
            'patient_id' => $otherPatient->id,
            'description' => 'Problema ajeno',
        ]);

        try {
            Livewire::actingAs($user)
                ->test('pages::patients.show', [
                    'uuid' => $patient->uuid,
                ])
                ->call(
                    'resolvePatientProblem',
                    $problem->id
                );

            $this->fail(
                'Se esperaba ModelNotFoundException al intentar modificar un problema de otro paciente.'
            );
        } catch (ModelNotFoundException $exception) {
            $this->assertSame(
                PatientProblem::class,
                $exception->getModel()
            );
        }

        $problem->refresh();

        $this->assertSame(
            PatientProblem::STATUS_ACTIVE,
            $problem->status
        );

        $this->assertNull(
            $problem->resolved_at
        );
    }

    private function createUserAndPatient(): array
    {
        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        app(TenantContext::class)->set($tenant);

        $patient = Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Prueba',
        ]);

        return [
            $tenant,
            $user,
            $patient,
        ];
    }
}
