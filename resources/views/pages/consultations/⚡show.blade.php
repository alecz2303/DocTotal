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
            $this->editingDiagnosisId = null;

            $this->resetDiagnosisForm();
            $this->resetValidation();

            $this->showDiagnosisModal = true;
        }

        public function editDiagnosis(int $diagnosisId): void
        {
            $diagnosis = ConsultationDiagnosis::query()
                ->where('consultation_id', $this->consultation->id)
                ->findOrFail($diagnosisId);

            $this->editingDiagnosisId = $diagnosis->id;

            $this->diagnosisSearch = '';

            $this->diagnosis_code = $diagnosis->code ?? '';
            $this->diagnosis_description = $diagnosis->description;
            $this->diagnosis_is_primary = $diagnosis->is_primary;
            $this->diagnosis_notes = $diagnosis->notes ?? '';

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
            $validated = $this->validateDiagnosis();

            if ($validated['diagnosis_is_primary']) {
                ConsultationDiagnosis::query()
                    ->where('consultation_id', $this->consultation->id)
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
                    ->where('consultation_id', $this->consultation->id)
                    ->findOrFail($this->editingDiagnosisId);

                $diagnosis->update([
                    'code' => $validated['diagnosis_code'] ?: null,
                    'description' => $validated['diagnosis_description'],
                    'is_primary' => $validated['diagnosis_is_primary'],
                    'notes' => $validated['diagnosis_notes'] ?: null,
                ]);

                $message = 'Diagnóstico actualizado correctamente.';
            } else {
                ConsultationDiagnosis::create([
                    'consultation_id' => $this->consultation->id,
                    'code' => $validated['diagnosis_code'] ?: null,
                    'description' => $validated['diagnosis_description'],
                    'is_primary' => $validated['diagnosis_is_primary'],
                    'notes' => $validated['diagnosis_notes'] ?: null,
                ]);

                $message = 'Diagnóstico registrado correctamente.';
            }

            $this->consultation->unsetRelation('diagnoses');

            $this->showDiagnosisModal = false;
            $this->editingDiagnosisId = null;

            $this->resetDiagnosisForm();

            session()->flash('success', $message);

            $this->redirectRoute(
                'consultations.show',
                ['uuid' => $this->consultation->uuid]
            );

            $this->dispatch('diagnosis-saved');
        }

        public function deleteDiagnosis(int $diagnosisId): void
        {
            $diagnosis = ConsultationDiagnosis::query()
                ->where('consultation_id', $this->consultation->id)
                ->findOrFail($diagnosisId);

            $diagnosis->delete();

            $this->consultation->unsetRelation('diagnoses');

            session()->flash(
                'success',
                'Diagnóstico eliminado correctamente.'
            );

            $this->redirectRoute(
                'consultations.show',
                ['uuid' => $this->consultation->uuid]
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

<div class="mx-auto max-w-5xl">

    <div class="mb-8">

        <div class="flex flex-wrap items-center gap-4">

            <a
                href="{{ route('consultations.index') }}"
                class="text-sm font-medium text-slate-500
                    hover:text-slate-900">
                ← Consultas
            </a>

            <a
                href="{{ route('patients.show', [
                    'uuid' => $consultation->patient->uuid
                ]) }}"
                class="text-sm font-medium text-slate-500
                    hover:text-slate-900">
                Expediente del paciente
            </a>

        </div>

        <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">

            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                    Consulta
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    {{ $consultation->patient->first_name }}
                    {{ $consultation->patient->last_name }}
                </p>
            </div>

            <div class="text-left sm:text-right">

                <p class="text-sm font-medium text-slate-900">
                    {{ $consultation->consultation_at->format('d/m/Y') }}
                </p>

                <p class="text-sm text-slate-500">
                    {{ $consultation->consultation_at->format('H:i') }}
                </p>

                <div class="mt-3 flex flex-wrap gap-2 sm:justify-end">

                    <a
                        href="{{ route('patients.show', [
                            'uuid' => $consultation->patient->uuid
                        ]) }}"
                        class="inline-flex items-center rounded-lg
                            border border-slate-300 px-4 py-2.5
                            text-sm font-semibold text-slate-700
                            hover:bg-slate-50">
                        Ver paciente
                    </a>

                    <button
                        type="button"
                        wire:click="openDiagnosisModal"
                        class="inline-flex items-center rounded-lg
                            border border-slate-300 px-4 py-2.5
                            text-sm font-semibold text-slate-700
                            hover:bg-slate-50">
                        Crear diagnóstico
                    </button>

                    <a
                        href="{{ route('prescriptions.create', [
                            'uuid' => $consultation->uuid
                        ]) }}"
                        class="inline-flex items-center rounded-lg
                            bg-slate-900 px-4 py-2.5
                            text-sm font-semibold text-white
                            hover:bg-slate-800">
                        Crear receta
                    </a>

                </div>

            </div>

        </div>

    </div>

    <div class="space-y-6">

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="font-semibold text-slate-900">
                    Datos generales
                </h2>
            </div>

            <div class="grid gap-5 p-6 sm:grid-cols-2">

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                        Motivo de consulta
                    </p>

                    <p class="mt-1 text-sm text-slate-700">
                        {{ $consultation->reason ?: 'Sin registro' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                        Médico
                    </p>

                    <p class="mt-1 text-sm text-slate-700">
                        {{ $consultation->doctorProfile->first_name }}
                        {{ $consultation->doctorProfile->last_name }}
                    </p>
                </div>

            </div>

        </div>

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="font-semibold text-slate-900">
                    Signos vitales
                </h2>
            </div>

            <div class="grid gap-5 p-6 sm:grid-cols-2 lg:grid-cols-4">

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                        Peso
                    </p>

                    <p class="mt-1 text-sm font-medium text-slate-900">
                        {{ $consultation->weight_kg
                            ? $consultation->weight_kg . ' kg'
                            : '—' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                        Estatura
                    </p>

                    <p class="mt-1 text-sm font-medium text-slate-900">
                        {{ $consultation->height_cm
                            ? $consultation->height_cm . ' cm'
                            : '—' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                        Presión arterial
                    </p>

                    <p class="mt-1 text-sm font-medium text-slate-900">
                        @if ($consultation->systolic_bp && $consultation->diastolic_bp)
                        {{ $consultation->systolic_bp }}/{{ $consultation->diastolic_bp }} mmHg
                        @else
                        —
                        @endif
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                        Frecuencia cardiaca
                    </p>

                    <p class="mt-1 text-sm font-medium text-slate-900">
                        {{ $consultation->heart_rate
                            ? $consultation->heart_rate . ' lpm'
                            : '—' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                        Frecuencia respiratoria
                    </p>

                    <p class="mt-1 text-sm font-medium text-slate-900">
                        {{ $consultation->respiratory_rate
                            ? $consultation->respiratory_rate . ' rpm'
                            : '—' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                        Temperatura
                    </p>

                    <p class="mt-1 text-sm font-medium text-slate-900">
                        {{ $consultation->temperature_c
                            ? $consultation->temperature_c . ' °C'
                            : '—' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                        Saturación O₂
                    </p>

                    <p class="mt-1 text-sm font-medium text-slate-900">
                        {{ $consultation->oxygen_saturation
                            ? $consultation->oxygen_saturation . '%'
                            : '—' }}
                    </p>
                </div>

            </div>

        </div>

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-6 py-4">

                <h2 class="font-semibold text-slate-900">
                    Nota clínica
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Formato SOAP
                </p>

            </div>

            <div class="grid gap-6 p-6 sm:grid-cols-2">

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                        Subjetivo
                    </p>

                    <p class="mt-2 whitespace-pre-line text-sm text-slate-700">
                        {{ $consultation->subjective ?: 'Sin registro' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                        Objetivo
                    </p>

                    <p class="mt-2 whitespace-pre-line text-sm text-slate-700">
                        {{ $consultation->objective ?: 'Sin registro' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                        Evaluación / diagnóstico
                    </p>

                    <p class="mt-2 whitespace-pre-line text-sm text-slate-700">
                        {{ $consultation->assessment ?: 'Sin registro' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                        Plan
                    </p>

                    <p class="mt-2 whitespace-pre-line text-sm text-slate-700">
                        {{ $consultation->plan ?: 'Sin registro' }}
                    </p>
                </div>

            </div>

        </div>

        <div id="diagnosticos" class="rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">

                <div>
                    <h2 class="font-semibold text-slate-900">
                        Diagnósticos
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Diagnósticos asociados a esta consulta.
                    </p>
                </div>

                <button
                    type="button"
                    wire:click="openDiagnosisModal"
                    class="text-sm font-semibold text-slate-700 hover:text-slate-900">
                    + Agregar diagnóstico
                </button>

            </div>

            <div>

                @forelse ($consultation->diagnoses as $diagnosis)

                <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-5 last:border-0 sm:flex-row sm:items-start sm:justify-between">

                    <div>

                        <div class="flex flex-wrap items-center gap-2">

                            @if ($diagnosis->is_primary)
                            <span class="rounded-full bg-slate-900 px-2 py-0.5 text-xs font-medium text-white">
                                Principal
                            </span>
                            @endif

                            @if ($diagnosis->code)
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                                {{ $diagnosis->code }}
                            </span>
                            @endif

                        </div>

                        <p class="mt-2 font-medium text-slate-900">
                            {{ $diagnosis->description }}
                        </p>

                        @if ($diagnosis->notes)
                        <p class="mt-2 whitespace-pre-line text-sm text-slate-500">
                            {{ $diagnosis->notes }}
                        </p>
                        @endif

                    </div>

                    <div class="flex items-center gap-3">

                        <button
                            type="button"
                            wire:click="editDiagnosis({{ $diagnosis->id }})"
                            class="text-xs font-semibold text-slate-600 hover:text-slate-900">
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
                            class="text-xs font-semibold text-red-600 hover:text-red-700">
                            Eliminar
                        </button>

                    </div>

                </div>

                @empty

                <div class="px-6 py-10 text-center">

                    <p class="font-medium text-slate-700">
                        Sin diagnósticos registrados
                    </p>

                    <p class="mt-1 text-sm text-slate-500">
                        Agrega uno o más diagnósticos para esta consulta.
                    </p>

                </div>

                @endforelse

            </div>

        </div>

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="flex items-center justify-between
                        border-b border-slate-200 px-6 py-4">

                <div>

                    <h2 class="font-semibold text-slate-900">
                        Recetas
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Recetas asociadas a esta consulta.
                    </p>

                </div>

                <a
                    href="{{ route('prescriptions.create', [
                        'uuid' => $consultation->uuid
                    ]) }}"
                    class="text-sm font-semibold text-slate-700
                        hover:text-slate-900">
                    + Crear receta
                </a>

            </div>

            <div>

                @forelse (
                $consultation->prescriptions()
                ->latest('prescribed_at')
                ->get()
                as $prescription
                )

                <div class="flex items-center justify-between
                                border-b border-slate-100
                                px-6 py-5 last:border-0">

                    <div>

                        <p class="font-medium text-slate-900">
                            {{ $prescription->prescribed_at->format('d/m/Y H:i') }}
                        </p>

                        <p class="mt-1 text-sm text-slate-500">
                            {{ $prescription->items()->count() }}
                            medicamento(s)
                        </p>

                    </div>

                    <a
                        href="{{ route('prescriptions.show', [
                                'uuid' => $prescription->uuid
                            ]) }}"
                        class="text-sm font-semibold text-slate-700
                                hover:text-slate-900">
                        Ver receta
                    </a>

                </div>

                @empty

                <div class="px-6 py-10 text-center">

                    <p class="font-medium text-slate-700">
                        Sin recetas
                    </p>

                    <p class="mt-1 text-sm text-slate-500">
                        Las recetas de esta consulta aparecerán aquí.
                    </p>

                </div>

                @endforelse

            </div>

        </div>

    </div>

    @if ($showDiagnosisModal)

    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">

        <div class="w-full max-w-xl rounded-2xl bg-white shadow-xl">

            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">

                <div>
                    <h2 class="text-lg font-semibold text-slate-900">
                        {{ $editingDiagnosisId
                                ? 'Editar diagnóstico'
                                : 'Nuevo diagnóstico' }}
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Registra un diagnóstico asociado a esta consulta.
                    </p>
                </div>

                <button
                    type="button"
                    wire:click="closeDiagnosisModal"
                    class="text-2xl leading-none text-slate-400 hover:text-slate-700">
                    ×
                </button>

            </div>

            <form wire:submit="saveDiagnosis">

                <div class="space-y-5 p-6">

                    <div class="relative">

                        <label class="mb-1 block text-sm font-medium">
                            Buscar en catálogo CIE-10
                        </label>

                        <input
                            wire:model.live.debounce.300ms="diagnosisSearch"
                            type="search"
                            autocomplete="off"
                            placeholder="Escribe código o diagnóstico, ej. cefalea..."
                            class="w-full rounded-lg border border-slate-300 px-3 py-2">

                        @if (mb_strlen(trim($diagnosisSearch)) >= 2)

                        <div
                            class="absolute z-20 mt-1 max-h-72 w-full
                                    overflow-y-auto rounded-lg border
                                    border-slate-200 bg-white shadow-lg">

                            @forelse ($this->diagnosisResults as $result)

                            <button
                                type="button"
                                wire:click="selectDiagnosis({{ $result->id }})"
                                class="block w-full border-b border-slate-100
                                            px-4 py-3 text-left last:border-0
                                            hover:bg-slate-50">

                                <div class="flex items-start gap-3">

                                    <span
                                        class="shrink-0 rounded bg-slate-100
                                                    px-2 py-1 text-xs font-semibold
                                                    text-slate-700">
                                        {{ $result->code }}
                                    </span>

                                    <span class="text-sm text-slate-700">
                                        {{ $result->description }}
                                    </span>

                                </div>

                            </button>

                            @empty

                            <div class="px-4 py-4">

                                <p class="text-sm font-medium text-slate-700">
                                    Sin coincidencias
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    Puedes capturar el diagnóstico manualmente.
                                </p>

                            </div>

                            @endforelse

                        </div>

                        @endif

                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Código CIE-10
                        </label>

                        <input
                            wire:model="diagnosis_code"
                            type="text"
                            placeholder="Ej. R51.9"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Descripción *
                        </label>

                        <input
                            wire:model="diagnosis_description"
                            type="text"
                            placeholder="Ej. Cefalea no especificada"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2">

                        @error('diagnosis_description')
                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Notas
                        </label>

                        <textarea
                            wire:model="diagnosis_notes"
                            rows="4"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                    </div>

                    <label class="flex items-center gap-3">

                        <input
                            wire:model="diagnosis_is_primary"
                            type="checkbox">

                        <span class="text-sm font-medium text-slate-700">
                            Marcar como diagnóstico principal
                        </span>

                    </label>

                </div>

                <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">

                    <button
                        type="button"
                        wire:click="closeDiagnosisModal"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium">
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">
                        <span
                            wire:loading.remove
                            wire:target="saveDiagnosis">
                            {{ $editingDiagnosisId
                                    ? 'Guardar cambios'
                                    : 'Guardar diagnóstico' }}
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