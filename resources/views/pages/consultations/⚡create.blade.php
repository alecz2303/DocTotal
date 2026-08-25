<?php

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\ConsultationDiagnosis;
use App\Models\DiagnosisCatalog;
use App\Models\DoctorProfile;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
    #[Layout('layouts::app')]
    #[Title('Consulta | DocTotal')]
    class extends Component
    {
        public Patient $patient;

        public ?Appointment $appointment = null;

        public ?Consultation $consultation = null;

        public string $consultation_at = '';
        public string $reason = '';

        public string $subjective = '';
        public string $objective = '';
        public string $assessment = '';
        public string $plan = '';

        public string $weight_kg = '';
        public string $height_cm = '';
        public string $systolic_bp = '';
        public string $diastolic_bp = '';
        public string $heart_rate = '';
        public string $respiratory_rate = '';
        public string $temperature_c = '';
        public string $oxygen_saturation = '';

        /*
         |--------------------------------------------------------------------------
         | Diagnósticos
         |--------------------------------------------------------------------------
         */

        public bool $showDiagnosisModal = false;

        public ?int $editingDiagnosisId = null;

        public string $diagnosis_code = '';
        public string $diagnosis_description = '';
        public bool $diagnosis_is_primary = false;
        public string $diagnosis_notes = '';
        public string $diagnosisSearch = '';

        public function mount(string $uuid): void
        {
            $this->patient = Patient::query()
                ->where('uuid', $uuid)
                ->firstOrFail();

            $appointmentUuid = request()->query(
                'appointment'
            );

            /*
             * Consulta iniciada desde una cita.
             */
            if ($appointmentUuid) {
                $this->appointment = Appointment::query()
                    ->with('consultation')
                    ->where(
                        'uuid',
                        $appointmentUuid
                    )
                    ->where(
                        'patient_id',
                        $this->patient->id
                    )
                    ->firstOrFail();

                /*
                 * Solo una cita actualmente en atención
                 * puede abrir el formulario clínico.
                 */
                if (
                    $this->appointment->status
                    !== Appointment::STATUS_IN_PROGRESS
                ) {
                    abort(404);
                }

                $this->consultation =
                    $this->appointment->consultation;

                /*
                 * Si ya existe una consulta asociada,
                 * continuamos trabajando con ella.
                 */
                if ($this->consultation) {
                    /*
                     * Una consulta completada nunca vuelve
                     * al formulario editable.
                     */
                    if (! $this->consultation->isDraft()) {
                        $this->redirectRoute(
                            'consultations.show',
                            [
                                'uuid' =>
                                $this->consultation->uuid,
                            ]
                        );

                        return;
                    }

                    $this->fillFromConsultation();

                    /*
                     * Compatibilidad con drafts anteriores
                     * que pudieran no tener copiado todavía
                     * el motivo de la cita.
                     */
                    if (
                        $this->reason === ''
                        && $this->appointment->reason
                    ) {
                        $this->reason =
                            $this->appointment->reason;
                    }

                    return;
                }

                /*
                 * Si por alguna razón la Appointment está
                 * in_progress pero aún no existe el draft,
                 * permitimos continuar.
                 */
                $this->consultation_at =
                    $this->appointment
                    ->starts_at
                    ->format('Y-m-d\TH:i');

                $this->reason =
                    $this->appointment->reason ?? '';

                return;
            }

            /*
             * Consulta iniciada directamente desde
             * el expediente.
             *
             * Si ya existe un draft directo para este
             * paciente, continuamos esa misma consulta.
             */
            $this->consultation = Consultation::query()
                ->where(
                    'patient_id',
                    $this->patient->id
                )
                ->whereNull('appointment_id')
                ->where(
                    'status',
                    Consultation::STATUS_DRAFT
                )
                ->latest('updated_at')
                ->first();

            if ($this->consultation) {
                $this->fillFromConsultation();

                return;
            }

            $this->consultation_at = now()
                ->format('Y-m-d\TH:i');
        }

        /*
         |--------------------------------------------------------------------------
         | Ciclo de la consulta
         |--------------------------------------------------------------------------
         */

        public function leaveConsultation(): void
        {
            $this->persistDraft();

            session()->flash(
                'success',
                'La consulta quedó guardada como borrador.'
            );

            if ($this->appointment) {
                $this->redirectRoute(
                    'appointments.show',
                    [
                        'uuid' =>
                        $this->appointment->uuid,
                    ]
                );

                return;
            }

            $this->redirectRoute(
                'patients.show',
                [
                    'uuid' =>
                    $this->patient->uuid,
                ]
            );
        }

        public function completeConsultation(): void
        {
            $consultation = DB::transaction(
                function (): Consultation {
                    $consultation =
                        $this->persistDraft();

                    $consultation->complete();

                    if ($this->appointment) {
                        $this->appointment->complete();
                    }

                    return $consultation;
                }
            );

            session()->flash(
                'success',
                'La consulta fue finalizada correctamente.'
            );

            $this->redirectRoute(
                'consultations.show',
                [
                    'uuid' =>
                    $consultation->uuid,
                ]
            );
        }

        private function persistDraft(): Consultation
        {
            $validated =
                $this->validatedData();

            $data = [
                'consultation_at' =>
                $validated['consultation_at'],

                'reason' =>
                $this->nullableString(
                    $validated['reason']
                ),

                'subjective' =>
                $this->nullableString(
                    $validated['subjective']
                ),

                'objective' =>
                $this->nullableString(
                    $validated['objective']
                ),

                'assessment' =>
                $this->nullableString(
                    $validated['assessment']
                ),

                'plan' =>
                $this->nullableString(
                    $validated['plan']
                ),

                'weight_kg' =>
                $this->nullableValue(
                    $validated['weight_kg']
                ),

                'height_cm' =>
                $this->nullableValue(
                    $validated['height_cm']
                ),

                'systolic_bp' =>
                $this->nullableValue(
                    $validated['systolic_bp']
                ),

                'diastolic_bp' =>
                $this->nullableValue(
                    $validated['diastolic_bp']
                ),

                'heart_rate' =>
                $this->nullableValue(
                    $validated['heart_rate']
                ),

                'respiratory_rate' =>
                $this->nullableValue(
                    $validated['respiratory_rate']
                ),

                'temperature_c' =>
                $this->nullableValue(
                    $validated['temperature_c']
                ),

                'oxygen_saturation' =>
                $this->nullableValue(
                    $validated['oxygen_saturation']
                ),
            ];

            /*
             * Si ya tenemos un draft, únicamente
             * actualizamos esa misma Consultation.
             */
            if ($this->consultation) {
                if (! $this->consultation->canEdit()) {
                    abort(403);
                }

                $this->consultation->update(
                    $data
                );

                $this->consultation->refresh();

                $this->fillFromConsultation();

                return $this->consultation;
            }

            /*
             * No existe todavía una Consultation.
             */
            $doctor = DoctorProfile::query()
                ->where(
                    'user_id',
                    auth()->id()
                )
                ->firstOrFail();

            $this->consultation =
                Consultation::create([
                    'patient_id' =>
                    $this->patient->id,

                    'doctor_profile_id' =>
                    $doctor->id,

                    'appointment_id' =>
                    $this->appointment?->id,

                    ...$data,

                    'status' =>
                    Consultation::STATUS_DRAFT,
                ]);

            $this->consultation->refresh();

            $this->fillFromConsultation();

            return $this->consultation;
        }

        /*
         |--------------------------------------------------------------------------
         | Diagnósticos
         |--------------------------------------------------------------------------
         */

        public function openDiagnosisModal(): void
        {
            /*
             * ConsultationDiagnosis necesita consultation_id.
             *
             * Si la consulta todavía no existe físicamente,
             * guardamos primero la información actual como draft.
             */
            if (! $this->consultation) {
                $this->persistDraft();
            }

            if (! $this->consultation->canEdit()) {
                abort(403);
            }

            $this->editingDiagnosisId = null;

            $this->resetDiagnosisForm();
            $this->resetValidation();

            $this->showDiagnosisModal = true;
        }

        public function editDiagnosis(
            int $diagnosisId
        ): void {
            if (
                ! $this->consultation
                || ! $this->consultation->canEdit()
            ) {
                abort(403);
            }

            $diagnosis =
                ConsultationDiagnosis::query()
                ->where(
                    'consultation_id',
                    $this->consultation->id
                )
                ->findOrFail($diagnosisId);

            $this->editingDiagnosisId =
                $diagnosis->id;

            $this->diagnosisSearch = '';

            $this->diagnosis_code =
                $diagnosis->code ?? '';

            $this->diagnosis_description =
                $diagnosis->description;

            $this->diagnosis_is_primary =
                $diagnosis->is_primary;

            $this->diagnosis_notes =
                $diagnosis->notes ?? '';

            $this->resetValidation();

            $this->showDiagnosisModal = true;
        }

        public function closeDiagnosisModal(): void
        {
            $this->showDiagnosisModal = false;

            $this->editingDiagnosisId = null;

            $this->resetDiagnosisForm();
            $this->resetValidation();
        }

        private function resetDiagnosisForm(): void
        {
            $this->reset([
                'diagnosisSearch',
                'diagnosis_code',
                'diagnosis_description',
                'diagnosis_is_primary',
                'diagnosis_notes',
            ]);

            unset($this->diagnosisResults);
        }

        private function validateDiagnosis(): array
        {
            return $this->validate([
                'diagnosis_code' => [
                    'nullable',
                    'string',
                    'max:20',
                ],

                'diagnosis_description' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'diagnosis_is_primary' => [
                    'boolean',
                ],

                'diagnosis_notes' => [
                    'nullable',
                    'string',
                    'max:5000',
                ],
            ]);
        }

        public function saveDiagnosis(): void
        {
            if (! $this->consultation) {
                abort(404);
            }

            if (! $this->consultation->canEdit()) {
                abort(403);
            }

            $validated =
                $this->validateDiagnosis();

            $message = DB::transaction(
                function () use ($validated): string {
                    if (
                        $validated['diagnosis_is_primary']
                    ) {
                        ConsultationDiagnosis::query()
                            ->where(
                                'consultation_id',
                                $this->consultation->id
                            )
                            ->when(
                                $this->editingDiagnosisId,
                                fn($query) =>
                                $query->where(
                                    'id',
                                    '!=',
                                    $this->editingDiagnosisId
                                )
                            )
                            ->update([
                                'is_primary' => false,
                            ]);
                    }

                    if ($this->editingDiagnosisId) {
                        $diagnosis =
                            ConsultationDiagnosis::query()
                            ->where(
                                'consultation_id',
                                $this->consultation->id
                            )
                            ->findOrFail(
                                $this->editingDiagnosisId
                            );

                        $diagnosis->update([
                            'code' =>
                            $validated['diagnosis_code'] ?: null,

                            'description' =>
                            $validated['diagnosis_description'],

                            'is_primary' =>
                            $validated['diagnosis_is_primary'],

                            'notes' =>
                            $validated['diagnosis_notes'] ?: null,
                        ]);

                        return
                            'Diagnóstico actualizado correctamente.';
                    }

                    ConsultationDiagnosis::create([
                        'consultation_id' =>
                        $this->consultation->id,

                        'code' =>
                        $validated['diagnosis_code'] ?: null,

                        'description' =>
                        $validated['diagnosis_description'],

                        'is_primary' =>
                        $validated['diagnosis_is_primary'],

                        'notes' =>
                        $validated['diagnosis_notes'] ?: null,
                    ]);

                    return
                        'Diagnóstico registrado correctamente.';
                }
            );

            $this->consultation
                ->unsetRelation('diagnoses');

            $this->showDiagnosisModal = false;

            $this->editingDiagnosisId = null;

            $this->resetDiagnosisForm();

            session()->flash(
                'success',
                $message
            );

            $this->dispatch('diagnosis-saved');
        }

        public function deleteDiagnosis(
            int $diagnosisId
        ): void {
            if (
                ! $this->consultation
                || ! $this->consultation->canEdit()
            ) {
                abort(403);
            }

            $diagnosis =
                ConsultationDiagnosis::query()
                ->where(
                    'consultation_id',
                    $this->consultation->id
                )
                ->findOrFail($diagnosisId);

            $diagnosis->delete();

            $this->consultation
                ->unsetRelation('diagnoses');

            session()->flash(
                'success',
                'Diagnóstico eliminado correctamente.'
            );

            $this->dispatch('diagnosis-saved');
        }

        #[Computed]
        public function diagnosisResults()
        {
            $search = trim(
                $this->diagnosisSearch
            );

            if (mb_strlen($search) < 2) {
                return collect();
            }

            return DiagnosisCatalog::query()
                ->where('active', true)
                ->where(
                    function ($query) use ($search) {
                        $query
                            ->where(
                                'code',
                                'like',
                                $search . '%'
                            )
                            ->orWhere(
                                'description',
                                'like',
                                '%' . $search . '%'
                            );
                    }
                )
                ->orderByRaw(
                    'CASE
                        WHEN code = ? THEN 0
                        WHEN code LIKE ? THEN 1
                        ELSE 2
                    END',
                    [
                        $search,
                        $search . '%',
                    ]
                )
                ->orderBy('description')
                ->limit(10)
                ->get();
        }

        public function selectDiagnosis(
            int $catalogId
        ): void {
            if (
                ! $this->consultation
                || ! $this->consultation->canEdit()
            ) {
                abort(403);
            }

            $diagnosis =
                DiagnosisCatalog::query()
                ->where('active', true)
                ->findOrFail($catalogId);

            $this->diagnosis_code =
                $diagnosis->code;

            $this->diagnosis_description =
                $diagnosis->description;

            $this->diagnosisSearch = '';

            unset($this->diagnosisResults);
        }

        /*
         |--------------------------------------------------------------------------
         | Validación
         |--------------------------------------------------------------------------
         */

        private function validatedData(): array
        {
            return $this->validate([
                'consultation_at' => [
                    'required',
                    'date',
                ],

                'reason' => [
                    'nullable',
                    'string',
                    'max:500',
                ],

                'subjective' => [
                    'nullable',
                    'string',
                    'max:10000',
                ],

                'objective' => [
                    'nullable',
                    'string',
                    'max:10000',
                ],

                'assessment' => [
                    'nullable',
                    'string',
                    'max:10000',
                ],

                'plan' => [
                    'nullable',
                    'string',
                    'max:10000',
                ],

                'weight_kg' => [
                    'nullable',
                    'numeric',
                    'min:0',
                    'max:999.99',
                ],

                'height_cm' => [
                    'nullable',
                    'numeric',
                    'min:0',
                    'max:999.99',
                ],

                'systolic_bp' => [
                    'nullable',
                    'integer',
                    'min:1',
                    'max:300',
                ],

                'diastolic_bp' => [
                    'nullable',
                    'integer',
                    'min:1',
                    'max:200',
                ],

                'heart_rate' => [
                    'nullable',
                    'integer',
                    'min:1',
                    'max:300',
                ],

                'respiratory_rate' => [
                    'nullable',
                    'integer',
                    'min:1',
                    'max:100',
                ],

                'temperature_c' => [
                    'nullable',
                    'numeric',
                    'min:25',
                    'max:50',
                ],

                'oxygen_saturation' => [
                    'nullable',
                    'integer',
                    'min:1',
                    'max:100',
                ],
            ]);
        }

        private function nullableString(
            mixed $value
        ): ?string {
            if ($value === null) {
                return null;
            }

            $value = trim(
                (string) $value
            );

            return $value === ''
                ? null
                : $value;
        }

        private function nullableValue(
            mixed $value
        ): mixed {
            return $value === ''
                || $value === null
                ? null
                : $value;
        }

        private function fillFromConsultation(): void
        {
            $this->consultation_at =
                $this->consultation
                ->consultation_at
                ->format('Y-m-d\TH:i');

            $this->reason =
                $this->consultation->reason
                ?? '';

            $this->subjective =
                $this->consultation->subjective
                ?? '';

            $this->objective =
                $this->consultation->objective
                ?? '';

            $this->assessment =
                $this->consultation->assessment
                ?? '';

            $this->plan =
                $this->consultation->plan
                ?? '';

            $this->weight_kg =
                $this->consultation->weight_kg
                ?? '';

            $this->height_cm =
                $this->consultation->height_cm
                ?? '';

            $this->systolic_bp =
                $this->consultation->systolic_bp
                ? (string)
                $this->consultation
                    ->systolic_bp
                : '';

            $this->diastolic_bp =
                $this->consultation->diastolic_bp
                ? (string)
                $this->consultation
                    ->diastolic_bp
                : '';

            $this->heart_rate =
                $this->consultation->heart_rate
                ? (string)
                $this->consultation
                    ->heart_rate
                : '';

            $this->respiratory_rate =
                $this->consultation->respiratory_rate
                ? (string)
                $this->consultation
                    ->respiratory_rate
                : '';

            $this->temperature_c =
                $this->consultation->temperature_c
                ?? '';

            $this->oxygen_saturation =
                $this->consultation->oxygen_saturation
                ? (string)
                $this->consultation
                    ->oxygen_saturation
                : '';
        }
    };
