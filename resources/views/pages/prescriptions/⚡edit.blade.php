<?php

use App\Models\MedicationCatalog;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
    #[Layout('layouts::app')]
    #[Title('Editar receta | DocTotal')]
    class extends Component
    {
        public Prescription $prescription;

        public string $prescribed_at = '';
        public string $general_instructions = '';

        public array $items = [];

        public function mount(string $uuid): void
        {
            $this->prescription = Prescription::query()
                ->with([
                    'patient',
                    'doctorProfile',
                    'consultation',
                    'items',
                ])
                ->where('uuid', $uuid)
                ->where('status', 'active')
                ->firstOrFail();

            $this->prescribed_at = $this->prescription
                ->prescribed_at
                ->format('Y-m-d\TH:i');

            $this->general_instructions =
                $this->prescription->general_instructions ?? '';

            $this->items = $this->prescription->items
                ->map(fn($item) => [
                    'id' => $item->id,
                    'search' => '',
                    'medication_catalog_id' => null,
                    'medication_name' => $item->medication_name,
                    'presentation' => $item->presentation ?? '',
                    'dose' => $item->dose ?? '',
                    'frequency' => $item->frequency ?? '',
                    'duration' => $item->duration ?? '',
                    'instructions' => $item->instructions ?? '',
                ])
                ->values()
                ->all();

            if ($this->items === []) {
                $this->addMedication();
            }
        }

        public function addMedication(): void
        {
            $this->items[] = [
                'id' => null,
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

            $secondPart = trim(
                (string) ($parts->get(1) ?? '')
            );

            if ($secondPart !== '') {
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

                if (
                    preg_match(
                        '/(\d+(?:[.,]\d+)?\s*'
                            . '(?:mg|g|mcg|µg|ug|ml|%|ui)'
                            . '(?:\s*\/\s*'
                            . '\d+(?:[.,]\d+)?\s*'
                            . '(?:mg|g|mcg|µg|ug|ml|ui))?)/iu',
                        $secondPart,
                        $matches
                    )
                ) {
                    $strength = trim(
                        $matches[1]
                    );
                }
            }

            foreach ($parts->slice(2) as $part) {
                $part = trim((string) $part);

                if (preg_match('/\bEnvase\b/iu', $part)) {
                    $package = $part;

                    break;
                }
            }

            $presentation = collect([
                $pharmaceuticalForm,
                $strength,
                $package,
            ])
                ->filter(
                    fn($value) => trim((string) $value) !== ''
                )
                ->unique()
                ->implode(' · ');

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

        public function updatePrescription(): void
        {
            abort_unless(
                $this->prescription->status === 'active',
                404
            );

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
                    'max:500',
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
                $this->prescription->update([
                    'prescribed_at' => $validated['prescribed_at'],
                    'general_instructions' =>
                    $validated['general_instructions'] ?: null,
                ]);

                /*
             * Para este flujo es más simple y seguro
             * reconstruir los items completos.
             */
                $this->prescription->items()->delete();

                foreach ($validated['items'] as $index => $item) {
                    PrescriptionItem::create([
                        'prescription_id' => $this->prescription->id,
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
            });

            $this->dispatch(
                'swal',
                title: 'Receta actualizada',
                text: 'Los cambios se guardaron correctamente.',
                icon: 'success'
            );

            $this->redirectRoute(
                'prescriptions.show',
                [
                    'uuid' => $this->prescription->uuid,
                ]
            );
        }
    };
?>

<div class="mx-auto max-w-6xl">

    {{-- HEADER --}}
    <div class="mb-6">
        <a href="{{ route('prescriptions.show', ['uuid' => $prescription->uuid]) }}"
            class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-blue-600">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" class="h-4 w-4">
                <path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            Volver a la receta
        </a>

        <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="h-1 bg-gradient-to-r from-violet-500 via-blue-500 to-cyan-500"></div>

            <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                <div class="flex min-w-0 items-center gap-4">
                    <div class="flex h-13 w-13 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500 to-blue-600 text-white shadow-sm">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-6 w-6">
                            <path d="M7 3h10v18H7z" />
                            <path d="M10 8h4M10 12h4M10 16h3" stroke-linecap="round" />
                            <path d="m15.5 18.5 4-4 1.5 1.5-4 4-2 .5.5-2Z" fill="currentColor" stroke="none" />
                        </svg>
                    </div>

                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-2xl font-bold tracking-tight text-slate-950">Editar receta</h1>
                            <span class="rounded-full bg-violet-50 px-2.5 py-1 text-[11px] font-bold text-violet-700 ring-1 ring-inset ring-violet-100">
                                En edición
                            </span>
                        </div>

                        <p class="mt-1 truncate font-semibold text-slate-700">
                            {{ $prescription->patient->first_name }}
                            {{ $prescription->patient->last_name }}
                            {{ $prescription->patient->second_last_name }}
                        </p>

                        <p class="mt-1 text-sm text-slate-500">
                            Modifica únicamente los datos necesarios de la prescripción.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form wire:submit="updatePrescription" class="space-y-6">

        {{-- PRESCRIPTION DATA --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                        <circle cx="12" cy="12" r="8" />
                        <path d="M12 8v4l2.5 1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-slate-950">Datos de la receta</h2>
                    <p class="text-xs text-slate-500">Fecha y hora de emisión.</p>
                </div>
            </div>

            <div class="p-5 sm:p-6">
                <div class="max-w-md">
                    <label class="dt-label">Fecha y hora *</label>
                    <input wire:model="prescribed_at" type="datetime-local" class="dt-input">

                    @error('prescribed_at')
                    <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </section>

        {{-- MEDICATIONS --}}
        <section class="overflow-visible rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                            <path d="m8 16 8-8a4 4 0 0 1 5.7 5.7l-8 8A4 4 0 0 1 8 16Z" />
                            <path d="m10.5 13.5 4 4" stroke-linecap="round" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="font-semibold text-slate-950">Medicamentos</h2>
                        <p class="text-xs text-slate-500">Modifica, agrega o elimina medicamentos.</p>
                    </div>
                </div>

                <button type="button" wire:click="addMedication" class="dt-btn dt-btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                        <path d="M12 5v14M5 12h14" stroke-linecap="round" />
                    </svg>
                    Agregar medicamento
                </button>
            </div>

            <div class="space-y-5 p-5 sm:p-6">
                @foreach ($items as $index => $item)
                <article wire:key="prescription-edit-item-{{ $index }}"
                    class="relative rounded-2xl border border-slate-200 bg-slate-50/40 p-4 sm:p-5">

                    <div class="mb-5 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-blue-600 text-xs font-black text-white shadow-sm">
                                {{ $index + 1 }}
                            </span>
                            <div>
                                <p class="font-bold text-slate-900">Medicamento {{ $index + 1 }}</p>
                                <p class="text-xs text-slate-500">Edita los datos o selecciona otro del catálogo.</p>
                            </div>
                        </div>

                        @if (count($items) > 1)
                        <button type="button"
                            wire:click="removeMedication({{ $index }})"
                            class="rounded-lg px-2.5 py-1.5 text-xs font-bold text-rose-600 transition hover:bg-rose-50">
                            Eliminar
                        </button>
                        @endif
                    </div>

                    {{-- SEARCH --}}
                    <div class="relative mb-5">
                        <label class="dt-label">Buscar medicamento</label>

                        <div class="relative">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400">
                                <circle cx="11" cy="11" r="7" />
                                <path d="m20 20-3.5-3.5" />
                            </svg>

                            <input wire:model.live.debounce.300ms="items.{{ $index }}.search"
                                type="search"
                                autocomplete="off"
                                placeholder="Busca por nombre, clave o presentación..."
                                class="dt-input pl-10">
                        </div>

                        @php
                        $medicationResults = $this->medicationResults($index);
                        @endphp

                        @if (mb_strlen(trim($items[$index]['search'] ?? '')) >= 2)
                        <div class="absolute z-30 mt-2 max-h-96 w-full overflow-y-auto rounded-2xl border border-slate-200 bg-white p-1.5 shadow-2xl">
                            @forelse ($medicationResults as $result)
                            @php
                            $parts = collect(
                            preg_split('/\.\s*/', trim($result->name))
                            )->filter()->values();

                            $title = trim(
                            (string) ($parts->get(0) ?? $result->name)
                            );

                            $summary = $parts
                            ->slice(1)
                            ->take(2)
                            ->map(fn ($part) => trim((string) $part))
                            ->filter()
                            ->implode('. ');
                            @endphp

                            <button type="button"
                                wire:click="selectMedication({{ $index }}, {{ $result->id }})"
                                class="block w-full rounded-xl px-3 py-3 text-left transition hover:bg-blue-50">
                                <div class="space-y-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if ($result->code)
                                        <span class="rounded-lg bg-blue-50 px-2 py-1 text-[11px] font-bold text-blue-700">
                                            {{ $result->code }}
                                        </span>
                                        @endif

                                        @if ($result->therapeutic_group)
                                        <span class="rounded-lg bg-violet-50 px-2 py-1 text-[11px] font-semibold text-violet-700">
                                            {{ $result->therapeutic_group }}
                                        </span>
                                        @endif
                                    </div>

                                    <p class="text-sm font-bold text-slate-900">{{ $title }}</p>

                                    @if ($summary)
                                    <p class="text-xs leading-5 text-slate-500">{{ $summary }}</p>
                                    @endif
                                </div>
                            </button>
                            @empty
                            <div class="rounded-xl bg-slate-50 px-4 py-4">
                                <p class="text-sm font-semibold text-slate-700">Sin coincidencias</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Puedes capturar el medicamento manualmente.
                                </p>
                            </div>
                            @endforelse
                        </div>
                        @endif
                    </div>

                    {{-- FIELDS --}}
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="dt-label">Medicamento *</label>
                            <input wire:model="items.{{ $index }}.medication_name"
                                type="text"
                                placeholder="Ej. Paracetamol"
                                class="dt-input">

                            @error("items.$index.medication_name")
                            <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="dt-label">Presentación</label>
                            <input wire:model="items.{{ $index }}.presentation"
                                type="text"
                                placeholder="Ej. Tableta · 500 mg"
                                class="dt-input">
                        </div>

                        <div>
                            <label class="dt-label">Dosis</label>
                            <input wire:model="items.{{ $index }}.dose"
                                type="text"
                                placeholder="Ej. 1 tableta"
                                class="dt-input">
                        </div>

                        <div>
                            <label class="dt-label">Frecuencia</label>
                            <input wire:model="items.{{ $index }}.frequency"
                                type="text"
                                placeholder="Ej. Cada 8 horas"
                                class="dt-input">
                        </div>

                        <div>
                            <label class="dt-label">Duración</label>
                            <input wire:model="items.{{ $index }}.duration"
                                type="text"
                                placeholder="Ej. 7 días"
                                class="dt-input">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="dt-label">Indicaciones</label>
                            <textarea wire:model="items.{{ $index }}.instructions"
                                rows="3"
                                placeholder="Ej. Tomar cada 8 horas"
                                class="dt-textarea"></textarea>
                        </div>
                    </div>
                </article>
                @endforeach
            </div>
        </section>

        {{-- GENERAL INSTRUCTIONS --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                        <path d="M6 4h12v16H6z" />
                        <path d="M9 9h6M9 13h6" stroke-linecap="round" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-slate-950">Indicaciones generales</h2>
                    <p class="text-xs text-slate-500">Recomendaciones generales de la prescripción.</p>
                </div>
            </div>

            <div class="p-5 sm:p-6">
                <textarea wire:model="general_instructions"
                    rows="4"
                    placeholder="Indicaciones generales para el paciente..."
                    class="dt-textarea"></textarea>

                @error('general_instructions')
                <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </section>

        {{-- ACTIONS --}}
        <div class="sticky bottom-4 z-20 rounded-2xl border border-slate-200 bg-white/95 p-3 shadow-lg backdrop-blur">
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-between">
                <p class="hidden text-xs text-slate-500 sm:block">
                    Los cambios reemplazarán la información actual de la receta.
                </p>

                <div class="flex flex-col-reverse gap-2 sm:flex-row">
                    <a href="{{ route('prescriptions.show', ['uuid' => $prescription->uuid]) }}"
                        class="dt-btn dt-btn-secondary justify-center">
                        Cancelar
                    </a>

                    <button type="submit"
                        wire:loading.attr="disabled"
                        wire:target="updatePrescription"
                        class="dt-btn dt-btn-primary justify-center disabled:opacity-50">
                        <span wire:loading.remove wire:target="updatePrescription">
                            Guardar cambios
                        </span>

                        <span wire:loading wire:target="updatePrescription">
                            Guardando...
                        </span>
                    </button>
                </div>
            </div>
        </div>

    </form>
</div>