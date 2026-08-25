<?php

use App\Models\Consultation;
use App\Models\DoctorProfile;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\MedicationCatalog;

new
    #[Layout('layouts::app')]
    #[Title('Nueva receta | DocTotal')]
    class extends Component
    {
        public Consultation $consultation;

        public string $prescribed_at = '';
        public string $general_instructions = '';

        public array $items = [];

        public function mount(string $uuid): void
        {
            $this->consultation = Consultation::query()
                ->with([
                    'patient',
                    'doctorProfile',
                ])
                ->where('uuid', $uuid)
                ->firstOrFail();

            if (! $this->consultation->isCompleted()) {
                abort(404);
            }

            $this->prescribed_at = now()
                ->format('Y-m-d\TH:i');

            $this->addMedication();
        }

        public function addMedication(): void
        {
            $this->items[] = [
                'search' => '',
                'medication_catalog_id' => null,
                'medication_name' => '',
                'presentation' => '',
                'dose' => '',
                'frequency' => '',
                'duration' => '',
                'instructions' => '',
            ];
        }

        public function removeMedication(int $index): void
        {
            if (count($this->items) <= 1) {
                return;
            }

            unset($this->items[$index]);

            $this->items = array_values($this->items);
        }

        public function savePrescription(): void
        {
            $validated = $this->validate([
                'prescribed_at' => [
                    'required',
                    'date',
                ],

                'general_instructions' => [
                    'nullable',
                    'string',
                    'max:5000',
                ],

                'items' => [
                    'required',
                    'array',
                    'min:1',
                ],

                'items.*.medication_name' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'items.*.presentation' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'items.*.dose' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'items.*.frequency' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'items.*.duration' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'items.*.instructions' => [
                    'nullable',
                    'string',
                    'max:5000',
                ],
            ]);

            DB::transaction(function () use ($validated): void {
                $doctor = DoctorProfile::query()
                    ->where('user_id', auth()->id())
                    ->firstOrFail();

                $prescription = Prescription::create([
                    'patient_id' => $this->consultation->patient_id,
                    'doctor_profile_id' => $doctor->id,
                    'consultation_id' => $this->consultation->id,
                    'prescribed_at' => $validated['prescribed_at'],
                    'general_instructions' =>
                    $validated['general_instructions'] ?: null,
                    'status' => 'active',
                ]);

                foreach ($validated['items'] as $index => $item) {
                    PrescriptionItem::create([
                        'prescription_id' => $prescription->id,
                        'medication_name' =>
                        $item['medication_name'],
                        'presentation' =>
                        $item['presentation'] ?: null,
                        'dose' =>
                        $item['dose'] ?: null,
                        'frequency' =>
                        $item['frequency'] ?: null,
                        'duration' =>
                        $item['duration'] ?: null,
                        'instructions' =>
                        $item['instructions'] ?: null,
                        'sort_order' => $index + 1,
                    ]);
                }

                session()->flash(
                    'success',
                    'Receta registrada correctamente.'
                );

                $this->redirectRoute(
                    'prescriptions.show',
                    ['uuid' => $prescription->uuid]
                );
            });
        }

        public function medicationResults(int $index)
        {
            $search = trim(
                $this->items[$index]['search'] ?? ''
            );

            if (mb_strlen($search) < 2) {
                return collect();
            }

            return MedicationCatalog::query()
                ->where('active', true)
                ->where(function ($query) use ($search) {
                    $query
                        ->where(
                            'name',
                            'like',
                            '%' . $search . '%'
                        )
                        ->orWhere(
                            'presentation',
                            'like',
                            '%' . $search . '%'
                        )
                        ->orWhere(
                            'code',
                            'like',
                            $search . '%'
                        );
                })
                ->orderBy('name')
                ->orderBy('presentation')
                ->limit(10)
                ->get();
        }

        public function selectMedication(
            int $index,
            int $medicationId
        ): void {
            if (! isset($this->items[$index])) {
                return;
            }

            $medication = MedicationCatalog::query()
                ->where('active', true)
                ->findOrFail($medicationId);

            $description = trim($medication->name);

            $parts = collect(
                preg_split('/\.\s*/', $description)
            )
                ->filter()
                ->values();

            $name = trim(
                (string) ($parts->get(0) ?? $description)
            );

            $pharmaceuticalForm = '';
            $strength = '';
            $package = '';

            /*
            * Ejemplo:
            *
            * Paracetamol.
            * Tableta Cada Tableta Contiene: Paracetamol 500 Mg.
            * Envase Con 10 Tabletas.
            */

            $secondPart = trim(
                (string) ($parts->get(1) ?? '')
            );

            if ($secondPart !== '') {
                /*
                * Intentamos separar la forma farmacéutica
                * del texto "Cada ... contiene".
                */
                if (
                    preg_match(
                        '/^(.*?)(?=\s+Cada\s+)/iu',
                        $secondPart,
                        $matches
                    )
                ) {
                    $pharmaceuticalForm = trim(
                        $matches[1]
                    );
                } else {
                    $pharmaceuticalForm = $secondPart;
                }

                /*
                * Concentraciones habituales:
                * 500 Mg
                * 1 G
                * 100 Mg/5 Ml
                * 10 %
                * 200 μg
                */
                if (
                    preg_match(
                        '/(\d+(?:[.,]\d+)?\s*'
                            . '(?:mg|g|mcg|μg|ug|ml|%|ui)'
                            . '(?:\s*\/\s*'
                            . '\d+(?:[.,]\d+)?\s*'
                            . '(?:mg|g|mcg|μg|ug|ml|ui))?)/iu',
                        $secondPart,
                        $matches
                    )
                ) {
                    $strength = trim(
                        $matches[1]
                    );
                }
            }

            /*
            * Normalmente el envase viene en el tercer segmento.
            */
            foreach ($parts->slice(2) as $part) {
                $part = trim((string) $part);

                if (
                    preg_match(
                        '/\bEnvase\b/iu',
                        $part
                    )
                ) {
                    $package = $part;

                    break;
                }
            }

            $presentationParts = collect([
                $pharmaceuticalForm,
                $strength,
                $package,
            ])
                ->filter(
                    fn($value) => trim((string) $value) !== ''
                )
                ->unique()
                ->values();

            $presentation = $presentationParts
                ->implode(' · ');

            /*
            * Fallback por si una descripción oficial no sigue
            * el patrón esperado.
            */
            if ($presentation === '') {
                $presentation = $medication->presentation ?? '';
            }

            $this->items[$index]['medication_catalog_id'] =
                $medication->id;

            $this->items[$index]['medication_name'] =
                $name;

            $this->items[$index]['presentation'] =
                $presentation;

            $this->items[$index]['search'] = '';
        }
    };
