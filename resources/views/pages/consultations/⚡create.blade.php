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

<div class="mx-auto max-w-6xl">

    {{-- HEADER --}}
    <div class="mb-6">
        <a
            href="{{ $appointment
                ? route('appointments.show', ['uuid' => $appointment->uuid])
                : route('patients.show', ['uuid' => $patient->uuid])
            }}"
            class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-blue-600">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" class="h-4 w-4">
                <path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            {{ $appointment ? 'Volver a la cita' : 'Volver al expediente' }}
        </a>

        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-violet-600 text-white shadow-sm shadow-blue-200">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-6 w-6">
                        <path d="M8 3h8M9 3v3h6V3M6 5h12a2 2 0 0 1 2 2v13H4V7a2 2 0 0 1 2-2Z" />
                        <path d="M8 11h8M8 15h5" stroke-linecap="round" />
                    </svg>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-bold tracking-tight text-slate-950">
                            {{ $consultation ? 'Consulta en progreso' : 'Nueva consulta' }}
                        </h1>
                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-bold text-amber-700 ring-1 ring-inset ring-amber-100">
                            Borrador
                        </span>
                    </div>
                    <p class="mt-1 text-sm font-medium text-slate-600">
                        {{ $patient->first_name }}
                        {{ $patient->last_name }}
                        {{ $patient->second_last_name }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- APPOINTMENT CONTEXT --}}
    @if ($appointment)
    <div class="mb-6 overflow-hidden rounded-2xl border border-orange-200 bg-gradient-to-r from-orange-50 to-amber-50">
        <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-orange-100 text-orange-600">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4.5 w-4.5">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M12 7v5l3 2" stroke-linecap="round" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-orange-900">Consulta iniciada desde una cita</p>
                    <p class="mt-0.5 text-sm text-orange-700">
                        {{ $appointment->starts_at->format('d/m/Y H:i') }}
                        – {{ $appointment->ends_at->format('H:i') }}
                        @if ($appointment->reason) · {{ $appointment->reason }} @endif
                    </p>
                </div>
            </div>
            <span class="self-start rounded-full bg-orange-100 px-3 py-1 text-xs font-bold text-orange-700 sm:self-auto">
                En atención
            </span>
        </div>
    </div>
    @endif

    @if ($consultation)
    <div class="mb-6 flex items-start gap-3 rounded-2xl border border-blue-100 bg-blue-50/60 px-5 py-4">
        <div class="mt-0.5 text-blue-600">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                <path d="M5 4h14v16H5z" />
                <path d="M8 8h8M8 12h8M8 16h5" stroke-linecap="round" />
            </svg>
        </div>
        <div>
            <p class="text-sm font-semibold text-blue-900">Borrador guardado</p>
            <p class="mt-0.5 text-xs text-blue-700">Puedes salir y continuar esta consulta posteriormente.</p>
        </div>
    </div>
    @endif

    <div class="space-y-6">

        {{-- CONSULTATION DATA --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4.5 w-4.5">
                        <rect x="3" y="5" width="18" height="16" rx="2" />
                        <path d="M8 3v4M16 3v4M3 10h18" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-slate-950">Datos de la consulta</h2>
                    <p class="text-xs text-slate-500">Información general de esta atención.</p>
                </div>
            </div>

            <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                <div>
                    <label class="dt-label">Fecha y hora *</label>
                    <input wire:model="consultation_at" type="datetime-local" class="dt-input">
                    @error('consultation_at') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="dt-label">Motivo de consulta</label>
                    <input wire:model="reason" type="text" placeholder="Ej. Dolor de cabeza" class="dt-input">
                    @error('reason') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        {{-- VITAL SIGNS --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4.5 w-4.5">
                        <path d="M3 12h4l2-5 4 10 2-5h6" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-slate-950">Signos vitales</h2>
                    <p class="text-xs text-slate-500">Registra las mediciones obtenidas durante la atención.</p>
                </div>
            </div>

            <div class="grid gap-3 p-5 sm:grid-cols-2 sm:p-6 lg:grid-cols-4">
                @foreach ([
                ['weight_kg', 'Peso', 'kg', '0.01'],
                ['height_cm', 'Estatura', 'cm', '0.01'],
                ['systolic_bp', 'Presión sistólica', 'mmHg', '1'],
                ['diastolic_bp', 'Presión diastólica', 'mmHg', '1'],
                ['heart_rate', 'Frecuencia cardiaca', 'lpm', '1'],
                ['respiratory_rate', 'Frecuencia respiratoria', 'rpm', '1'],
                ['temperature_c', 'Temperatura', '°C', '0.1'],
                ['oxygen_saturation', 'Saturación O₂', '%', '1'],
                ] as [$model, $label, $unit, $step])
                <div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-3 transition focus-within:border-blue-200 focus-within:bg-blue-50/40">
                    <label class="mb-2 flex items-center justify-between gap-2 text-xs font-semibold text-slate-600">
                        <span>{{ $label }}</span>
                        <span class="text-[10px] font-bold uppercase text-slate-400">{{ $unit }}</span>
                    </label>
                    <input
                        wire:model="{{ $model }}"
                        type="number"
                        step="{{ $step }}"
                        class="w-full border-0 bg-transparent p-0 text-lg font-bold text-slate-900 outline-none ring-0 focus:ring-0">
                    @error($model) <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                @endforeach
            </div>
        </section>

        {{-- SOAP --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4.5 w-4.5">
                        <path d="M6 3h9l3 3v15H6z" />
                        <path d="M14 3v4h4M9 11h6M9 15h6" stroke-linecap="round" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-slate-950">Nota clínica</h2>
                    <p class="text-xs text-slate-500">Estructura SOAP para documentar la consulta.</p>
                </div>
            </div>

            <div class="grid gap-4 p-5 sm:grid-cols-2 sm:p-6">
                @foreach ([
                ['subjective', 'S', 'Subjetivo', 'Síntomas, evolución, antecedentes relevantes...', 'blue'],
                ['objective', 'O', 'Objetivo', 'Exploración física, hallazgos...', 'cyan'],
                ['assessment', 'A', 'Evaluación / diagnóstico', 'Impresión diagnóstica...', 'violet'],
                ['plan', 'P', 'Plan', 'Tratamiento, estudios, seguimiento...', 'emerald'],
                ] as [$model, $letter, $label, $placeholder, $tone])
                <div class="rounded-2xl border border-slate-200 bg-slate-50/40 p-4">
                    <div class="mb-3 flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-white text-xs font-black text-slate-700 shadow-sm ring-1 ring-slate-200">{{ $letter }}</span>
                        <label class="text-sm font-bold text-slate-800">{{ $label }}</label>
                    </div>
                    <textarea wire:model="{{ $model }}" rows="6" placeholder="{{ $placeholder }}" class="dt-textarea bg-white"></textarea>
                    @error($model) <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                @endforeach
            </div>
        </section>

        {{-- DIAGNOSES --}}
        <section id="diagnosticos" class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4.5 w-4.5">
                            <path d="M12 3v18M3 12h18" stroke-linecap="round" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="font-semibold text-slate-950">Diagnósticos</h2>
                        <p class="text-xs text-slate-500">Registra los diagnósticos asociados a esta atención.</p>
                    </div>
                </div>

                <button type="button" wire:click="openDiagnosisModal" wire:loading.attr="disabled" wire:target="openDiagnosisModal"
                    class="dt-btn dt-btn-secondary inline-flex items-center justify-center gap-2 disabled:opacity-50">
                    <span wire:loading.remove wire:target="openDiagnosisModal">+ Agregar diagnóstico</span>
                    <span wire:loading wire:target="openDiagnosisModal">Preparando...</span>
                </button>
            </div>

            <div>
                @forelse ($consultation?->diagnoses ?? collect() as $diagnosis)
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 last:border-0 sm:flex-row sm:items-start sm:justify-between sm:px-6">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($diagnosis->is_primary)
                            <span class="rounded-full bg-gradient-to-r from-blue-600 to-violet-600 px-2.5 py-1 text-[11px] font-bold text-white">Principal</span>
                            @endif
                            @if ($diagnosis->code)
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600">{{ $diagnosis->code }}</span>
                            @endif
                        </div>
                        <p class="mt-2 font-semibold text-slate-900">{{ $diagnosis->description }}</p>
                        @if ($diagnosis->notes)
                        <p class="mt-1 whitespace-pre-line text-sm text-slate-500">{{ $diagnosis->notes }}</p>
                        @endif
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        <button type="button" wire:click="editDiagnosis({{ $diagnosis->id }})"
                            class="rounded-lg px-2.5 py-1.5 text-xs font-bold text-blue-600 transition hover:bg-blue-50">Editar</button>
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
                            class="rounded-lg px-2.5 py-1.5 text-xs font-bold text-rose-600 transition hover:bg-rose-50">
                            Eliminar
                        </button>
                    </div>
                </div>
                @empty
                <div class="px-6 py-10 text-center">
                    <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                            <path d="M12 3v18M3 12h18" stroke-linecap="round" />
                        </svg>
                    </div>
                    <p class="mt-3 font-semibold text-slate-700">Sin diagnósticos registrados</p>
                    <p class="mt-1 text-sm text-slate-500">Puedes agregar diagnósticos antes de finalizar la consulta.</p>
                </div>
                @endforelse
            </div>
        </section>

        {{-- ACTIONS --}}
        <div class="sticky bottom-3 z-20 flex flex-col-reverse gap-2 rounded-2xl border border-slate-200/80 bg-white/95 p-3 shadow-lg shadow-slate-200/60 backdrop-blur sm:flex-row sm:justify-end">
            <button type="button" wire:click="leaveConsultation" wire:loading.attr="disabled" wire:target="leaveConsultation"
                class="dt-btn dt-btn-secondary disabled:opacity-50">
                <span wire:loading.remove wire:target="leaveConsultation">Guardar borrador y salir</span>
                <span wire:loading wire:target="leaveConsultation">Guardando...</span>
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
                class="dt-btn dt-btn-primary disabled:opacity-50">
                <span wire:loading.remove wire:target="completeConsultation">Finalizar consulta</span>
                <span wire:loading wire:target="completeConsultation">Finalizando...</span>
            </button>
        </div>
    </div>

    {{-- DIAGNOSIS MODAL --}}
    @if ($showDiagnosisModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm">
        <div class="max-h-[92vh] w-full max-w-xl overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-2xl">
            <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-100 bg-white px-6 py-5">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                            <path d="M12 3v18M3 12h18" stroke-linecap="round" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-950">
                            {{ $editingDiagnosisId ? 'Editar diagnóstico' : 'Nuevo diagnóstico' }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">Registra un diagnóstico asociado a esta consulta.</p>
                    </div>
                </div>

                <button type="button" wire:click="closeDiagnosisModal" aria-label="Cerrar"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" class="h-5 w-5">
                        <path d="m7 7 10 10M17 7 7 17" stroke-linecap="round" />
                    </svg>
                </button>
            </div>

            <form wire:submit="saveDiagnosis">
                <div class="space-y-5 p-6">
                    <div class="relative">
                        <label class="dt-label">Buscar en catálogo CIE-10</label>
                        <div class="relative">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400">
                                <circle cx="11" cy="11" r="7" />
                                <path d="m20 20-3.5-3.5" />
                            </svg>
                            <input wire:model.live.debounce.300ms="diagnosisSearch" type="search" autocomplete="off"
                                placeholder="Escribe código o diagnóstico, ej. cefalea..." class="dt-input pl-10">
                        </div>

                        @if (mb_strlen(trim($diagnosisSearch)) >= 2)
                        <div class="absolute z-20 mt-2 max-h-72 w-full overflow-y-auto rounded-2xl border border-slate-200 bg-white p-1.5 shadow-xl">
                            @forelse ($this->diagnosisResults as $result)
                            <button type="button" wire:click="selectDiagnosis({{ $result->id }})"
                                class="flex w-full items-start gap-3 rounded-xl px-3 py-3 text-left transition hover:bg-blue-50">
                                <span class="shrink-0 rounded-lg bg-blue-50 px-2 py-1 text-xs font-bold text-blue-700">{{ $result->code }}</span>
                                <span class="text-sm text-slate-700">{{ $result->description }}</span>
                            </button>
                            @empty
                            <div class="rounded-xl bg-slate-50 px-4 py-4">
                                <p class="text-sm font-semibold text-slate-700">Sin coincidencias</p>
                                <p class="mt-1 text-xs text-slate-500">Puedes capturar el diagnóstico manualmente.</p>
                            </div>
                            @endforelse
                        </div>
                        @endif
                    </div>

                    <div>
                        <label class="dt-label">Código CIE-10</label>
                        <input wire:model="diagnosis_code" type="text" placeholder="Ej. R51.9" class="dt-input">
                        @error('diagnosis_code') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="dt-label">Descripción *</label>
                        <input wire:model="diagnosis_description" type="text" placeholder="Ej. Cefalea no especificada" class="dt-input">
                        @error('diagnosis_description') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="dt-label">Notas</label>
                        <textarea wire:model="diagnosis_notes" rows="4" class="dt-textarea"></textarea>
                        @error('diagnosis_notes') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <input wire:model="diagnosis_is_primary" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <div>
                            <span class="block text-sm font-semibold text-slate-800">Diagnóstico principal</span>
                            <span class="block text-xs text-slate-500">Se marcará como el diagnóstico principal de esta consulta.</span>
                        </div>
                    </label>
                </div>

                <div class="sticky bottom-0 flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50/95 px-6 py-4 backdrop-blur sm:flex-row sm:justify-end">
                    <button type="button" wire:click="closeDiagnosisModal" class="dt-btn dt-btn-secondary">Cancelar</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="saveDiagnosis" class="dt-btn dt-btn-primary disabled:opacity-50">
                        <span wire:loading.remove wire:target="saveDiagnosis">
                            {{ $editingDiagnosisId ? 'Guardar cambios' : 'Guardar diagnóstico' }}
                        </span>
                        <span wire:loading wire:target="saveDiagnosis">Guardando...</span>
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