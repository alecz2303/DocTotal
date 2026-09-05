<?php

namespace Tests\Feature\Prescriptions;

use App\Actions\Prescriptions\RepeatPrescription;
use App\Models\AuditEvent;
use App\Models\Consultation;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class PrescriptionRepeatTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $suffix = (string) Str::uuid();
        $tenant = Tenant::create([
            'name' => 'Consultorio '.$suffix, 'slug' => $suffix,
            'trial_started_at' => now(), 'trial_ends_at' => now()->addDays(30),
            'onboarding_completed_at' => now(),
        ]);
        app(TenantContext::class)->set($tenant);
        $user = User::create([
            'tenant_id' => $tenant->id, 'name' => 'Médico',
            'email' => $suffix.'@example.com', 'password' => 'password123', 'role' => 'owner',
        ]);
        $user->forceFill(['email_verified_at' => now()])->saveQuietly();
        $doctor = DoctorProfile::create(['user_id' => $user->id, 'first_name' => 'Doctor', 'last_name' => 'Test']);
        $patient = Patient::create(['first_name' => 'Paciente', 'last_name' => 'Test']);
        $consultation = Consultation::create([
            'patient_id' => $patient->id, 'doctor_profile_id' => $doctor->id,
            'consultation_at' => now()->subDays(30), 'status' => Consultation::STATUS_COMPLETED,
            'completed_at' => now()->subDays(30),
        ]);
        $source = Prescription::create([
            'patient_id' => $patient->id, 'doctor_profile_id' => $doctor->id,
            'consultation_id' => $consultation->id, 'prescribed_at' => now()->subDays(30),
            'general_instructions' => 'Indicaciones históricas', 'status' => 'active',
        ]);
        $source->items()->create([
            'medication_name' => 'Medicamento A', 'presentation' => 'Presentación A',
            'dose' => 'Dosis A', 'frequency' => 'Frecuencia A', 'duration' => 'Duración A',
            'instructions' => 'Indicaciones A', 'sort_order' => 1,
        ]);
        return [$tenant, $user, $doctor, $patient, $source];
    }

    private function form(User $user, Prescription $source)
    {
        $this->actingAs($user);
        return Livewire::test('pages::prescriptions.repeat', ['uuid' => $source->uuid]);
    }

    private function payload(): array
    {
        return ['prescribed_at' => now()->format('Y-m-d H:i:s'), 'items' => [['medication_name' => 'Nuevo']]];
    }

    private function assertSaveRejectsMissingModel($form, string $model): void
    {
        $itemsBefore = \Illuminate\Support\Facades\DB::table('prescription_items')->count();
        try {
            $form->call('savePrescription');
            $this->fail('Expected ModelNotFoundException during save.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $exception) {
            $this->assertSame($model, $exception->getModel());
        }
        $this->assertDatabaseCount('prescriptions', 1);
        $this->assertDatabaseCount('prescription_items', $itemsBefore);
        $this->assertSame(0, AuditEvent::where('action', 'prescription.repeated')->count());
    }

    public function test_opening_form_copies_fields_without_writing(): void
    {
        $this->freezeTime();
        [, $user, , $patient, $source] = $this->context();
        $this->form($user, $source)
            ->assertSet('patientId', $patient->id)
            ->assertSet('prescribed_at', now()->format('Y-m-d\TH:i'))
            ->assertSet('general_instructions', 'Indicaciones históricas')
            ->assertSet('items.0.medication_name', 'Medicamento A')
            ->assertSet('items.0.presentation', 'Presentación A')
            ->assertSet('items.0.dose', 'Dosis A')
            ->assertSet('items.0.frequency', 'Frecuencia A')
            ->assertSet('items.0.duration', 'Duración A')
            ->assertSet('items.0.instructions', 'Indicaciones A');
        $this->assertDatabaseCount('prescriptions', 1);
        $this->assertDatabaseCount('prescription_items', 1);
    }

    public function test_save_creates_independent_records_and_keeps_source_byte_for_byte(): void
    {
        [$tenant, $user, $doctor, $patient, $source] = $this->context();
        $before = $source->fresh()->getRawOriginal();
        $itemBefore = $source->items()->firstOrFail()->getRawOriginal();
        $this->form($user, $source)->call('savePrescription')->assertHasNoErrors();
        $new = Prescription::where('id', '!=', $source->id)->firstOrFail();
        $this->assertNotSame($source->uuid, $new->uuid);
        $this->assertSame($source->id, $new->source_prescription_id);
        $this->assertSame($patient->id, $new->patient_id);
        $this->assertSame($tenant->id, $new->tenant_id);
        $this->assertSame($doctor->id, $new->doctor_profile_id);
        $this->assertNull($new->consultation_id);
        $this->assertSame('active', $new->status);
        $this->assertTrue($new->prescribed_at->greaterThan($source->prescribed_at));
        $this->assertNotSame($itemBefore['id'], $new->items->first()->id);
        $this->assertSame($tenant->id, $new->items->first()->tenant_id);
        $this->assertSame($before, $source->fresh()->getRawOriginal());
        $this->assertSame($itemBefore, $source->items()->first()->getRawOriginal());
    }

    public function test_every_treatment_field_can_be_edited_before_save(): void
    {
        [, $user, , , $source] = $this->context();
        $form = $this->form($user, $source)->set('general_instructions', 'Nuevas indicaciones');
        foreach (['medication_name', 'presentation', 'dose', 'frequency', 'duration', 'instructions'] as $field) {
            $form->set('items.0.'.$field, 'Nuevo '.$field);
        }
        $form->call('savePrescription')->assertHasNoErrors();
        $new = Prescription::where('source_prescription_id', $source->id)->firstOrFail();
        foreach (['medication_name', 'presentation', 'dose', 'frequency', 'duration', 'instructions'] as $field) {
            $this->assertSame('Nuevo '.$field, $new->items->first()->{$field});
        }
        $this->assertSame('Nuevas indicaciones', $new->general_instructions);
        $this->assertSame('Medicamento A', $source->items()->first()->medication_name);
    }

    public function test_rows_can_be_added_and_removed(): void
    {
        [, $user, , , $source] = $this->context();
        $this->form($user, $source)->call('addMedication')
            ->set('items.1.medication_name', 'Medicamento B')->call('removeMedication', 0)
            ->assertCount('items', 1)->call('savePrescription')->assertHasNoErrors();
        $new = Prescription::where('source_prescription_id', $source->id)->firstOrFail();
        $this->assertCount(1, $new->items);
        $this->assertSame('Medicamento B', $new->items->first()->medication_name);
        $this->assertEquals(1, $new->items->first()->sort_order);
        $this->assertSame('Medicamento A', $source->items()->first()->medication_name);
    }

    public function test_validation_does_not_leave_partial_records(): void
    {
        [, $user, , , $source] = $this->context();
        $this->form($user, $source)->set('prescribed_at', '')->set('items.0.medication_name', '')
            ->call('savePrescription')->assertHasErrors(['prescribed_at', 'items.0.medication_name']);
        $this->assertDatabaseCount('prescriptions', 1);
        $this->assertDatabaseCount('prescription_items', 1);
    }

    public function test_empty_items_are_rejected(): void
    {
        [, $user, , , $source] = $this->context();
        $this->form($user, $source)->set('items', [])->call('savePrescription')->assertHasErrors(['items']);
        $this->assertDatabaseCount('prescriptions', 1);
    }

    public function test_browser_supplied_ids_are_never_reused(): void
    {
        [, $user, , $patient, $source] = $this->context();
        $this->actingAs($user);
        $data = $this->payload();
        $data['uuid'] = $source->uuid;
        $data['status'] = 'cancelled';
        $data['patient_id'] = 99999;
        $data['consultation_id'] = $source->consultation_id;
        $data['items'][0] += ['id' => $source->items->first()->id, 'tenant_id' => 99999, 'prescription_id' => $source->id];
        $new = app(RepeatPrescription::class)->execute($source->uuid, $patient->id, $data);
        $this->assertSame($patient->id, $new->patient_id);
        $this->assertSame('active', $new->status);
        $this->assertNull($new->consultation_id);
        $this->assertNotSame($source->uuid, $new->uuid);
        $this->assertSame($new->id, $new->items->first()->prescription_id);
        $this->assertSame($new->tenant_id, $new->items->first()->tenant_id);
    }

    public function test_same_patient_is_required_by_backend(): void
    {
        [, $user, , , $source] = $this->context();
        $other = Patient::create(['first_name' => 'Otro', 'last_name' => 'Paciente']);
        $this->actingAs($user);
        try {
            app(RepeatPrescription::class)->execute($source->uuid, $other->id, $this->payload());
            $this->fail('Expected patient mismatch rejection.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $exception) {
            $this->assertDatabaseCount('prescriptions', 1);
        }
    }

    public function test_locked_patient_cannot_be_changed_in_livewire(): void
    {
        [, $user, , , $source] = $this->context();
        $form = $this->form($user, $source);
        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);
        $form->set('patientId', 99999);
    }

    public function test_locked_source_cannot_be_changed_in_livewire(): void
    {
        [, $user, , , $source] = $this->context();
        $form = $this->form($user, $source);
        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);
        $form->set('sourceUuid', (string) Str::uuid());
    }

    public function test_foreign_source_is_not_accessible(): void
    {
        [$tenant, $user] = $this->context();
        [, , , , $foreign] = $this->context();
        app(TenantContext::class)->set($tenant);
        $this->actingAs($user)->get(route('prescriptions.repeat', ['uuid' => $foreign->uuid]))->assertNotFound();
        $this->assertDatabaseCount('prescriptions', 2);
    }

    public function test_foreign_source_cannot_be_saved_by_direct_action(): void
    {
        [$tenant, $user] = $this->context();
        [, , , $patient, $foreign] = $this->context();
        app(TenantContext::class)->set($tenant);
        $this->actingAs($user);
        try {
            app(RepeatPrescription::class)->execute($foreign->uuid, $patient->id, $this->payload());
            $this->fail('Expected tenant isolation.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $exception) {
            $this->assertDatabaseCount('prescriptions', 2);
        }
    }

    public function test_cancelled_source_cannot_be_opened(): void
    {
        [, $user, , , $source] = $this->context();
        $source->update(['status' => 'cancelled']);
        $this->actingAs($user)->get(route('prescriptions.repeat', ['uuid' => $source->uuid]))->assertNotFound();
    }

    public function test_source_is_revalidated_when_cancelled_after_form_open(): void
    {
        [, $user, , , $source] = $this->context();
        $form = $this->form($user, $source);
        $source->update(['status' => 'cancelled']);
        $this->assertSaveRejectsMissingModel($form, Prescription::class);
        $this->assertDatabaseCount('prescriptions', 1);
    }

    public function test_deleted_source_is_rejected_at_save(): void
    {
        [, $user, , , $source] = $this->context();
        $form = $this->form($user, $source);
        $source->delete();
        $this->assertSaveRejectsMissingModel($form, Prescription::class);
        $this->assertDatabaseCount('prescriptions', 1);
    }

    public function test_suspended_tenant_is_rejected_even_after_form_open(): void
    {
        [$tenant, $user, , , $source] = $this->context();
        $form = $this->form($user, $source);
        $tenant->update(['status' => 'suspended']);
        $form->call('savePrescription')->assertForbidden();
        $this->assertDatabaseCount('prescriptions', 1);
    }

    public function test_guest_cannot_open_repeat_route(): void
    {
        [, , , , $source] = $this->context();
        $this->get(route('prescriptions.repeat', ['uuid' => $source->uuid]))->assertRedirect(route('login'));
    }

    public function test_doctor_profile_is_required(): void
    {
        [, $user, $doctor, , $source] = $this->context();
        $doctor->delete();
        $this->actingAs($user)->get(route('prescriptions.repeat', ['uuid' => $source->uuid]))->assertNotFound();
    }

    public function test_new_prescription_uses_current_doctor_not_source_doctor(): void
    {
        [$tenant, , $originalDoctor, $patient, $source] = $this->context();
        $user = User::create(['tenant_id' => $tenant->id, 'name' => 'Otro médico', 'email' => 'other@example.com', 'password' => 'password123', 'role' => 'owner']);
        $doctor = DoctorProfile::create(['user_id' => $user->id, 'first_name' => 'Otro', 'last_name' => 'Médico']);
        $this->actingAs($user);
        $new = app(RepeatPrescription::class)->execute($source->uuid, $patient->id, $this->payload());
        $this->assertSame($doctor->id, $new->doctor_profile_id);
        $this->assertSame($originalDoctor->id, $source->fresh()->doctor_profile_id);
    }

    public function test_audit_contains_reference_but_no_treatment_payload(): void
    {
        [, $user, , , $source] = $this->context();
        $this->form($user, $source)->call('savePrescription')->assertHasNoErrors();
        $event = AuditEvent::where('action', 'prescription.repeated')->firstOrFail();
        $this->assertSame(['source_prescription_id' => $source->id], $event->metadata);
        $this->assertStringNotContainsString('Medicamento A', json_encode($event->toArray()));
    }

    public function test_detail_has_repeat_button_and_origin_link(): void
    {
        [, $user, , $patient, $source] = $this->context();
        $this->actingAs($user)->get(route('prescriptions.show', ['uuid' => $source->uuid]))
            ->assertOk()->assertSee('Repetir receta');
        $new = app(RepeatPrescription::class)->execute($source->uuid, $patient->id, $this->payload());
        $this->get(route('prescriptions.show', ['uuid' => $new->uuid]))
            ->assertOk()->assertSee('Ver receta origen')
            ->assertSee(route('prescriptions.show', ['uuid' => $source->uuid]), false);
    }

    public function test_item_failure_rolls_back_entire_copy(): void
    {
        [, $user, , $patient, $source] = $this->context();
        $this->actingAs($user);
        $data = $this->payload();
        $data['items'][] = ['medication_name' => 'Forzar fallo'];
        $dispatcher = PrescriptionItem::getEventDispatcher();
        PrescriptionItem::setEventDispatcher(clone $dispatcher);
        PrescriptionItem::creating(function (PrescriptionItem $item) {
            if ($item->medication_name === 'Forzar fallo') {
                throw new \RuntimeException('Fallo simulado');
            }
        });
        try {
            app(RepeatPrescription::class)->execute($source->uuid, $patient->id, $data);
            $this->fail('Expected simulated persistence failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Fallo simulado', $exception->getMessage());
        } finally {
            PrescriptionItem::setEventDispatcher($dispatcher);
        }
        $this->assertDatabaseCount('prescriptions', 1);
        $this->assertDatabaseCount('prescription_items', 1);
        $this->assertSame(0, AuditEvent::where('action', 'prescription.repeated')->count());
    }

    public function test_source_patient_is_revalidated_at_save(): void
    {
        [, $user, , , $source] = $this->context();
        $form = $this->form($user, $source);
        $other = Patient::create(['first_name' => 'Otro', 'last_name' => 'Paciente']);
        $source->update(['patient_id' => $other->id]);
        $this->assertSaveRejectsMissingModel($form, Prescription::class);
        $this->assertDatabaseCount('prescriptions', 1);
    }

    public function test_deleted_patient_is_rejected_at_save(): void
    {
        [, $user, , $patient, $source] = $this->context();
        $form = $this->form($user, $source);
        $patient->delete();
        $this->assertSaveRejectsMissingModel($form, Patient::class);
        $this->assertDatabaseCount('prescriptions', 1);
    }

    public function test_unverified_user_cannot_open_repeat_route(): void
    {
        [, $user, , , $source] = $this->context();
        $this->preserveUnverifiedUsers = true;
        $user->forceFill(['email_verified_at' => null])->saveQuietly();
        $this->actingAs($user)->get(route('prescriptions.repeat', ['uuid' => $source->uuid]))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_editing_and_cancelling_copy_does_not_change_source(): void
    {
        [, $user, , $patient, $source] = $this->context();
        $before = $source->fresh()->getRawOriginal();
        $itemBefore = $source->items()->first()->getRawOriginal();
        $this->actingAs($user);
        $new = app(RepeatPrescription::class)->execute($source->uuid, $patient->id, $this->payload());
        Livewire::test('pages::prescriptions.edit', ['uuid' => $new->uuid])
            ->set('items.0.medication_name', 'Editado')->call('updatePrescription')->assertHasNoErrors();
        Livewire::test('pages::prescriptions.show', ['uuid' => $new->uuid])->call('cancelPrescription');
        $this->assertSame('cancelled', $new->fresh()->status);
        $this->assertSame('Editado', $new->items()->first()->medication_name);
        $this->assertSame($before, $source->fresh()->getRawOriginal());
        $this->assertSame($itemBefore, $source->items()->first()->getRawOriginal());
    }
}
