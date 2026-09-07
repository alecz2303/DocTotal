<?php

use App\Models\ClinicalDocument;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new
    #[Layout('layouts::app')]
    #[Title('Archivos | DocTotal')]
    class extends Component
    {
        use WithPagination;

        public string $search = '';
        public string $category = '';
        public string $dateFrom = '';
        public string $dateTo = '';

        public function updatedSearch(): void
        {
            $this->resetPage();
        }

        public function updatedCategory(): void
        {
            $this->resetPage();
        }

        public function updatedDateFrom(): void
        {
            $this->resetPage();
        }

        public function updatedDateTo(): void
        {
            $this->resetPage();
        }

        public function clearFilters(): void
        {
            $this->reset(['search', 'category', 'dateFrom', 'dateTo']);
            $this->resetPage();
        }

        #[Computed]
        public function documents()
        {
            return ClinicalDocument::query()
                ->with([
                    'patient:id,uuid,first_name,last_name,second_last_name',
                    'consultation:id,uuid,consultation_at',
                    'laboratoryStudies:id,clinical_document_id,name,study_date',
                ])
                ->when($this->search !== '', function ($query): void {
                    $term = trim($this->search);

                    $query->where(function ($query) use ($term): void {
                        $query->where('title', 'like', "%{$term}%")
                            ->orWhere('original_name', 'like', "%{$term}%")
                            ->orWhereHas('patient', function ($query) use ($term): void {
                                $query->where('first_name', 'like', "%{$term}%")
                                    ->orWhere('last_name', 'like', "%{$term}%")
                                    ->orWhere('second_last_name', 'like', "%{$term}%");
                            });
                    });
                })
                ->when(
                    $this->category !== '',
                    fn ($query) => $query->where('category', $this->category)
                )
                ->when(
                    $this->dateFrom !== '',
                    fn ($query) => $query->whereDate('document_date', '>=', $this->dateFrom)
                )
                ->when(
                    $this->dateTo !== '',
                    fn ($query) => $query->whereDate('document_date', '<=', $this->dateTo)
                )
                ->orderByRaw('document_date IS NULL')
                ->orderByDesc('document_date')
                ->orderByDesc('created_at')
                ->paginate(20);
        }

        public function categoryLabel(string $category): string
        {
            return match ($category) {
                ClinicalDocument::CATEGORY_LABORATORY => 'Laboratorio',
                ClinicalDocument::CATEGORY_IMAGING => 'Imagen',
                ClinicalDocument::CATEGORY_OTHER => 'Otro',
                default => 'General',
            };
        }

        public function formattedSize(?int $bytes): string
        {
            if (! $bytes) {
                return '—';
            }

            if ($bytes < 1024) {
                return $bytes.' B';
            }

            if ($bytes < 1024 * 1024) {
                return number_format($bytes / 1024, 1).' KB';
            }

            return number_format($bytes / (1024 * 1024), 1).' MB';
        }
    };
?>

<div class="space-y-6">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-sm font-semibold text-blue-600">Expediente clínico</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-950">Archivos</h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-600">
                Consulta de forma transversal los documentos clínicos de tus pacientes sin salir del contexto seguro del consultorio.
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600 shadow-sm">
            <span class="font-semibold text-slate-950">{{ $this->documents->total() }}</span>
            {{ $this->documents->total() === 1 ? 'archivo' : 'archivos' }}
        </div>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <label class="xl:col-span-2">
                <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Buscar</span>
                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Archivo, paciente o nombre original"
                    class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </label>

            <label>
                <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Tipo</span>
                <select wire:model.live="category" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todos</option>
                    <option value="general">General</option>
                    <option value="laboratory">Laboratorio</option>
                    <option value="imaging">Imagen</option>
                    <option value="other">Otro</option>
                </select>
            </label>

            <label>
                <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Desde</span>
                <input type="date" wire:model.live="dateFrom" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </label>

            <label>
                <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Hasta</span>
                <input type="date" wire:model.live="dateTo" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </label>
        </div>

        @if ($search !== '' || $category !== '' || $dateFrom !== '' || $dateTo !== '')
            <div class="mt-3 flex justify-end">
                <button type="button" wire:click="clearFilters" class="text-sm font-medium text-blue-700 hover:underline">
                    Limpiar filtros
                </button>
            </div>
        @endif
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="px-5 py-3">Archivo</th>
                        <th class="px-5 py-3">Paciente</th>
                        <th class="px-5 py-3">Tipo</th>
                        <th class="px-5 py-3">Fecha</th>
                        <th class="px-5 py-3">Tamaño</th>
                        <th class="px-5 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->documents as $document)
                        @php
                            $patientName = collect([
                                $document->patient?->first_name,
                                $document->patient?->last_name,
                                $document->patient?->second_last_name,
                            ])->filter()->implode(' ');
                        @endphp
                        <tr class="align-top hover:bg-slate-50/70">
                            <td class="px-5 py-4">
                                <div class="font-medium text-slate-950">{{ $document->title }}</div>
                                <div class="mt-1 max-w-xs truncate text-xs text-slate-500">{{ $document->original_name }}</div>
                                @if ($document->laboratoryStudies->isNotEmpty())
                                    <div class="mt-2 inline-flex rounded-full bg-violet-50 px-2 py-1 text-[11px] font-semibold text-violet-700">
                                        Vinculado a laboratorio estructurado
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @if ($document->patient)
                                    <a href="{{ route('patients.show', $document->patient->uuid) }}" class="font-medium text-blue-700 hover:underline">
                                        {{ $patientName ?: 'Paciente' }}
                                    </a>
                                @else
                                    <span class="text-slate-400">Paciente no disponible</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                    {{ $this->categoryLabel($document->category) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">
                                {{ $document->document_date?->format('d/m/Y') ?? $document->created_at?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">
                                {{ $this->formattedSize($document->size_bytes) }}
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('clinical-documents.view', $document) }}" target="_blank" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                        Ver
                                    </a>
                                    <a href="{{ route('clinical-documents.download', $document) }}" class="rounded-lg bg-slate-950 px-3 py-2 text-xs font-medium text-white hover:bg-slate-800">
                                        Descargar
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <p class="font-medium text-slate-700">No encontramos archivos con esos criterios.</p>
                                <p class="mt-1 text-sm text-slate-500">Los documentos clínicos se cargan desde el expediente de cada paciente.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($this->documents->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $this->documents->links() }}
            </div>
        @endif
    </section>
</div>
