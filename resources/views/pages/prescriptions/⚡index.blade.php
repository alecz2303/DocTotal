<?php

use App\Models\Prescription;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new
    #[Layout('layouts::app')]
    #[Title('Recetas | DocTotal')]
    class extends Component
    {
        use WithPagination;

        public string $search = '';

        public string $dateFrom = '';

        public string $dateTo = '';

        public function updatedSearch(): void
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
            $this->reset([
                'search',
                'dateFrom',
                'dateTo',
            ]);

            $this->resetPage();
        }

        #[Computed]
        public function prescriptions()
        {
            return Prescription::query()
                ->with([
                    'patient',
                    'doctorProfile',
                ])
                ->withCount('items')
                ->when(
                    trim($this->search) !== '',
                    function ($query) {
                        $search = trim($this->search);

                        $query->whereHas(
                            'patient',
                            function ($patientQuery) use ($search) {
                                $patientQuery->where(function ($q) use ($search) {
                                    $q->where(
                                        'first_name',
                                        'like',
                                        '%' . $search . '%'
                                    )
                                        ->orWhere(
                                            'last_name',
                                            'like',
                                            '%' . $search . '%'
                                        )
                                        ->orWhere(
                                            'second_last_name',
                                            'like',
                                            '%' . $search . '%'
                                        )
                                        ->orWhere(
                                            'email',
                                            'like',
                                            '%' . $search . '%'
                                        );
                                });
                            }
                        );
                    }
                )
                ->when(
                    $this->dateFrom !== '',
                    fn($query) => $query->whereDate(
                        'prescribed_at',
                        '>=',
                        $this->dateFrom
                    )
                )
                ->when(
                    $this->dateTo !== '',
                    fn($query) => $query->whereDate(
                        'prescribed_at',
                        '<=',
                        $this->dateTo
                    )
                )
                ->latest('prescribed_at')
                ->paginate(15);
        }
    };
?>