?>

<div class="mx-auto max-w-5xl">

    {{-- ENCABEZADO --}}
    <div class="mb-8">

        <a
            href="{{ $appointment
                ? route(
                    'appointments.show',
                    [
                        'uuid' =>
                            $appointment->uuid,
                    ]
                )
                : route(
                    'patients.show',
                    [
                        'uuid' =>
                            $patient->uuid,
                    ]
                )
            }}"
            class="text-sm font-medium
                   text-slate-500
                   hover:text-slate-900">
            {{ $appointment
                ? '← Volver a la cita'
                : '← Volver al expediente'
            }}
        </a>

        <h1
            class="mt-3 text-2xl
                   font-bold tracking-tight
                   text-slate-900">
            {{ $consultation
                ? 'Consulta en progreso'
                : 'Nueva consulta'
            }}
        </h1>

        <p class="mt-1 text-sm text-slate-500">
            {{ $patient->first_name }}
            {{ $patient->last_name }}
            {{ $patient->second_last_name }}
        </p>

    </div>


    {{-- CITA ASOCIADA --}}
    @if ($appointment)

    <div
        class="mb-6 rounded-xl
                   border border-orange-200
                   bg-orange-50
                   px-5 py-4">

        <div
            class="flex flex-col gap-2
                       sm:flex-row
                       sm:items-center
                       sm:justify-between">

            <div>

                <p
                    class="text-sm font-semibold
                               text-orange-800">
                    Consulta iniciada desde una cita
                </p>

                <p
                    class="mt-1 text-sm
                               text-orange-700">
                    {{ $appointment->starts_at
                            ->format('d/m/Y H:i') }}
                    —
                    {{ $appointment->ends_at
                            ->format('H:i') }}

                    @if ($appointment->reason)
                    · {{ $appointment->reason }}
                    @endif
                </p>

            </div>

            <span
                class="inline-flex self-start
                           rounded-full
                           bg-orange-100
                           px-3 py-1
                           text-xs font-semibold
                           text-orange-700
                           sm:self-auto">
                En atención
            </span>

        </div>

    </div>

    @endif


    {{-- ESTADO DEL BORRADOR --}}
    @if ($consultation)

    <div
        class="mb-6 rounded-xl
                   border border-slate-200
                   bg-white
                   px-5 py-4">

        <div
            class="flex flex-col gap-2
                       sm:flex-row
                       sm:items-center
                       sm:justify-between">

            <div>

                <p
                    class="text-sm font-semibold
                               text-slate-800">
                    Consulta en progreso
                </p>

                <p
                    class="mt-1 text-xs
                               text-slate-500">
                    Puedes salir y continuar esta
                    consulta posteriormente.
                </p>

            </div>

            <span
                class="inline-flex self-start
                           rounded-full
                           bg-slate-100
                           px-3 py-1
                           text-xs font-semibold
                           text-slate-600
                           sm:self-auto">
                Borrador
            </span>

        </div>

    </div>

    @endif


    <div class="space-y-6">

        {{-- DATOS DE LA CONSULTA --}}
        <section
            class="rounded-xl
                   border border-slate-200
                   bg-white shadow-sm">

            <div
                class="border-b
                       border-slate-200
                       px-6 py-4">
                <h2
                    class="font-semibold
                           text-slate-900">
                    Datos de la consulta
                </h2>
            </div>

            <div
                class="grid gap-5 p-6
                       sm:grid-cols-2">

                <div>

                    <label
                        class="mb-1 block
                               text-sm font-medium">
                        Fecha y hora *
                    </label>

                    <input
                        wire:model="consultation_at"
                        type="datetime-local"
                        class="w-full rounded-lg
                               border border-slate-300
                               px-3 py-2">

                    @error('consultation_at')
                    <p
                        class="mt-1 text-sm
                                   text-red-600">
                        {{ $message }}
                    </p>
                    @enderror

                </div>

                <div>

                    <label
                        class="mb-1 block
                               text-sm font-medium">
                        Motivo de consulta
                    </label>

                    <input
                        wire:model="reason"
                        type="text"
                        placeholder="Ej. Dolor de cabeza"
                        class="w-full rounded-lg
                               border border-slate-300
                               px-3 py-2">

                    @error('reason')
                    <p
                        class="mt-1 text-sm
                                   text-red-600">
                        {{ $message }}
                    </p>
                    @enderror

                </div>

            </div>

        </section>


        {{-- SIGNOS VITALES --}}
        <section
            class="rounded-xl
                   border border-slate-200
                   bg-white shadow-sm">

            <div
                class="border-b
                       border-slate-200
                       px-6 py-4">
                <h2
                    class="font-semibold
                           text-slate-900">
                    Signos vitales
                </h2>
            </div>

            <div
                class="grid gap-5 p-6
                       sm:grid-cols-2
                       lg:grid-cols-4">

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Peso (kg)
                    </label>

                    <input
                        wire:model="weight_kg"
                        type="number"
                        step="0.01"
                        class="w-full rounded-lg
                               border border-slate-300
                               px-3 py-2">

                    @error('weight_kg')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Estatura (cm)
                    </label>

                    <input
                        wire:model="height_cm"
                        type="number"
                        step="0.01"
                        class="w-full rounded-lg
                               border border-slate-300
                               px-3 py-2">

                    @error('height_cm')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Presión sistólica
                    </label>

                    <input
                        wire:model="systolic_bp"
                        type="number"
                        class="w-full rounded-lg
                               border border-slate-300
                               px-3 py-2">

                    @error('systolic_bp')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Presión diastólica
                    </label>

                    <input
                        wire:model="diastolic_bp"
                        type="number"
                        class="w-full rounded-lg
                               border border-slate-300
                               px-3 py-2">

                    @error('diastolic_bp')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Frecuencia cardiaca
                    </label>

                    <input
                        wire:model="heart_rate"
                        type="number"
                        class="w-full rounded-lg
                               border border-slate-300
                               px-3 py-2">

                    @error('heart_rate')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Frecuencia respiratoria
                    </label>

                    <input
                        wire:model="respiratory_rate"
                        type="number"
                        class="w-full rounded-lg
                               border border-slate-300
                               px-3 py-2">

                    @error('respiratory_rate')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Temperatura °C
                    </label>

                    <input
                        wire:model="temperature_c"
                        type="number"
                        step="0.1"
                        class="w-full rounded-lg
                               border border-slate-300
                               px-3 py-2">

                    @error('temperature_c')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Saturación O₂ %
                    </label>

                    <input
                        wire:model="oxygen_saturation"
                        type="number"
                        class="w-full rounded-lg
                               border border-slate-300
                               px-3 py-2">

                    @error('oxygen_saturation')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                    @enderror
                </div>

            </div>

        </section>


        {{-- NOTA CLÍNICA --}}
        <section
            class="rounded-xl
                   border border-slate-200
                   bg-white shadow-sm">

            <div
                class="border-b
                       border-slate-200
                       px-6 py-4">

                <h2
                    class="font-semibold
                           text-slate-900">
                    Nota clínica
                </h2>

                <p
                    class="mt-1 text-sm
                           text-slate-500">
                    Estructura SOAP para documentar
                    la consulta.
                </p>

            </div>

            <div
                class="grid gap-5 p-6
                       sm:grid-cols-2">

                <div>

                    <label class="mb-1 block text-sm font-medium">
                        Subjetivo
                    </label>

                    <textarea
                        wire:model="subjective"
                        rows="6"
                        placeholder="Síntomas, evolución, antecedentes relevantes..."
                        class="w-full rounded-lg
                               border border-slate-300
                               px-3 py-2"></textarea>

                    @error('subjective')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                    @enderror

                </div>

                <div>

                    <label class="mb-1 block text-sm font-medium">
                        Objetivo
                    </label>

                    <textarea
                        wire:model="objective"
                        rows="6"
                        placeholder="Exploración física, hallazgos..."
                        class="w-full rounded-lg
                               border border-slate-300
                               px-3 py-2"></textarea>

                    @error('objective')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                    @enderror

                </div>

                <div>

                    <label class="mb-1 block text-sm font-medium">
                        Evaluación / diagnóstico
                    </label>

                    <textarea
                        wire:model="assessment"
                        rows="6"
                        placeholder="Impresión diagnóstica..."
                        class="w-full rounded-lg
                               border border-slate-300
                               px-3 py-2"></textarea>

                    @error('assessment')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                    @enderror

                </div>

                <div>

                    <label class="mb-1 block text-sm font-medium">
                        Plan
                    </label>

                    <textarea
                        wire:model="plan"
                        rows="6"
                        placeholder="Tratamiento, estudios, seguimiento..."
                        class="w-full rounded-lg
                               border border-slate-300
                               px-3 py-2"></textarea>

                    @error('plan')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                    @enderror

                </div>

            </div>

        </section>


        {{-- DIAGNÓSTICOS --}}
        <section
            id="diagnosticos"
            class="rounded-xl
                   border border-slate-200
                   bg-white shadow-sm">

            <div
                class="flex flex-col gap-3
                       border-b border-slate-200
                       px-6 py-4
                       sm:flex-row
                       sm:items-center
                       sm:justify-between">

                <div>

                    <h2
                        class="font-semibold
                               text-slate-900">
                        Diagnósticos
                    </h2>

                    <p
                        class="mt-1 text-sm
                               text-slate-500">
                        Registra los diagnósticos asociados
                        a esta atención.
                    </p>

                </div>

                <button
                    type="button"
                    wire:click="openDiagnosisModal"
                    wire:loading.attr="disabled"
                    wire:target="openDiagnosisModal"
                    class="inline-flex items-center
                           justify-center
                           rounded-lg
                           border border-slate-300
                           px-3 py-2
                           text-sm font-semibold
                           text-slate-700
                           hover:bg-slate-50
                           disabled:opacity-50">

                    <span
                        wire:loading.remove
                        wire:target="openDiagnosisModal">
                        + Agregar diagnóstico
                    </span>

                    <span
                        wire:loading
                        wire:target="openDiagnosisModal">
                        Preparando...
                    </span>

                </button>

            </div>


            <div>

                @forelse (
                $consultation?->diagnoses ?? collect()
                as $diagnosis
                )

                <div
                    class="flex flex-col gap-3
                               border-b
                               border-slate-100
                               px-6 py-5
                               last:border-0
                               sm:flex-row
                               sm:items-start
                               sm:justify-between">

                    <div>

                        <div
                            class="flex flex-wrap
                                       items-center gap-2">

                            @if ($diagnosis->is_primary)

                            <span
                                class="rounded-full
                                               bg-slate-900
                                               px-2 py-0.5
                                               text-xs font-medium
                                               text-white">
                                Principal
                            </span>

                            @endif

                            @if ($diagnosis->code)

                            <span
                                class="rounded-full
                                               bg-slate-100
                                               px-2 py-0.5
                                               text-xs font-medium
                                               text-slate-600">
                                {{ $diagnosis->code }}
                            </span>

                            @endif

                        </div>

                        <p
                            class="mt-2 font-medium
                                       text-slate-900">
                            {{ $diagnosis->description }}
                        </p>

                        @if ($diagnosis->notes)

                        <p
                            class="mt-2
                                           whitespace-pre-line
                                           text-sm
                                           text-slate-500">
                            {{ $diagnosis->notes }}
                        </p>

                        @endif

                    </div>


                    <div
                        class="flex items-center
                                   gap-3">

                        <button
                            type="button"
                            wire:click="editDiagnosis({{ $diagnosis->id }})"
                            class="text-xs
                                       font-semibold
                                       text-slate-600
                                       hover:text-slate-900">
                            Editar
                        </button>

                        <button
                            type="button"
                            x-data
                            x-on:click="
                                    Swal.fire({
                                        title: '¿Eliminar diagnóstico?',
                                        text: 'Esta acción no se puede deshacer.',
                                        icon: 'warning',
                                        showCancelButton: true,
                                        confirmButtonText: 'Sí, eliminar',
                                        cancelButtonText: 'Cancelar'
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            $wire.deleteDiagnosis({{ $diagnosis->id }})
                                        }
                                    })
                                "
                            class="text-xs
                                       font-semibold
                                       text-red-600
                                       hover:text-red-700">
                            Eliminar
                        </button>

                    </div>

                </div>

                @empty

                <div
                    class="px-6 py-10
                               text-center">

                    <p
                        class="font-medium
                                   text-slate-700">
                        Sin diagnósticos registrados
                    </p>

                    <p
                        class="mt-1 text-sm
                                   text-slate-500">
                        Puedes agregar diagnósticos antes
                        de finalizar la consulta.
                    </p>

                </div>

                @endforelse

            </div>

        </section>


        {{-- ACCIONES --}}
        <div
            class="flex flex-col-reverse
                   gap-3
                   sm:flex-row
                   sm:justify-end">

            <button
                type="button"
                wire:click="leaveConsultation"
                wire:loading.attr="disabled"
                wire:target="leaveConsultation"
                class="rounded-lg
                       border border-slate-300
                       px-4 py-2.5
                       text-sm font-medium
                       text-slate-700
                       hover:bg-slate-50
                       disabled:opacity-50">

                <span
                    wire:loading.remove
                    wire:target="leaveConsultation">
                    Salir
                </span>

                <span
                    wire:loading
                    wire:target="leaveConsultation">
                    Guardando...
                </span>

            </button>


            <button
                type="button"
                x-data
                x-on:click="
                    Swal.fire({
                        title: '¿Finalizar consulta?',
                        text: 'La consulta quedará cerrada y formará parte del historial clínico. Los datos clínicos y diagnósticos ya no podrán modificarse.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, finalizar consulta',
                        cancelButtonText: 'Seguir editando',
                        reverseButtons: true,
                        focusCancel: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $wire.completeConsultation()
                        }
                    })
                "
                wire:loading.attr="disabled"
                wire:target="completeConsultation"
                class="rounded-lg
                    bg-slate-900
                    px-5 py-2.5
                    text-sm font-semibold
                    text-white
                    hover:bg-slate-800
                    disabled:opacity-50">

                <span
                    wire:loading.remove
                    wire:target="completeConsultation">
                    Finalizar consulta
                </span>

                <span
                    wire:loading
                    wire:target="completeConsultation">
                    Finalizando...
                </span>

            </button>

        </div>

    </div>


    {{-- MODAL DIAGNÓSTICO --}}
    @if ($showDiagnosisModal)

    <div
        class="fixed inset-0 z-50
                   flex items-center
                   justify-center
                   bg-slate-950/50
                   p-4">

        <div
            class="max-h-[90vh]
                       w-full max-w-xl
                       overflow-y-auto
                       rounded-2xl
                       bg-white shadow-xl">

            <div
                class="flex items-center
                           justify-between
                           border-b
                           border-slate-200
                           px-6 py-4">

                <div>

                    <h2
                        class="text-lg
                                   font-semibold
                                   text-slate-900">
                        {{ $editingDiagnosisId
                                ? 'Editar diagnóstico'
                                : 'Nuevo diagnóstico'
                            }}
                    </h2>

                    <p
                        class="mt-1 text-sm
                                   text-slate-500">
                        Registra un diagnóstico
                        asociado a esta consulta.
                    </p>

                </div>

                <button
                    type="button"
                    wire:click="closeDiagnosisModal"
                    class="text-2xl
                               leading-none
                               text-slate-400
                               hover:text-slate-700">
                    ×
                </button>

            </div>


            <form wire:submit="saveDiagnosis">

                <div class="space-y-5 p-6">

                    {{-- AUTOCOMPLETE CIE-10 --}}
                    <div class="relative">

                        <label
                            class="mb-1 block
                                       text-sm font-medium">
                            Buscar en catálogo CIE-10
                        </label>

                        <input
                            wire:model.live.debounce.300ms="diagnosisSearch"
                            type="search"
                            autocomplete="off"
                            placeholder="Escribe código o diagnóstico, ej. cefalea..."
                            class="w-full
                                       rounded-lg
                                       border border-slate-300
                                       px-3 py-2">

                        @if (
                        mb_strlen(
                        trim($diagnosisSearch)
                        ) >= 2
                        )

                        <div
                            class="absolute z-20
                                           mt-1 max-h-72
                                           w-full
                                           overflow-y-auto
                                           rounded-lg
                                           border
                                           border-slate-200
                                           bg-white
                                           shadow-lg">

                            @forelse (
                            $this->diagnosisResults
                            as $result
                            )

                            <button
                                type="button"
                                wire:click="selectDiagnosis({{ $result->id }})"
                                class="block w-full
                                                   border-b
                                                   border-slate-100
                                                   px-4 py-3
                                                   text-left
                                                   last:border-0
                                                   hover:bg-slate-50">

                                <div
                                    class="flex
                                                       items-start
                                                       gap-3">

                                    <span
                                        class="shrink-0
                                                           rounded
                                                           bg-slate-100
                                                           px-2 py-1
                                                           text-xs
                                                           font-semibold
                                                           text-slate-700">
                                        {{ $result->code }}
                                    </span>

                                    <span
                                        class="text-sm
                                                           text-slate-700">
                                        {{ $result->description }}
                                    </span>

                                </div>

                            </button>

                            @empty

                            <div class="px-4 py-4">

                                <p
                                    class="text-sm
                                                       font-medium
                                                       text-slate-700">
                                    Sin coincidencias
                                </p>

                                <p
                                    class="mt-1
                                                       text-xs
                                                       text-slate-500">
                                    Puedes capturar el
                                    diagnóstico manualmente.
                                </p>

                            </div>

                            @endforelse

                        </div>

                        @endif

                    </div>


                    {{-- CÓDIGO --}}
                    <div>

                        <label
                            class="mb-1 block
                                       text-sm font-medium">
                            Código CIE-10
                        </label>

                        <input
                            wire:model="diagnosis_code"
                            type="text"
                            placeholder="Ej. R51.9"
                            class="w-full
                                       rounded-lg
                                       border border-slate-300
                                       px-3 py-2">

                        @error('diagnosis_code')
                        <p
                            class="mt-1
                                           text-sm
                                           text-red-600">
                            {{ $message }}
                        </p>
                        @enderror

                    </div>


                    {{-- DESCRIPCIÓN --}}
                    <div>

                        <label
                            class="mb-1 block
                                       text-sm font-medium">
                            Descripción *
                        </label>

                        <input
                            wire:model="diagnosis_description"
                            type="text"
                            placeholder="Ej. Cefalea no especificada"
                            class="w-full
                                       rounded-lg
                                       border border-slate-300
                                       px-3 py-2">

                        @error('diagnosis_description')
                        <p
                            class="mt-1
                                           text-sm
                                           text-red-600">
                            {{ $message }}
                        </p>
                        @enderror

                    </div>


                    {{-- NOTAS --}}
                    <div>

                        <label
                            class="mb-1 block
                                       text-sm font-medium">
                            Notas
                        </label>

                        <textarea
                            wire:model="diagnosis_notes"
                            rows="4"
                            class="w-full
                                       rounded-lg
                                       border border-slate-300
                                       px-3 py-2"></textarea>

                        @error('diagnosis_notes')
                        <p
                            class="mt-1
                                           text-sm
                                           text-red-600">
                            {{ $message }}
                        </p>
                        @enderror

                    </div>


                    {{-- PRINCIPAL --}}
                    <label
                        class="flex
                                   items-center
                                   gap-3">

                        <input
                            wire:model="diagnosis_is_primary"
                            type="checkbox">

                        <span
                            class="text-sm
                                       font-medium
                                       text-slate-700">
                            Marcar como diagnóstico principal
                        </span>

                    </label>

                </div>


                {{-- ACCIONES MODAL --}}
                <div
                    class="flex justify-end
                               gap-3
                               border-t
                               border-slate-200
                               px-6 py-4">

                    <button
                        type="button"
                        wire:click="closeDiagnosisModal"
                        class="rounded-lg
                                   border border-slate-300
                                   px-4 py-2
                                   text-sm font-medium
                                   text-slate-700
                                   hover:bg-slate-50">
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="saveDiagnosis"
                        class="rounded-lg
                                   bg-slate-900
                                   px-4 py-2
                                   text-sm font-semibold
                                   text-white
                                   disabled:opacity-50">

                        <span
                            wire:loading.remove
                            wire:target="saveDiagnosis">
                            {{ $editingDiagnosisId
                                    ? 'Guardar cambios'
                                    : 'Guardar diagnóstico'
                                }}
                        </span>

                        <span
                            wire:loading
                            wire:target="saveDiagnosis">
                            Guardando...
                        </span>

                    </button>

                </div>

            </form>

        </div>

    </div>

    @endif


    @script
    <script>
        $wire.on('diagnosis-saved', () => {
            requestAnimationFrame(() => {
                document
                    .getElementById('diagnosticos')
                    ?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
            });
        });
    </script>
    @endscript

</div>