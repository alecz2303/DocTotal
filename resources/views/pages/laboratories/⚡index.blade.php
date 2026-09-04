<?php

use App\Models\LaboratoryStudy;
use App\Models\Patient;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
    #[Layout('layouts::app')]
    #[Title('Laboratorios | DocTotal')]
    class extends Component
    {
        public Patient $patient;

        public bool $showForm = false;
        public ?int $editingStudyId = null;

        public string $name = '';
        public string $study_date = '';
        public string $laboratory_name = '';
        public string $notes = '';
        public ?int $consultation_id = null;

        public array $results = [];

        public function mount(string $uuid): void
        {
            $this->patient = Patient::query()
                ->where('uuid', $uuid)
                ->firstOrFail();

            $this->resetForm();
        }

        #[Computed]
        public function studies()
        {
            return $this->patient
                ->laboratoryStudies()
                ->with(['results', 'consultation'])
                ->orderByDesc('study_date')
                ->orderByDesc('created_at')
                ->get();
        }

        #[Computed]
        public function consultations()
        {
            return $this->patient
                ->consultations()
                ->orderByDesc('consultation_at')
                ->get();
        }

        public function createStudy(): void
        {
            $this->resetForm();
            $this->showForm = true;
        }

        public function editStudy(int $studyId): void
        {
            $study = $this->patient
                ->laboratoryStudies()
                ->with('results')
                ->findOrFail($studyId);

            $this->editingStudyId = $study->id;
            $this->name = $study->name;
            $this->study_date = $study->study_date->format('Y-m-d');
            $this->laboratory_name = $study->laboratory_name ?? '';
            $this->notes = $study->notes ?? '';
            $this->consultation_id = $study->consultation_id;
            $this->results = $study->results
                ->map(fn ($result) => [
                    'parameter_name' => $result->parameter_name,
                    'value' => $result->value,
                    'unit' => $result->unit ?? '',
                    'reference_range' => $result->reference_range ?? '',
                ])
                ->values()
                ->all();

            if ($this->results === []) {
                $this->addResult();
            }

            $this->resetValidation();
            $this->showForm = true;
        }

        public function closeForm(): void
        {
            $this->showForm = false;
            $this->resetForm();
        }

        public function addResult(): void
        {
            $this->results[] = [
                'parameter_name' => '',
                'value' => '',
                'unit' => '',
                'reference_range' => '',
            ];
        }

        public function removeResult(int $index): void
        {
            if (count($this->results) <= 1) {
                return;
            }

            unset($this->results[$index]);
            $this->results = array_values($this->results);
        }

        public function saveStudy(): void
        {
            $validated = $this->validate([
                'name' => ['required', 'string', 'max:180'],
                'study_date' => ['required', 'date'],
                'laboratory_name' => ['nullable', 'string', 'max:180'],
                'notes' => ['nullable', 'string', 'max:5000'],
                'consultation_id' => ['nullable', 'integer'],
                'results' => ['required', 'array', 'min:1'],
                'results.*.parameter_name' => ['required', 'string', 'max:180'],
                'results.*.value' => ['required', 'string', 'max:255'],
                'results.*.unit' => ['nullable', 'string', 'max:80'],
                'results.*.reference_range' => ['nullable', 'string', 'max:180'],
            ]);

            if ($validated['consultation_id']) {
                $belongsToPatient = $this->patient
                    ->consultations()
                    ->whereKey($validated['consultation_id'])
                    ->exists();

                if (! $belongsToPatient) {
                    throw ValidationException::withMessages([
                        'consultation_id' => 'La consulta seleccionada no pertenece a este paciente.',
                    ]);
                }
            }

            $study = DB::transaction(function () use ($validated): LaboratoryStudy {
                if ($this->editingStudyId) {
                    $study = $this->patient
                        ->laboratoryStudies()
                        ->findOrFail($this->editingStudyId);

                    $action = 'laboratory_study.updated';
                    $description = 'Estudio de laboratorio actualizado.';
                } else {
                    $study = new LaboratoryStudy();
                    $study->patient_id = $this->patient->id;

                    $action = 'laboratory_study.created';
                    $description = 'Estudio de laboratorio registrado.';
                }

                $study->fill([
                    'consultation_id' => $validated['consultation_id'] ?: null,
                    'name' => $validated['name'],
                    'study_date' => $validated['study_date'],
                    'laboratory_name' => $validated['laboratory_name'] ?: null,
                    'notes' => $validated['notes'] ?: null,
                ]);
                $study->save();

                $study->results()->delete();

                foreach (array_values($validated['results']) as $position => $result) {
                    $study->results()->create([
                        'parameter_name' => $result['parameter_name'],
                        'value' => $result['value'],
                        'unit' => $result['unit'] ?: null,
                        'reference_range' => $result['reference_range'] ?: null,
                        'position' => $position,
                    ]);
                }

                app(AuditLogger::class)->safeLog(
                    action: $action,
                    auditable: $study,
                    description: $description,
                    metadata: [
                        'patient_id' => $this->patient->id,
                        'consultation_id' => $study->consultation_id,
                        'results_count' => count($validated['results']),
                    ],
                );

                return $study;
            });

            $wasEditing = $this->editingStudyId !== null;

            unset($this->studies);
            $this->showForm = false;
            $this->resetForm();

            session()->flash(
                'success',
                $wasEditing
                    ? 'Estudio actualizado correctamente.'
                    : 'Estudio registrado correctamente.'
            );
        }

        public function deleteStudy(int $studyId): void
        {
            $study = $this->patient
                ->laboratoryStudies()
                ->with('results')
                ->findOrFail($studyId);

            DB::transaction(function () use ($study): void {
                app(AuditLogger::class)->safeLog(
                    action: 'laboratory_study.deleted',
                    auditable: $study,
                    description: 'Estudio de laboratorio eliminado.',
                    metadata: [
                        'patient_id' => $this->patient->id,
                        'study_name' => $study->name,
                        'results_count' => $study->results->count(),
                    ],
                );

                $study->results()->delete();
                $study->delete();
            });

            unset($this->studies);

            session()->flash('success', 'Estudio eliminado correctamente.');
        }

        public string $bulkResults = '';

        public bool $showBulkResults = false;

        public function openBulkResults(): void
        {
            $this->bulkResults = '';
            $this->showBulkResults = true;
            $this->resetErrorBag('bulkResults');
        }

        public function closeBulkResults(): void
        {
            $this->bulkResults = '';
            $this->showBulkResults = false;
            $this->resetErrorBag('bulkResults');
        }

        public function importBulkResults(): void
        {
            $lines = preg_split('/\r\n|\r|\n/', trim($this->bulkResults)) ?: [];
            $parsed = [];

            foreach ($lines as $line) {
                $line = trim($line);

                if ($line === '') {
                    continue;
                }

                if (str_contains($line, "\t")) {
                    $columns = array_map('trim', explode("\t", $line));
                } else {
                    $delimiter = str_contains($line, '|')
                        ? '|'
                        : (str_contains($line, ';') ? ';' : null);

                    if ($delimiter === null) {
                        continue;
                    }

                    $columns = array_map('trim', explode($delimiter, $line));
                }

                if (count($columns) < 2) {
                    continue;
                }

                $parameter = $columns[0] ?? '';
                $value = $columns[1] ?? '';

                $normalizedParameter = mb_strtolower($parameter);

                if (
                    $normalizedParameter === 'parámetro'
                    || $normalizedParameter === 'parametro'
                    || $normalizedParameter === 'parameter'
                ) {
                    continue;
                }

                if ($parameter === '' || $value === '') {
                    continue;
                }

                $parsed[] = [
                    'parameter_name' => mb_substr($parameter, 0, 180),
                    'value' => mb_substr($value, 0, 255),
                    'unit' => isset($columns[2]) && $columns[2] !== ''
                        ? mb_substr($columns[2], 0, 80)
                        : '',
                    'reference_range' => isset($columns[3]) && $columns[3] !== ''
                        ? mb_substr($columns[3], 0, 180)
                        : '',
                ];
            }

            if ($parsed === []) {
                $this->addError(
                    'bulkResults',
                    'No pude reconocer resultados. Usa columnas: Parámetro, Valor, Unidad y Rango; separadas por tabulador, | o ;.'
                );

                return;
            }

            $currentResults = collect($this->results)
                ->filter(fn (array $result): bool =>
                    trim((string) ($result['parameter_name'] ?? '')) !== ''
                    || trim((string) ($result['value'] ?? '')) !== ''
                    || trim((string) ($result['unit'] ?? '')) !== ''
                    || trim((string) ($result['reference_range'] ?? '')) !== ''
                )
                ->values()
                ->all();

            $this->results = array_values([...$currentResults, ...$parsed]);
            $this->bulkResults = '';
            $this->showBulkResults = false;
            $this->resetErrorBag('bulkResults');
        }

        private function resetForm(): void
        {
            $this->editingStudyId = null;
            $this->name = '';
            $this->study_date = now()->toDateString();
            $this->laboratory_name = '';
            $this->notes = '';
            $this->consultation_id = null;
            $this->bulkResults = '';
            $this->showBulkResults = false;
            $this->results = [];
            $this->addResult();
            $this->resetValidation();
        }
    };