?>

<div class="mx-auto max-w-5xl">

    <div class="mb-8">

        <a
            href="{{ route('consultations.show', [
                'uuid' => $consultation->uuid
            ]) }}"
            class="text-sm font-medium text-slate-500 hover:text-slate-900">
            ← Volver a la consulta
        </a>

        <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-900">
            Nueva receta
        </h1>

        <p class="mt-1 text-sm text-slate-500">
            {{ $consultation->patient->first_name }}
            {{ $consultation->patient->last_name }}
        </p>

    </div>

    <form wire:submit="savePrescription" class="space-y-6">

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="font-semibold text-slate-900">
                    Datos de la receta
                </h2>
            </div>

            <div class="grid gap-5 p-6 sm:grid-cols-2">

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Fecha y hora *
                    </label>

                    <input
                        wire:model="prescribed_at"
                        type="datetime-local"
                        class="w-full rounded-lg border
                               border-slate-300 px-3 py-2">

                    @error('prescribed_at')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                    @enderror
                </div>

            </div>

        </div>

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">

                <div>
                    <h2 class="font-semibold text-slate-900">
                        Medicamentos
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Agrega uno o más medicamentos.
                    </p>
                </div>

                <button
                    type="button"
                    wire:click="addMedication"
                    class="text-sm font-semibold text-slate-700
                           hover:text-slate-900">
                    + Agregar medicamento
                </button>

            </div>

            <div class="space-y-5 p-6">

                @foreach ($items as $index => $item)

                <div
                    wire:key="prescription-item-{{ $index }}"
                    class="rounded-xl border border-slate-200 p-5">

                    <div class="mb-4 flex items-center justify-between">

                        <p class="font-medium text-slate-900">
                            Medicamento {{ $index + 1 }}
                        </p>

                        @if (count($items) > 1)

                        <button
                            type="button"
                            wire:click="removeMedication({{ $index }})"
                            class="text-xs font-semibold text-red-600
                                           hover:text-red-700">
                            Eliminar
                        </button>

                        @endif

                    </div>

                    <div class="relative sm:col-span-2">

                        <label class="mb-1 block text-sm font-medium">
                            Buscar medicamento
                        </label>

                        <input
                            wire:model.live.debounce.300ms="items.{{ $index }}.search"
                            type="search"
                            autocomplete="off"
                            placeholder="Busca por nombre, clave o presentación..."
                            class="w-full rounded-lg border
                                border-slate-300 px-3 py-2">

                        @php
                        $medicationResults =
                        $this->medicationResults($index);
                        @endphp

                        @if (
                        mb_strlen(
                        trim($items[$index]['search'] ?? '')
                        ) >= 2
                        )

                        <div
                            class="absolute z-20 mt-1 max-h-96
                            w-full overflow-y-auto rounded-lg
                            border border-slate-200
                            bg-white shadow-xl">

                            @forelse ($medicationResults as $result)

                            @php
                            $parts = collect(
                            preg_split(
                            '/\.\s*/',
                            trim($result->name)
                            )
                            )
                            ->filter()
                            ->values();

                            $title = trim(
                            (string) ($parts->get(0) ?? $result->name)
                            );

                            $summary = $parts
                            ->slice(1)
                            ->take(2)
                            ->map(
                            fn ($part) => trim((string) $part)
                            )
                            ->filter()
                            ->implode('. ');
                            @endphp

                            <button
                                type="button"
                                wire:click="selectMedication(
                                    {{ $index }},
                                    {{ $result->id }}
                                )"
                                class="block w-full border-b border-slate-100
                                    px-4 py-3 text-left last:border-0
                                    hover:bg-slate-50">
                                <div class="space-y-1">

                                    <div class="flex flex-wrap items-center gap-2">

                                        @if ($result->code)
                                        <span
                                            class="rounded bg-slate-100 px-2 py-0.5
                                                    text-xs font-semibold text-slate-600">
                                            {{ $result->code }}
                                        </span>
                                        @endif

                                        @if ($result->therapeutic_group)
                                        <span
                                            class="rounded bg-slate-50 px-2 py-0.5
                                                    text-xs font-medium text-slate-500">
                                            {{ $result->therapeutic_group }}
                                        </span>
                                        @endif

                                    </div>

                                    <p class="text-sm font-semibold text-slate-900">
                                        {{ $title }}
                                    </p>

                                    @if ($summary)
                                    <p class="text-xs leading-5 text-slate-500">
                                        {{ $summary }}
                                    </p>
                                    @endif

                                </div>
                            </button>

                            @empty

                            <div class="px-4 py-4">

                                <p class="text-sm font-medium text-slate-700">
                                    Sin coincidencias
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    Puedes capturar el medicamento manualmente.
                                </p>

                            </div>

                            @endforelse

                        </div>

                        @endif

                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">

                        <div class="sm:col-span-2">

                            <label class="mb-1 block text-sm font-medium">
                                Medicamento *
                            </label>

                            <input
                                wire:model="items.{{ $index }}.medication_name"
                                type="text"
                                placeholder="Ej. Paracetamol"
                                class="w-full rounded-lg border
                                           border-slate-300 px-3 py-2">

                            @error("items.$index.medication_name")
                            <p class="mt-1 text-sm text-red-600">
                                {{ $message }}
                            </p>
                            @enderror

                        </div>

                        <div>

                            <label class="mb-1 block text-sm font-medium">
                                Presentación
                            </label>

                            <input
                                wire:model="items.{{ $index }}.presentation"
                                type="text"
                                placeholder="Ej. Tabletas 500 mg"
                                class="w-full rounded-lg border
                                           border-slate-300 px-3 py-2">

                        </div>

                        <div>

                            <label class="mb-1 block text-sm font-medium">
                                Dosis
                            </label>

                            <input
                                wire:model="items.{{ $index }}.dose"
                                type="text"
                                placeholder="Ej. 1 tableta"
                                class="w-full rounded-lg border
                                           border-slate-300 px-3 py-2">

                        </div>

                        <div>

                            <label class="mb-1 block text-sm font-medium">
                                Frecuencia
                            </label>

                            <input
                                wire:model="items.{{ $index }}.frequency"
                                type="text"
                                placeholder="Ej. Cada 8 horas"
                                class="w-full rounded-lg border
                                           border-slate-300 px-3 py-2">

                        </div>

                        <div>

                            <label class="mb-1 block text-sm font-medium">
                                Duración
                            </label>

                            <input
                                wire:model="items.{{ $index }}.duration"
                                type="text"
                                placeholder="Ej. 5 días"
                                class="w-full rounded-lg border
                                           border-slate-300 px-3 py-2">

                        </div>

                        <div class="sm:col-span-2">

                            <label class="mb-1 block text-sm font-medium">
                                Indicaciones
                            </label>

                            <textarea
                                wire:model="items.{{ $index }}.instructions"
                                rows="3"
                                placeholder="Ej. Tomar después de alimentos."
                                class="w-full rounded-lg border
                                           border-slate-300 px-3 py-2"></textarea>

                        </div>

                    </div>

                </div>

                @endforeach

            </div>

        </div>

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="font-semibold text-slate-900">
                    Indicaciones generales
                </h2>
            </div>

            <div class="p-6">

                <textarea
                    wire:model="general_instructions"
                    rows="4"
                    placeholder="Ej. Mantener hidratación adecuada y acudir a revisión si los síntomas empeoran."
                    class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>

            </div>

        </div>

        <div class="flex justify-end gap-3">

            <a
                href="{{ route('consultations.show', [
                    'uuid' => $consultation->uuid
                ]) }}"
                class="rounded-lg border border-slate-300
                       px-4 py-2.5 text-sm font-medium text-slate-700">
                Cancelar
            </a>

            <button
                type="submit"
                wire:loading.attr="disabled"
                class="rounded-lg bg-slate-900 px-5 py-2.5
                       text-sm font-semibold text-white
                       disabled:opacity-50">
                <span
                    wire:loading.remove
                    wire:target="savePrescription">
                    Guardar receta
                </span>

                <span
                    wire:loading
                    wire:target="savePrescription">
                    Guardando...
                </span>
            </button>

        </div>

    </form>

</div>