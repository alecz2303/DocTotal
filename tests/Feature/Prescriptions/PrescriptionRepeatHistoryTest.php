<?php

namespace Tests\Feature\Prescriptions;

use App\Actions\Patients\BuildPatientClinicalTimeline;
use App\Actions\Prescriptions\RepeatPrescription;
use App\Models\Consultation;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\PatientMedicalHistory;
use App\Models\PracticeProfile;
use App\Models\Prescription;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class PrescriptionRepeatHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function context(bool $standalone = false): array
    {
        $slug = (string) Str::uuid();
        $tenant = Tenant::create([
            'name' => 'Consultorio de prueba', 'slug' => $slug,
            'trial_started_at' => now(), 'trial_ends_at' => now()->addDays(30),
            'onboarding_completed_at' => now(),
        ]);
        app(TenantContext::class)->set($tenant);
        $user = User::create([
            'tenant_id' => $tenant->id, 'name' => 'Doctor Test',
            'email' => $slug.'@example.com', 'password' => 'password123', 'role' => 'owner',
        ]);
        $user->forceFill(['email_verified_at' => now()])->saveQuietly();
        $doctor = DoctorProfile::create([
            'user_id' => $user->id, 'first_name' => 'Doctor', 'last_name' => 'Test',
            'professional_license' => '12345678',
        ]);
        $practice = PracticeProfile::create(['public_name' => 'Consultorio Test', 'country' => 'MX']);
        $patient = Patient::create(['first_name' => 'Paciente', 'last_name' => 'Test', 'birth_date' => '1990-01-15']);
        $consultation = $standalone ? null : Consultation::create([
            'patient_id' => $patient->id, 'doctor_profile_id' => $doctor->id,
            'consultation_at' => now()->subDays(30), 'status' => Consultation::STATUS_COMPLETED,
            'completed_at' => now()->subDays(30),
        ]);
        $source = Prescription::create([
            'patient_id' => $patient->id, 'doctor_profile_id' => $doctor->id,
            'consultation_id' => $consultation?->id, 'prescribed_at' => now()->subDays(30),
            'status' => 'active', 'general_instructions' => 'Indicaciones originales',
        ]);
        $source->items()->create([
            'medication_name' => 'Medicamento historico A', 'dose' => 'Dosis original',
            'presentation' => 'Tabletas', 'sort_order' => 1,
        ]);
        $this->actingAs($user);

        return [$tenant, $user, $doctor, $patient, $source, $practice, $consultation];
    }

    private function repeat(Prescription $source): Prescription
    {
        return app(RepeatPrescription::class)->execute($source->uuid, $source->patient_id, [
            'prescribed_at' => now()->format('Y-m-d H:i:s'),
            'general_instructions' => 'Indicaciones nuevas',
            'items' => [['medication_name' => 'Medicamento nuevo B', 'dose' => 'Dosis nueva']],
        ]);
    }

    private function history(Patient $patient)
    {
        return $this->get(route('patients.show', ['uuid' => $patient->uuid]));
    }

    public function test_consultation_history_offers_repeat_for_active_recipe(): void
    {
        [, , , $patient, $source] = $this->context();
        $this->history($patient)->assertOk()
            ->assertSee('data-repeat-context="consultation-history"', false)
            ->assertSee(route('prescriptions.repeat', ['uuid' => $source->uuid]), false);
    }

    public function test_standalone_history_offers_repeat(): void
    {
        [, , , $patient, $source] = $this->context(standalone: true);
        $this->history($patient)->assertOk()
            ->assertSee('data-repeat-context="standalone-history"', false)
            ->assertSee('data-repeat-prescription="'.$source->uuid.'"', false);
    }

    public function test_grouped_treatment_link_copies_the_whole_recipe(): void
    {
        [, , , $patient, $source] = $this->context();
        $source->items()->create(['medication_name' => 'Segundo medicamento', 'sort_order' => 2]);
        $this->history($patient)->assertOk()->assertSee('Repetir receta completa')
            ->assertSee('data-repeat-context="treatment-summary"', false);
        Livewire::test('pages::prescriptions.repeat', ['uuid' => $source->uuid])
            ->assertCount('items', 2)
            ->assertSet('items.0.medication_name', 'Medicamento historico A')
            ->assertSet('items.1.medication_name', 'Segundo medicamento');
        $this->assertDatabaseCount('prescriptions', 1);
    }

    public function test_cancelled_recipe_remains_visible_without_repeat_link(): void
    {
        [, , , $patient, $source] = $this->context();
        $source->update(['status' => 'cancelled']);
        $this->history($patient)->assertOk()->assertSee('Medicamento historico A')
            ->assertDontSee(route('prescriptions.repeat', ['uuid' => $source->uuid]), false);
    }

    public function test_history_does_not_offer_repeat_without_doctor_profile(): void
    {
        [, $user, $doctor, $patient, $source] = $this->context();
        $doctor->delete();
        $user->unsetRelation('doctorProfile');
        $this->history($patient)->assertOk()->assertSee('Medicamento historico A')
            ->assertDontSee(route('prescriptions.repeat', ['uuid' => $source->uuid]), false);
    }

    public function test_copy_is_a_single_standalone_event_not_attached_to_old_consultation(): void
    {
        [, , , $patient, $source, , $consultation] = $this->context();
        $new = $this->repeat($source);
        $timeline = app(BuildPatientClinicalTimeline::class)->handle($patient);
        $copies = $timeline->filter(fn ($entry) => $entry['type'] === 'prescription' && $entry['prescription']->id === $new->id);
        $this->assertCount(1, $copies);
        $this->assertCount(2, $timeline);
        $this->assertSame($new->id, $timeline->first()['prescription']->id);
        $this->assertSame([$source->id], $consultation->prescriptions()->pluck('id')->all());
        $this->history($patient)->assertOk()->assertSee('Medicamento nuevo B')
            ->assertSee(route('prescriptions.repeat', ['uuid' => $new->uuid]), false);
    }

    public function test_print_of_copy_uses_new_content_and_does_not_write_to_source(): void
    {
        [, , , , $source] = $this->context();
        $before = $source->fresh()->getRawOriginal();
        $itemBefore = $source->items()->first()->getRawOriginal();
        $new = $this->repeat($source);
        $this->get(route('prescriptions.print', ['uuid' => $new->uuid]))
            ->assertOk()->assertSee('Medicamento nuevo B')->assertSee('Dosis nueva')
            ->assertSee('Indicaciones nuevas')->assertDontSee('Medicamento historico A');
        $this->assertSame($before, $source->fresh()->getRawOriginal());
        $this->assertSame($itemBefore, $source->items()->first()->getRawOriginal());
    }

    public function test_copy_downloads_real_pdf_with_new_emission_date(): void
    {
        [, , , , $source] = $this->context();
        $new = $this->repeat($source);
        $response = $this->get(route('prescriptions.pdf', ['uuid' => $new->uuid]))->assertOk();
        $this->assertStringStartsWith('application/pdf', (string) $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('receta-paciente-test-'.$new->prescribed_at->format('Y-m-d').'.pdf', (string) $response->headers->get('Content-Disposition'));
        $this->assertNull($new->consultation_id);
    }

    public function test_pdf_template_uses_copy_content_without_original_treatment(): void
    {
        [, , , , $source, $practice] = $this->context();
        $new = $this->repeat($source)->load(['patient', 'doctorProfile.specialty', 'items']);
        $this->view('prescriptions.pdf', ['prescription' => $new, 'practice' => $practice])
            ->assertSee('Medicamento nuevo B')->assertSee('Indicaciones nuevas')
            ->assertDontSee('Medicamento historico A');
    }

    public function test_cancelling_origin_does_not_cancel_or_block_copy_outputs(): void
    {
        [, , , , $source] = $this->context();
        $new = $this->repeat($source);
        Livewire::test('pages::prescriptions.show', ['uuid' => $source->uuid])->call('cancelPrescription');
        $this->assertSame('active', $new->fresh()->status);
        $this->get(route('prescriptions.print', ['uuid' => $new->uuid]))->assertOk()->assertSee('Medicamento nuevo B');
        $this->get(route('prescriptions.pdf', ['uuid' => $new->uuid]))->assertOk();
    }

    public function test_editing_origin_does_not_change_copy_snapshot(): void
    {
        [, , , , $source] = $this->context();
        $new = $this->repeat($source);
        $before = $new->fresh()->getRawOriginal();
        $itemBefore = $new->items()->first()->getRawOriginal();
        Livewire::test('pages::prescriptions.edit', ['uuid' => $source->uuid])
            ->set('items.0.medication_name', 'Origen modificado')->call('updatePrescription')->assertHasNoErrors();
        $this->assertSame($before, $new->fresh()->getRawOriginal());
        $this->assertSame($itemBefore, $new->items()->first()->getRawOriginal());
    }

    public function test_copy_can_be_repeated_with_independent_ids_and_immediate_origin(): void
    {
        [, , , , $source] = $this->context();
        $first = $this->repeat($source);
        $second = $this->repeat($first);
        $this->assertSame($source->id, $first->source_prescription_id);
        $this->assertSame($first->id, $second->source_prescription_id);
        $this->assertSame($source->patient_id, $second->patient_id);
        $this->assertCount(3, array_unique([$source->uuid, $first->uuid, $second->uuid]));
        $this->assertNotSame($first->items()->first()->id, $second->items()->first()->id);
        $first->update(['status' => 'cancelled']);
        $this->assertSame('active', $source->fresh()->status);
        $this->assertSame('active', $second->fresh()->status);
    }

    public function test_deleted_origin_does_not_break_copy_detail_or_print(): void
    {
        [, , , , $source] = $this->context();
        $new = $this->repeat($source);
        $source->delete();
        $this->get(route('prescriptions.show', ['uuid' => $new->uuid]))->assertOk()
            ->assertSee('La receta origen no está disponible.')
            ->assertDontSee(route('prescriptions.show', ['uuid' => $source->uuid]), false);
        $this->get(route('prescriptions.print', ['uuid' => $new->uuid]))->assertOk();
        $this->assertSame('active', $new->fresh()->status);
    }

    public function test_other_patient_history_has_no_source_or_copy_links(): void
    {
        [, , , , $source] = $this->context();
        $new = $this->repeat($source);
        $other = Patient::create(['first_name' => 'Otro', 'last_name' => 'Paciente']);
        $this->history($other)->assertOk()
            ->assertDontSee(route('prescriptions.repeat', ['uuid' => $source->uuid]), false)
            ->assertDontSee(route('prescriptions.repeat', ['uuid' => $new->uuid]), false)
            ->assertDontSee('Medicamento nuevo B');
    }

    public function test_cross_tenant_history_and_all_copy_endpoints_are_rejected(): void
    {
        [$tenant, $user] = $this->context();
        [, , , $foreignPatient, $foreignSource] = $this->context();
        $foreignCopy = $this->repeat($foreignSource);
        app(TenantContext::class)->set($tenant);
        $this->actingAs($user);
        $this->history($foreignPatient)->assertNotFound();
        foreach (['show', 'edit', 'repeat', 'print', 'pdf'] as $endpoint) {
            $this->get(route('prescriptions.'.$endpoint, ['uuid' => $foreignCopy->uuid]))->assertNotFound();
        }
    }

    public function test_repeating_does_not_update_current_medication_history(): void
    {
        [, , , $patient, $source] = $this->context();
        $history = PatientMedicalHistory::create([
            'patient_id' => $patient->id, 'current_medications_text' => 'Lista revisada por el médico',
        ]);
        $before = $history->fresh()->getRawOriginal();
        $this->repeat($source);
        $this->history($patient)->assertOk();
        $this->assertSame($before, $history->fresh()->getRawOriginal());
    }
}