?>
<div class="mx-auto w-full max-w-[1480px] space-y-5 px-4 py-5 sm:px-6 lg:px-8">
    {{-- ENCABEZADO --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <a
                href="{{ route('patients.show', ['uuid' => $patient->uuid]) }}"
                wire:navigate
                class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-slate-900"
            >
                <span aria-hidden="true">←</span>
                Volver al expediente
            </a>

            <div class="mt-3 flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-violet-50 text-violet-600 ring-1 ring-inset ring-violet-100">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                        <path d="M9 3h6M10 3v5.2l-5.2 8.4A2.8 2.8 0 0 0 7.2 21h9.6a2.8 2.8 0 0 0 2.4-4.4L14 8.2V3" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M7.5 15h9" stroke-linecap="round"/>
                    </svg>
                </div>

                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-950">Laboratorios</h1>
                    <p class="mt-0.5 text-sm text-slate-500">
                        {{ $patient->first_name }} {{ $patient->last_name }} {{ $patient->second_last_name }}
                    </p>
                </div>
            </div>
        </div>

        @if (! $showForm)
            <button type="button" wire:click="createStudy" class="dt-btn dt-btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                    <path d="M12 5v14M5 12h14" stroke-linecap="round"/>
                </svg>
                Nuevo estudio
            </button>
        @endif
    </div>

    @if (session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- FORMULARIO --}}
    @if ($showForm)
        <div class="space-y-4">
            <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-doctotal-md">
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-950">
                            {{ $editingStudyId ? 'Editar estudio' : 'Nuevo estudio' }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Registra la información general del estudio de laboratorio.
                        </p>
                    </div>

                    <div class="flex gap-2">
                        <button type="button" wire:click="closeForm" class="dt-btn dt-btn-secondary">
                            Cancelar
                        </button>
                        <button type="button" wire:click="saveStudy" class="dt-btn dt-btn-primary">
                            Guardar estudio
                        </button>
                    </div>
                </div>

                <div class="grid gap-x-5 gap-y-4 p-5 lg:grid-cols-2">
                    <label class="space-y-1.5">
                        <span class="text-sm font-semibold text-slate-700">Estudio *</span>
                        <input wire:model="name" type="text" class="dt-input" placeholder="Ej. Biometría hemática">
                        @error('name') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="space-y-1.5">
                        <span class="text-sm font-semibold text-slate-700">Fecha *</span>
                        <input wire:model="study_date" type="date" class="dt-input">
                        @error('study_date') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="space-y-1.5">
                        <span class="text-sm font-semibold text-slate-700">Laboratorio / proveedor</span>
                        <input wire:model="laboratory_name" type="text" class="dt-input" placeholder="Ej. Chopo">
                    </label>

                    <label class="space-y-1.5">
                        <span class="text-sm font-semibold text-slate-700">Consulta relacionada</span>
                        <select wire:model="consultation_id" class="dt-input">
                            <option value="">Sin consulta relacionada</option>
                            @foreach ($this->consultations as $consultation)
                                <option value="{{ $consultation->id }}">
                                    {{ $consultation->consultation_at?->format('d/m/Y H:i') ?? 'Consulta' }}
                                    — {{ $consultation->reason ?: 'Sin motivo' }}
                                </option>
                            @endforeach
                        </select>
                        @error('consultation_id') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="space-y-1.5 lg:col-span-2">
                        <span class="text-sm font-semibold text-slate-700">Observaciones</span>
                        <textarea wire:model="notes" rows="3" class="dt-input" placeholder="Observaciones clínicas opcionales"></textarea>
                    </label>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-doctotal-md">
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-950">Resultados</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Agrega uno o varios parámetros del mismo estudio.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button type="button" wire:click="openBulkResults" class="dt-btn dt-btn-primary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                                <path d="M8 5h8M8 9h8M8 13h5M6 3h12a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Pegar resultados
                        </button>

                        <button type="button" wire:click="addResult" class="dt-btn dt-btn-secondary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                                <path d="M12 5v14M5 12h14" stroke-linecap="round"/>
                            </svg>
                            Agregar parámetro
                        </button>
                    </div>
                </div>

                @if ($showBulkResults)
                    <div class="border-b border-violet-100 bg-violet-50/50 p-4 sm:p-5">
                        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
                            <div>
                                <div class="flex items-center gap-2">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-white text-violet-600 ring-1 ring-inset ring-violet-200">
                                        <span class="text-sm font-black">↧</span>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-bold text-slate-900">Pegar resultados en bloque</h3>
                                        <p class="text-xs text-slate-500">Una fila por parámetro. Puedes copiar directamente desde Excel o Google Sheets.</p>
                                    </div>
                                </div>

                                <textarea
                                    wire:model="bulkResults"
                                    rows="9"
                                    class="dt-input mt-3 font-mono text-sm leading-6"
                                    placeholder="Leucocitos&#9;16.34&#9;miles/µL&#9;4.1-12.6&#10;Eritrocitos&#9;4.24&#9;millones/µL&#9;4.50-5.20&#10;Hemoglobina&#9;12.7&#9;g/dL&#9;12.0-16.0"
                                ></textarea>

                                @error('bulkResults')
                                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror

                                <div class="mt-3 flex flex-wrap justify-end gap-2">
                                    <button type="button" wire:click="closeBulkResults" class="dt-btn dt-btn-secondary">
                                        Cancelar
                                    </button>
                                    <button type="button" wire:click="importBulkResults" class="dt-btn dt-btn-primary">
                                        Convertir en filas
                                    </button>
                                </div>
                            </div>

                            <aside class="rounded-2xl border border-violet-100 bg-white p-4">
                                <p class="text-xs font-bold uppercase tracking-wide text-violet-700">Formato</p>
                                <p class="mt-2 text-sm leading-6 text-slate-600">
                                    Usa cuatro columnas. Solo <strong>Parámetro</strong> y <strong>Valor</strong> son obligatorios.
                                </p>

                                <div class="mt-3 overflow-hidden rounded-xl border border-slate-200 text-xs">
                                    <div class="grid grid-cols-4 bg-slate-50 font-bold text-slate-600">
                                        <span class="px-2 py-2">Parámetro</span>
                                        <span class="px-2 py-2">Valor</span>
                                        <span class="px-2 py-2">Unidad</span>
                                        <span class="px-2 py-2">Rango</span>
                                    </div>
                                    <div class="grid grid-cols-4 border-t border-slate-100 text-slate-500">
                                        <span class="px-2 py-2">Glucosa</span>
                                        <span class="px-2 py-2">115</span>
                                        <span class="px-2 py-2">mg/dL</span>
                                        <span class="px-2 py-2">55-99</span>
                                    </div>
                                </div>

                                <p class="mt-3 text-xs leading-5 text-slate-500">
                                    También acepta texto separado por <strong>|</strong> o <strong>;</strong>. Antes de guardar podrás revisar y editar todas las filas.
                                </p>
                            </aside>
                        </div>
                    </div>
                @endif

                <div class="p-4 sm:p-5">
                    <div class="hidden grid-cols-12 gap-3 px-3 pb-2 text-xs font-bold uppercase tracking-wide text-slate-400 lg:grid">
                        <div class="col-span-4">Parámetro *</div>
                        <div class="col-span-2">Valor *</div>
                        <div class="col-span-2">Unidad</div>
                        <div class="col-span-3">Rango de referencia</div>
                        <div class="col-span-1 text-center">Acción</div>
                    </div>

                    <div class="space-y-3">
                        @foreach ($results as $index => $result)
                            <div wire:key="lab-result-{{ $index }}" class="grid gap-3 rounded-xl border border-slate-200 bg-slate-50/50 p-3 lg:grid-cols-12 lg:items-start">
                                <label class="space-y-1 lg:col-span-4">
                                    <span class="text-xs font-semibold text-slate-500 lg:hidden">Parámetro *</span>
                                    <input wire:model="results.{{ $index }}.parameter_name" type="text" class="dt-input" placeholder="Ej. Hemoglobina">
                                    @error("results.$index.parameter_name") <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                                </label>

                                <label class="space-y-1 lg:col-span-2">
                                    <span class="text-xs font-semibold text-slate-500 lg:hidden">Valor *</span>
                                    <input wire:model="results.{{ $index }}.value" type="text" class="dt-input" placeholder="14.2">
                                    @error("results.$index.value") <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                                </label>

                                <label class="space-y-1 lg:col-span-2">
                                    <span class="text-xs font-semibold text-slate-500 lg:hidden">Unidad</span>
                                    <input wire:model="results.{{ $index }}.unit" type="text" class="dt-input" placeholder="g/dL">
                                </label>

                                <label class="space-y-1 lg:col-span-3">
                                    <span class="text-xs font-semibold text-slate-500 lg:hidden">Rango de referencia</span>
                                    <input wire:model="results.{{ $index }}.reference_range" type="text" class="dt-input" placeholder="12 - 16">
                                </label>

                                <div class="flex lg:col-span-1 lg:justify-center">
                                    <button
                                        type="button"
                                        wire:click="removeResult({{ $index }})"
                                        @disabled(count($results) <= 1)
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-rose-100 bg-white text-rose-600 transition hover:bg-rose-50 disabled:pointer-events-none disabled:opacity-30"
                                        title="Quitar parámetro"
                                        aria-label="Quitar parámetro"
                                    >
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                                            <path d="M4 7h16M9 7V4h6v3M8 7l1 13h6l1-13M10 11v5M14 11v5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-5 flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <button type="button" wire:click="closeForm" class="dt-btn dt-btn-secondary">
                            Cancelar
                        </button>
                        <button type="button" wire:click="saveStudy" class="dt-btn dt-btn-primary">
                            Guardar estudio
                        </button>
                    </div>
                </div>
            </section>
        </div>
    @endif

    {{-- HISTORIAL --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-doctotal-md">
        <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-base font-bold text-slate-950">Historial de laboratorios</h2>
                    @if ($this->studies->isNotEmpty())
                        <span class="inline-flex items-center rounded-full bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700 ring-1 ring-inset ring-violet-200">
                            {{ $this->studies->count() }}
                            {{ $this->studies->count() === 1 ? 'estudio' : 'estudios' }}
                        </span>
                    @endif
                </div>
                <p class="mt-1 text-sm text-slate-500">
                    Resultados históricos registrados para este paciente.
                </p>
            </div>

            @if ($showForm)
                <span class="text-xs font-medium text-slate-400">
                    Guarda o cancela el estudio actual para crear otro.
                </span>
            @endif
        </div>

        @forelse ($this->studies as $study)
            <article
                x-data="{ open: false }"
                class="border-b border-slate-100 last:border-0"
            >
                <div class="grid gap-3 px-5 py-4 lg:grid-cols-[150px_minmax(220px,1.4fr)_minmax(180px,1fr)_110px_auto] lg:items-center">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 lg:hidden">Fecha</p>
                        <p class="text-sm font-semibold text-slate-700">{{ $study->study_date->format('d/m/Y') }}</p>
                    </div>

                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 lg:hidden">Estudio</p>
                        <p class="truncate text-sm font-bold text-slate-900">{{ $study->name }}</p>
                        @if ($study->consultation)
                            <a
                                href="{{ route('consultations.show', ['uuid' => $study->consultation->uuid]) }}"
                                wire:navigate
                                class="mt-1 inline-block text-xs font-semibold text-blue-600 hover:text-blue-700"
                            >
                                Consulta relacionada
                            </a>
                        @endif
                    </div>

                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 lg:hidden">Laboratorio / proveedor</p>
                        <p class="truncate text-sm text-slate-600">{{ $study->laboratory_name ?: '—' }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 lg:hidden">Parámetros</p>
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                            {{ $study->results->count() }}
                            {{ $study->results->count() === 1 ? 'parámetro' : 'parámetros' }}
                        </span>
                    </div>

                    <div class="flex flex-wrap justify-start gap-2 lg:justify-end">
                        <button
                            type="button"
                            x-on:click="open = !open"
                            class="dt-btn dt-btn-secondary"
                        >
                            <span x-text="open ? 'Ocultar' : 'Ver'">Ver</span>
                        </button>

                        <button type="button" wire:click="editStudy({{ $study->id }})" class="dt-btn dt-btn-secondary">
                            Editar
                        </button>

                        <button
                            type="button"
                            x-on:click="
                                Swal.fire({
                                    title: '¿Eliminar estudio de laboratorio?',
                                    text: 'Los resultados asociados también serán eliminados.',
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonText: 'Sí, eliminar',
                                    cancelButtonText: 'Cancelar',
                                    reverseButtons: true,
                                    focusCancel: true
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        $wire.deleteStudy({{ $study->id }})
                                    }
                                })
                            "
                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-rose-100 bg-white text-rose-600 transition hover:bg-rose-50"
                            title="Eliminar estudio"
                            aria-label="Eliminar estudio"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                                <path d="M4 7h16M9 7V4h6v3M8 7l1 13h6l1-13M10 11v5M14 11v5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div x-show="open" x-collapse x-cloak class="border-t border-slate-100 bg-slate-50/60 px-5 py-4">
                    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50">
                                <tr class="border-b border-slate-200 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                                    <th class="px-4 py-3">Parámetro</th>
                                    <th class="px-4 py-3">Valor</th>
                                    <th class="px-4 py-3">Unidad</th>
                                    <th class="px-4 py-3">Rango de referencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($study->results as $result)
                                    <tr class="border-b border-slate-100 last:border-0">
                                        <td class="px-4 py-3 font-semibold text-slate-800">{{ $result->parameter_name }}</td>
                                        <td class="px-4 py-3 font-semibold text-slate-950">{{ $result->value }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $result->unit ?: '—' }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $result->reference_range ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($study->notes)
                        <div class="mt-3 rounded-xl border border-slate-200 bg-white px-4 py-3">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Observaciones</p>
                            <p class="mt-1 text-sm leading-6 text-slate-600">{{ $study->notes }}</p>
                        </div>
                    @endif
                </div>
            </article>
        @empty
            <div class="px-5 py-12 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                        <path d="M9 3h6M10 3v5.2l-5.2 8.4A2.8 2.8 0 0 0 7.2 21h9.6a2.8 2.8 0 0 0 2.4-4.4L14 8.2V3" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M7.5 15h9" stroke-linecap="round"/>
                    </svg>
                </div>
                <p class="mt-3 font-semibold text-slate-700">Aún no hay estudios de laboratorio.</p>
                <p class="mt-1 text-sm text-slate-500">
                    Registra el primer estudio para comenzar el historial estructurado.
                </p>

                @if (! $showForm)
                    <button type="button" wire:click="createStudy" class="dt-btn dt-btn-primary mt-4">
                        Registrar primer estudio
                    </button>
                @endif
            </div>
        @endforelse
    </section>
</div>
