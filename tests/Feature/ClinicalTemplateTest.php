<?php

namespace Tests\Feature;

use App\Models\AuditEvent;
use App\Models\ClinicalTemplate;
use App\Models\Consultation;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClinicalTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_clinical_template(): void
    {
        [$tenant, $user] = $this->createUser();
        app(TenantContext::class)->set($tenant);

        Livewire::actingAs($user)
            ->test('pages::clinical-templates.index')
            ->call('createTemplate')
            ->set('name', 'Seguimiento general')
            ->set('subjective', 'Evolución desde la última consulta.')
            ->set('objective', 'Exploración dirigida.')
            ->set('assessment', 'Evolución clínica.')
            ->set('plan', 'Continuar seguimiento.')
            ->call('saveTemplate')
            ->assertHasNoErrors();

        $template = ClinicalTemplate::firstOrFail();
        $this->assertSame($tenant->id, $template->tenant_id);
        $this->assertSame('Seguimiento general', $template->name);
        $this->assertSame('Evolución clínica.', $template->content['assessment']);
        $this->assertTrue($template->active);

        $this->assertDatabaseHas('audit_events', [
            'action' => 'clinical_template.created',
            'auditable_type' => ClinicalTemplate::class,
            'auditable_id' => $template->id,
        ]);
    }

    public function test_templates_are_isolated_by_tenant(): void
    {
        [$tenantA, $userA] = $this->createUser('Consultorio A', 'consultorio-a', 'a@example.com');
        app(TenantContext::class)->set($tenantA);
        ClinicalTemplate::create(['name' => 'Plantilla A', 'content' => []]);

        [$tenantB, $userB] = $this->createUser('Consultorio B', 'consultorio-b', 'b@example.com');
        app(TenantContext::class)->set($tenantB);
        ClinicalTemplate::create(['name' => 'Plantilla B', 'content' => []]);

        Livewire::actingAs($userB)
            ->test('pages::clinical-templates.index')
            ->assertSee('Plantilla B')
            ->assertDontSee('Plantilla A');
    }

    public function test_inactive_template_is_not_available_in_consultation(): void
    {
        [$tenant, $user, $doctor, $patient] = $this->createClinicalContext();
        app(TenantContext::class)->set($tenant);

        ClinicalTemplate::create(['name' => 'Activa', 'content' => [], 'active' => true]);
        ClinicalTemplate::create(['name' => 'Inactiva', 'content' => [], 'active' => false]);

        Livewire::actingAs($user)
            ->test('pages::consultations.create', ['uuid' => $patient->uuid])
            ->assertSee('Activa')
            ->assertDontSee('Inactiva');
    }

    public function test_applying_template_copies_content_to_consultation(): void
    {
        [$tenant, $user, $doctor, $patient] = $this->createClinicalContext();
        app(TenantContext::class)->set($tenant);

        $template = ClinicalTemplate::create([
            'name' => 'Control',
            'content' => [
                'reason' => 'Consulta de control',
                'subjective' => 'Paciente refiere mejoría.',
                'objective' => 'Sin datos de alarma.',
                'assessment' => 'Evolución favorable.',
                'plan' => 'Continuar manejo.',
            ],
        ]);

        Livewire::actingAs($user)
            ->test('pages::consultations.create', ['uuid' => $patient->uuid])
            ->set('selectedTemplateId', (string) $template->id)
            ->call('applyClinicalTemplate')
            ->assertHasNoErrors()
            ->assertSet('reason', 'Consulta de control')
            ->assertSet('assessment', 'Evolución favorable.');

        $consultation = Consultation::firstOrFail();
        $this->assertSame('Paciente refiere mejoría.', $consultation->subjective);
        $this->assertSame('Continuar manejo.', $consultation->plan);
        $this->assertSame(1, $template->fresh()->usage_count);

        $this->assertDatabaseHas('audit_events', [
            'action' => 'clinical_template.applied',
            'auditable_type' => Consultation::class,
            'auditable_id' => $consultation->id,
        ]);
    }

    public function test_editing_template_does_not_change_existing_consultation(): void
    {
        [$tenant, $user, $doctor, $patient] = $this->createClinicalContext();
        app(TenantContext::class)->set($tenant);

        $template = ClinicalTemplate::create([
            'name' => 'Control',
            'content' => ['subjective' => 'Texto original.'],
        ]);

        Livewire::actingAs($user)
            ->test('pages::consultations.create', ['uuid' => $patient->uuid])
            ->set('selectedTemplateId', (string) $template->id)
            ->call('applyClinicalTemplate');

        $consultation = Consultation::firstOrFail();
        $template->update(['content' => ['subjective' => 'Texto modificado.']]);
        $this->assertSame('Texto original.', $consultation->fresh()->subjective);
    }

    public function test_used_template_cannot_be_deleted_but_can_be_deactivated(): void
    {
        [$tenant, $user] = $this->createUser();
        app(TenantContext::class)->set($tenant);

        $template = ClinicalTemplate::create([
            'name' => 'Usada',
            'content' => [],
            'usage_count' => 1,
        ]);

        Livewire::actingAs($user)
            ->test('pages::clinical-templates.index')
            ->call('deleteTemplate', $template->id)
            ->assertHasErrors(['deleteTemplate'])
            ->call('toggleTemplate', $template->id);

        $this->assertNotNull($template->fresh());
        $this->assertFalse($template->fresh()->active);
    }

    private function createClinicalContext(): array
    {
        [$tenant, $user] = $this->createUser();
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

        return [$tenant, $user, $doctor, $patient];
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

        return [$tenant, $user];
    }
}