<div class="mx-auto max-w-7xl">

    {{-- HEADER --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-500 via-blue-500 to-violet-600 text-white shadow-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                    <path d="M7 3h10v18H7z" />
                    <path d="M10 8h4M10 12h4M10 16h3" stroke-linecap="round" />
                </svg>
            </div>

            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-950">Recetas</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Consulta las recetas emitidas en tu consultorio.
                </p>
            </div>
        </div>
    </div>

    {{-- FILTERS --}}
    <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
        <div class="grid gap-4 p-4 sm:p-5 md:grid-cols-4">
            <div class="md:col-span-2">
                <label class="dt-label">Buscar paciente</label>

                <div class="relative">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m20 20-3.5-3.5" />
                    </svg>

                    <input
                        wire:model.live.debounce.300ms="search"
                        type="search"
                        placeholder="Nombre, apellido o correo..."
                        class="dt-input pl-10">
                </div>
            </div>

            <div>
                <label class="dt-label">Desde</label>
                <input wire:model.live="dateFrom" type="date" class="dt-input">
            </div>

            <div>
                <label class="dt-label">Hasta</label>
                <input wire:model.live="dateTo" type="date" class="dt-input">
            </div>
        </div>

        @if ($search || $dateFrom || $dateTo)
        <div class="flex justify-end border-t border-slate-100 bg-slate-50/50 px-4 py-3 sm:px-5">
            <button type="button"
                wire:click="clearFilters"
                class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 transition hover:text-blue-600">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" class="h-3.5 w-3.5">
                    <path d="M6 6l12 12M18 6 6 18" stroke-linecap="round" />
                </svg>
                Limpiar filtros
            </button>
        </div>
        @endif
    </section>

    {{-- DESKTOP TABLE --}}
    <section class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm md:block">
        <div class="overflow-x-auto">
            <table class="dt-table min-w-full">
                <thead>
                    <tr>
                        <th>Paciente</th>
                        <th>Fecha</th>
                        <th>Médico</th>
                        <th class="text-center">Medicamentos</th>
                        <th>Estado</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($this->prescriptions as $prescription)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-blue-50 to-violet-50 text-xs font-black text-blue-700 ring-1 ring-inset ring-blue-100">
                                    {{ mb_strtoupper(mb_substr($prescription->patient->first_name, 0, 1)) }}
                                </div>

                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-900">
                                        {{ $prescription->patient->first_name }}
                                        {{ $prescription->patient->last_name }}
                                        {{ $prescription->patient->second_last_name }}
                                    </p>

                                    @if ($prescription->patient->email)
                                    <p class="mt-0.5 max-w-xs truncate text-xs text-slate-500">
                                        {{ $prescription->patient->email }}
                                    </p>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <td>
                            <p class="text-sm font-semibold text-slate-700">
                                {{ $prescription->prescribed_at->format('d/m/Y') }}
                            </p>
                            <p class="mt-0.5 text-xs text-slate-500">
                                {{ $prescription->prescribed_at->format('H:i') }}
                            </p>
                        </td>

                        <td>
                            <p class="text-sm font-medium text-slate-700">
                                {{ $prescription->doctorProfile->first_name }}
                                {{ $prescription->doctorProfile->last_name }}
                            </p>
                        </td>

                        <td class="text-center">
                            <span class="inline-flex min-w-8 items-center justify-center rounded-xl bg-violet-50 px-2.5 py-1.5 text-xs font-bold text-violet-700 ring-1 ring-inset ring-violet-100">
                                {{ $prescription->items_count }}
                            </span>
                        </td>

                        <td>
                            @if ($prescription->status === 'cancelled')
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700 ring-1 ring-inset ring-rose-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                Anulada
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                Activa
                            </span>
                            @endif
                        </td>

                        <td class="text-right">
                            <a href="{{ route('prescriptions.show', ['uuid' => $prescription->uuid]) }}"
                                class="dt-btn dt-btn-secondary whitespace-nowrap">
                                Ver receta
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6">
                            <div class="px-6 py-16 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-5 w-5">
                                        <path d="M7 3h10v18H7z" />
                                        <path d="M10 8h4M10 12h4" stroke-linecap="round" />
                                    </svg>
                                </div>
                                <p class="mt-4 font-semibold text-slate-700">No se encontraron recetas</p>
                                <p class="mt-1 text-sm text-slate-500">Las recetas emitidas aparecerán aquí.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($this->prescriptions->hasPages())
        <div class="border-t border-slate-100 px-5 py-4">
            {{ $this->prescriptions->links() }}
        </div>
        @endif
    </section>

    {{-- MOBILE CARDS --}}
    <div class="space-y-3 md:hidden">
        @forelse ($this->prescriptions as $prescription)
        <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="{{ $prescription->status === 'cancelled' ? 'bg-rose-500' : 'bg-gradient-to-r from-cyan-500 to-blue-500' }} h-1"></div>

            <div class="p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-xs font-black text-blue-700">
                            {{ mb_strtoupper(mb_substr($prescription->patient->first_name, 0, 1)) }}
                        </div>

                        <div class="min-w-0">
                            <p class="truncate font-bold text-slate-900">
                                {{ $prescription->patient->first_name }}
                                {{ $prescription->patient->last_name }}
                            </p>
                            <p class="mt-0.5 text-xs text-slate-500">
                                {{ $prescription->prescribed_at->format('d/m/Y') }}
                                · {{ $prescription->prescribed_at->format('H:i') }}
                            </p>
                        </div>
                    </div>

                    @if ($prescription->status === 'cancelled')
                    <span class="rounded-full bg-rose-50 px-2 py-1 text-[10px] font-bold text-rose-700">Anulada</span>
                    @else
                    <span class="rounded-full bg-emerald-50 px-2 py-1 text-[10px] font-bold text-emerald-700">Activa</span>
                    @endif
                </div>

                <div class="mt-4 grid grid-cols-2 gap-2">
                    <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Médico</p>
                        <p class="mt-1 truncate text-xs font-semibold text-slate-700">
                            {{ $prescription->doctorProfile->first_name }}
                            {{ $prescription->doctorProfile->last_name }}
                        </p>
                    </div>

                    <div class="rounded-xl bg-violet-50/70 px-3 py-2.5">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-violet-400">Medicamentos</p>
                        <p class="mt-1 text-xs font-bold text-violet-700">{{ $prescription->items_count }}</p>
                    </div>
                </div>

                <a href="{{ route('prescriptions.show', ['uuid' => $prescription->uuid]) }}"
                    class="dt-btn dt-btn-secondary mt-4 w-full justify-center">
                    Ver receta
                </a>
            </div>
        </article>
        @empty
        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-12 text-center shadow-sm">
            <p class="font-semibold text-slate-700">No se encontraron recetas</p>
            <p class="mt-1 text-sm text-slate-500">Las recetas emitidas aparecerán aquí.</p>
        </div>
        @endforelse

        @if ($this->prescriptions->hasPages())
        <div class="pt-3">
            {{ $this->prescriptions->links() }}
        </div>
        @endif
    </div>

</div>