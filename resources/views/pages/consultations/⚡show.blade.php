<?php

use App\Models\Consultation;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\ConsultationDiagnosis;
use App\Models\DiagnosisCatalog;
use Livewire\Attributes\Computed;

new
    #[Layout('layouts::app')]
    #[Title('Consulta | DocTotal')]
    class extends Component
    {
        public Consultation $consultation;

        public bool $showDiagnosisModal = false;

        public ?int $editingDiagnosisId = null;

        public string $diagnosis_code = '';
        public string $diagnosis_description = '';
        public bool $diagnosis_is_primary = false;
        public string $diagnosis_notes = '';
        public string $diagnosisSearch = '';

        public function mount(string $uuid): void
        {
            $this->consultation = Consultation::query()
                ->with([
                    'patient',
                    'doctorProfile',
                ])
                ->where('uuid', $uuid)
                ->firstOrFail();
        }

        public function openDiagnosisModal(): void
        {
            if (! $this->consultation->canEdit()) {
                abort(403);
            }

            $this->editingDiagnosisId = null;

            $this->resetDiagnosisForm();
            $this->resetValidation();

            $this->showDiagnosisModal = true;
        }

        public function editDiagnosis(int $diagnosisId): void
        {
            if (! $this->consultation->canEdit()) {
                abort(403);
            }

            $diagnosis = ConsultationDiagnosis::query()
                ->where(
                    'consultation_id',
                    $this->consultation->id
                )
                ->findOrFail($diagnosisId);

            $this->editingDiagnosisId = $diagnosis->id;

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
            if (! $this->consultation->canEdit()) {
                abort(403);
            }

            $validated = $this->validateDiagnosis();

            if ($validated['diagnosis_is_primary']) {
                ConsultationDiagnosis::query()
                    ->where(
                        'consultation_id',
                        $this->consultation->id
                    )
                    ->when(
                        $this->editingDiagnosisId,
                        fn($query) => $query->where(
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
                $diagnosis = ConsultationDiagnosis::query()
                    ->where(
                        'consultation_id',
                        $this->consultation->id
                    )
                    ->findOrFail(
                        $this->editingDiagnosisId
                    );

                $diagnosis->update([
                    'code' =>
                    $validated['diagnosis_code']
                        ?: null,

                    'description' =>
                    $validated['diagnosis_description'],

                    'is_primary' =>
                    $validated['diagnosis_is_primary'],

                    'notes' =>
                    $validated['diagnosis_notes']
                        ?: null,
                ]);

                $message =
                    'Diagnóstico actualizado correctamente.';
            } else {
                ConsultationDiagnosis::create([
                    'consultation_id' =>
                    $this->consultation->id,

                    'code' =>
                    $validated['diagnosis_code']
                        ?: null,

                    'description' =>
                    $validated['diagnosis_description'],

                    'is_primary' =>
                    $validated['diagnosis_is_primary'],

                    'notes' =>
                    $validated['diagnosis_notes']
                        ?: null,
                ]);

                $message =
                    'Diagnóstico registrado correctamente.';
            }

            $this->consultation
                ->unsetRelation('diagnoses');

            $this->showDiagnosisModal = false;

            $this->editingDiagnosisId = null;

            $this->resetDiagnosisForm();

            session()->flash(
                'success',
                $message
            );

            $this->redirectRoute(
                'consultations.show',
                [
                    'uuid' =>
                    $this->consultation->uuid,
                ]
            );

            $this->dispatch('diagnosis-saved');
        }

        public function deleteDiagnosis(
            int $diagnosisId
        ): void {
            if (! $this->consultation->canEdit()) {
                abort(403);
            }

            $diagnosis = ConsultationDiagnosis::query()
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

            $this->redirectRoute(
                'consultations.show',
                [
                    'uuid' =>
                    $this->consultation->uuid,
                ]
            );
        }

        #[Computed]
        public function diagnosisResults()
        {
            $search = trim($this->diagnosisSearch);

            if (mb_strlen($search) < 2) {
                return collect();
            }

            return DiagnosisCatalog::query()
                ->where('active', true)
                ->where(function ($query) use ($search) {
                    $query
                        ->where('code', 'like', $search . '%')
                        ->orWhere(
                            'description',
                            'like',
                            '%' . $search . '%'
                        );
                })
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

        public function selectDiagnosis(int $catalogId): void
        {
            $diagnosis = DiagnosisCatalog::query()
                ->where('active', true)
                ->findOrFail($catalogId);

            $this->diagnosis_code = $diagnosis->code;
            $this->diagnosis_description = $diagnosis->description;

            $this->diagnosisSearch = '';

            unset($this->diagnosisResults);
        }
    };
?>

<div class="mx-auto max-w-6xl">

    {{-- HEADER --}}
    <div class="mb-6">
        <div class="flex flex-wrap items-center gap-4">
            <a href="{{ route('consultations.index') }}"
                class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-blue-600">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" class="h-4 w-4">
                    <path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Consultas
            </a>

            <span class="h-4 w-px bg-slate-200"></span>

            <a href="{{ route('patients.show', ['uuid' => $consultation->patient->uuid]) }}"
                class="text-sm font-semibold text-slate-500 transition hover:text-blue-600">
                Expediente del paciente
            </a>
        </div>

        <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="h-1 bg-gradient-to-r from-blue-500 via-violet-500 to-cyan-500"></div>

            <div class="flex flex-col gap-5 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                <div class="flex min-w-0 items-center gap-4">
                    <div class="flex h-13 w-13 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-violet-600 text-lg font-bold text-white shadow-sm">
                        {{ strtoupper(substr($consultation->patient->first_name, 0, 1)) }}
                    </div>

                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-2xl font-bold tracking-tight text-slate-950">Consulta</h1>

                            @if ($consultation->isCompleted())
                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700 ring-1 ring-inset ring-emerald-100">
                                Finalizada
                            </span>
                            @else
                            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-bold text-amber-700 ring-1 ring-inset ring-amber-100">
                                Borrador
                            </span>
                            @endif
                        </div>

                        <p class="mt-1 truncate text-base font-semibold text-slate-700">
                            {{ $consultation->patient->first_name }}
                            {{ $consultation->patient->last_name }}
                        </p>

                        <p class="mt-1 text-sm text-slate-500">
                            {{ $consultation->consultation_at->locale('es')->translatedFormat('d \d\e F \d\e Y') }}
                            · {{ $consultation->consultation_at->format('H:i') }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('patients.show', ['uuid' => $consultation->patient->uuid]) }}"
                        class="dt-btn dt-btn-secondary">
                        Ver paciente
                    </a>

                    @if ($consultation->canEdit())
                    <button type="button" wire:click="openDiagnosisModal" class="dt-btn dt-btn-secondary">
                        + Diagnóstico
                    </button>
                    @endif

                    @if ($consultation->isCompleted())
                    <a href="{{ route('prescriptions.create', ['uuid' => $consultation->uuid]) }}"
                        class="dt-btn dt-btn-primary">
                        Crear receta
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-6">

        {{-- GENERAL DATA --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4.5 w-4.5">
                        <path d="M5 4h14v16H5z" />
                        <path d="M8 9h8M8 13h6" stroke-linecap="round" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-slate-950">Datos generales</h2>
                    <p class="text-xs text-slate-500">Información principal de la atención.</p>
                </div>
            </div>

            <div class="grid gap-4 p-5 sm:grid-cols-2 sm:p-6">
                <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-400">Motivo de consulta</p>
                    <p class="mt-2 text-sm font-medium text-slate-800">{{ $consultation->reason ?: 'Sin registro' }}</p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-400">Médico</p>
                    <p class="mt-2 text-sm font-medium text-slate-800">
                        {{ $consultation->doctorProfile->first_name }}
                        {{ $consultation->doctorProfile->last_name }}
                    </p>
                </div>
            </div>
        </section>

        {{-- VITALS --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4.5 w-4.5">
                        <path d="M3 12h4l2-5 4 10 2-5h6" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-slate-950">Signos vitales</h2>
                    <p class="text-xs text-slate-500">Mediciones registradas durante la consulta.</p>
                </div>
            </div>

            <div class="grid gap-3 p-5 sm:grid-cols-2 sm:p-6 lg:grid-cols-4">
                @foreach ([
                ['Peso', $consultation->weight_kg ? $consultation->weight_kg . ' kg' : '—'],
                ['Estatura', $consultation->height_cm ? $consultation->height_cm . ' cm' : '—'],
                ['Presión arterial', ($consultation->systolic_bp && $consultation->diastolic_bp) ? $consultation->systolic_bp . '/' . $consultation->diastolic_bp . ' mmHg' : '—'],
                ['Frecuencia cardiaca', $consultation->heart_rate ? $consultation->heart_rate . ' lpm' : '—'],
                ['Frecuencia respiratoria', $consultation->respiratory_rate ? $consultation->respiratory_rate . ' rpm' : '—'],
                ['Temperatura', $consultation->temperature_c ? $consultation->temperature_c . ' °C' : '—'],
                ['Saturación O₂', $consultation->oxygen_saturation ? $consultation->oxygen_saturation . '%' : '—'],
                ] as [$label, $value])
                <div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4">
                    <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-slate-400">{{ $label }}</p>
                    <p class="mt-2 text-lg font-bold tabular-nums text-slate-900">{{ $value }}</p>
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
                    <p class="text-xs text-slate-500">Formato SOAP</p>
                </div>
            </div>

            <div class="grid gap-4 p-5 sm:grid-cols-2 sm:p-6">
                @foreach ([
                ['S', 'Subjetivo', $consultation->subjective, 'blue'],
                ['O', 'Objetivo', $consultation->objective, 'cyan'],
                ['A', 'Evaluación / diagnóstico', $consultation->assessment, 'violet'],
                ['P', 'Plan', $consultation->plan, 'emerald'],
                ] as [$letter, $label, $value, $tone])
                <div class="rounded-2xl border border-slate-200 bg-slate-50/40 p-4">
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-white text-xs font-black text-slate-700 shadow-sm ring-1 ring-slate-200">
                            {{ $letter }}
                        </span>
                        <p class="text-sm font-bold text-slate-800">{{ $label }}</p>
                    </div>

                    <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-600">
                        {{ $value ?: 'Sin registro' }}
                    </p>
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
                        <p class="text-xs text-slate-500">Diagnósticos asociados a esta consulta.</p>
                    </div>
                </div>

                @if ($consultation->canEdit())
                <button type="button" wire:click="openDiagnosisModal" class="dt-btn dt-btn-secondary">
                    + Agregar diagnóstico
                </button>
                @endif
            </div>

            <div>
                @forelse ($consultation->diagnoses as $diagnosis)
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 last:border-0 sm:flex-row sm:items-start sm:justify-between sm:px-6">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($diagnosis->is_primary)
                            <span class="rounded-full bg-gradient-to-r from-blue-600 to-violet-600 px-2.5 py-1 text-[11px] font-bold text-white">
                                Principal
                            </span>
                            @endif

                            @if ($diagnosis->code)
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600">
                                {{ $diagnosis->code }}
                            </span>
                            @endif
                        </div>

                        <p class="mt-2 font-semibold text-slate-900">{{ $diagnosis->description }}</p>

                        @if ($diagnosis->notes)
                        <p class="mt-1 whitespace-pre-line text-sm text-slate-500">{{ $diagnosis->notes }}</p>
                        @endif
                    </div>

                    @if ($consultation->canEdit())
                    <div class="flex shrink-0 items-center gap-2">
                        <button type="button" wire:click="editDiagnosis({{ $diagnosis->id }})"
                            class="rounded-lg px-2.5 py-1.5 text-xs font-bold text-blue-600 transition hover:bg-blue-50">
                            Editar
                        </button>

                        <button type="button" x-data
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
                    @endif
                </div>
                @empty
                <div class="px-6 py-10 text-center">
                    <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                            <path d="M12 3v18M3 12h18" stroke-linecap="round" />
                        </svg>
                    </div>
                    <p class="mt-3 font-semibold text-slate-700">Sin diagnósticos registrados</p>
                    <p class="mt-1 text-sm text-slate-500">Agrega uno o más diagnósticos para esta consulta.</p>
                </div>
                @endforelse
            </div>
        </section>

        {{-- PRESCRIPTIONS --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-50 text-cyan-600">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4.5 w-4.5">
                            <path d="M7 3h10v18H7z" />
                            <path d="M10 8h4M10 12h4M10 16h3" stroke-linecap="round" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="font-semibold text-slate-950">Recetas</h2>
                        <p class="text-xs text-slate-500">Recetas asociadas a esta consulta.</p>
                    </div>
                </div>

                @if ($consultation->isCompleted())
                <a href="{{ route('prescriptions.create', ['uuid' => $consultation->uuid]) }}"
                    class="dt-btn dt-btn-secondary">
                    + Crear receta
                </a>
                @endif
            </div>

            <div>
                @forelse ($consultation->prescriptions()->latest('prescribed_at')->get() as $prescription)
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 last:border-0 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-cyan-50 text-cyan-600">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                                <path d="M7 3h10v18H7z" />
                                <path d="M10 8h4M10 12h4" stroke-linecap="round" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900">
                                {{ $prescription->prescribed_at->format('d/m/Y H:i') }}
                            </p>
                            <p class="mt-0.5 text-sm text-slate-500">
                                {{ $prescription->items()->count() }} medicamento(s)
                            </p>
                        </div>
                    </div>

                    <a href="{{ route('prescriptions.show', ['uuid' => $prescription->uuid]) }}"
                        class="text-sm font-bold text-blue-600 transition hover:text-blue-700">
                        Ver receta →
                    </a>
                </div>
                @empty
                <div class="px-6 py-10 text-center">
                    <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-2xl bg-cyan-50 text-cyan-600">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                            <path d="M7 3h10v18H7z" />
                            <path d="M10 8h4M10 12h4" stroke-linecap="round" />
                        </svg>
                    </div>
                    <p class="mt-3 font-semibold text-slate-700">Sin recetas</p>
                    <p class="mt-1 text-sm text-slate-500">Las recetas de esta consulta aparecerán aquí.</p>
                </div>
                @endforelse
            </div>
        </section>
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
                        <input wire:model="diagnosis_is_primary" type="checkbox"
                            class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <div>
                            <span class="block text-sm font-semibold text-slate-800">Diagnóstico principal</span>
                            <span class="block text-xs text-slate-500">Se marcará como el diagnóstico principal de esta consulta.</span>
                        </div>
                    </label>
                </div>

                <div class="sticky bottom-0 flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50/95 px-6 py-4 backdrop-blur sm:flex-row sm:justify-end">
                    <button type="button" wire:click="closeDiagnosisModal" class="dt-btn dt-btn-secondary">Cancelar</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="saveDiagnosis"
                        class="dt-btn dt-btn-primary disabled:opacity-50">
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