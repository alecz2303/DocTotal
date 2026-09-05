<?php

namespace App\Actions\Prescriptions;

use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\Prescription;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RepeatPrescription
{
    public function authorize(): DoctorProfile
    {
        $user = auth()->user();
        $tenant = app(TenantContext::class)->get();
        abort_unless($user && $tenant && (int) $user->tenant_id === (int) $tenant->id, 403);
        abort_unless($user->hasVerifiedEmail(), 403);
        $tenant = $tenant->fresh();
        abort_unless($tenant && $tenant->onboarding_completed_at && $tenant->hasAccessToService(), 403);

        return DoctorProfile::query()->where('user_id', $user->id)->firstOrFail();
    }

    public function source(string $uuid, ?int $patientId = null, bool $lock = false): Prescription
    {
        $this->authorize();
        $query = Prescription::query()->where('uuid', $uuid)->where('status', 'active');
        if ($patientId !== null) {
            $query->where('patient_id', $patientId);
        }
        if ($lock) {
            $query->lockForUpdate();
        }
        $source = $query->firstOrFail();
        // Patient lookups must also honor tenant scope and soft deletion.
        $source->setRelation('patient', Patient::query()->findOrFail($source->patient_id));
        $source->load('items');

        return $source;
    }

    public function execute(string $sourceUuid, int $patientId, array $data): Prescription
    {
        $doctor = $this->authorize();
        $validated = Validator::make($data, [
            'prescribed_at' => ['required', 'date'],
            'general_instructions' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*' => ['required', 'array'],
            'items.*.medication_name' => ['required', 'string', 'max:255'],
            'items.*.presentation' => ['nullable', 'string', 'max:255'],
            'items.*.dose' => ['nullable', 'string', 'max:255'],
            'items.*.frequency' => ['nullable', 'string', 'max:255'],
            'items.*.duration' => ['nullable', 'string', 'max:255'],
            'items.*.instructions' => ['nullable', 'string', 'max:5000'],
        ], [
            'required' => 'Este campo es obligatorio.',
            'date' => 'Ingresa una fecha y hora válidas.',
            'string' => 'Este campo debe contener texto.',
            'items.min' => 'Agrega al menos un medicamento.',
            'items.max' => 'La receta admite hasta 100 medicamentos.',
            'max.string' => 'Este campo supera el límite de :max caracteres.',
        ])->validate();

        $new = DB::transaction(function () use ($sourceUuid, $patientId, $doctor, $validated) {
            $source = $this->source($sourceUuid, $patientId, lock: true);
            $new = Prescription::create([
                'patient_id' => $source->patient_id,
                'doctor_profile_id' => $doctor->id,
                'source_prescription_id' => $source->id,
                'consultation_id' => null,
                'prescribed_at' => $validated['prescribed_at'],
                'general_instructions' => $validated['general_instructions'] ?? null,
                'status' => 'active',
            ]);
            foreach (array_values($validated['items']) as $index => $item) {
                // Explicit allowlist: never reuse IDs, tenant, or ownership from the browser.
                $new->items()->create([
                    'medication_name' => $item['medication_name'],
                    'presentation' => $item['presentation'] ?? null,
                    'dose' => $item['dose'] ?? null,
                    'frequency' => $item['frequency'] ?? null,
                    'duration' => $item['duration'] ?? null,
                    'instructions' => $item['instructions'] ?? null,
                    'sort_order' => $index + 1,
                ]);
            }

            return $new;
        });

        app(AuditLogger::class)->safeLog(
            action: 'prescription.repeated',
            auditable: $new,
            description: 'Receta creada a partir de una receta anterior.',
            metadata: ['source_prescription_id' => $new->source_prescription_id],
        );

        return $new;
    }
}
