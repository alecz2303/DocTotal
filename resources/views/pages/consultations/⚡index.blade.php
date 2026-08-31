<?php

use App\Models\Consultation;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new
    #[Layout('layouts::app')]
    #[Title('Consultas | DocTotal')]
    class extends Component
    {
        use WithPagination;

        public string $search = '';
        public string $dateFrom = '';
        public string $dateTo = '';

        #[Url]
        public string $status = '';

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

        public function updatedStatus(): void
        {
            $this->resetPage();
        }

        public function clearFilters(): void
        {
            $this->reset([
                'search',
                'dateFrom',
                'dateTo',
                'status',
            ]);

            $this->resetPage();
        }

        public function with(): array
        {
            $search = trim($this->search);

            $consultations = Consultation::query()
                ->with([
                    'patient',
                    'doctorProfile.specialty',
                    'appointment',
                ])
                ->when(
                    $search !== '',
                    function ($query) use ($search) {
                        $query->whereHas(
                            'patient',
                            function ($patientQuery) use ($search) {
                                $patientQuery->where(
                                    function ($nameQuery) use ($search) {
                                        $nameQuery
                                            ->where(
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
                                            );
                                    }
                                );
                            }
                        );
                    }
                )
                ->when(
                    $this->dateFrom !== '',
                    fn($query) => $query->whereDate(
                        'consultation_at',
                        '>=',
                        $this->dateFrom
                    )
                )
                ->when(
                    $this->dateTo !== '',
                    fn($query) => $query->whereDate(
                        'consultation_at',
                        '<=',
                        $this->dateTo
                    )
                )
                ->when(
                    $this->status !== '',
                    fn($query) => $query->where(
                        'status',
                        $this->status
                    )
                )
                ->latest('consultation_at')
                ->paginate(15);

            return [
                'consultations' => $consultations,
            ];
        }
    };
?>

<div class="dt-page mx-auto max-w-7xl">

    {{-- HEADER --}}
    <div class="dt-page-header mb-6">
        <div class="flex items-start gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-violet-600 text-white shadow-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5.5 w-5.5">
                    <path d="M6 3h9l3 3v15H6z" />
                    <path d="M14 3v4h4M9 11h6M9 15h6" stroke-linecap="round" />
                </svg>
            </div>
            <div>
                <h1 class="dt-page-title">Consultas</h1>
                <p class="dt-page-subtitle">Consulta y continúa la atención médica de tus pacientes.</p>
            </div>
        </div>
    </div>

    {{-- FILTERS --}}
    <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3.5">
            <div class="flex items-center gap-2">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4 text-blue-600">
                    <path d="M4 5h16M7 12h10M10 19h4" stroke-linecap="round" />
                </svg>
                <span class="text-sm font-semibold text-slate-800">Filtros</span>
            </div>

            @if ($search || $dateFrom || $dateTo || $status)
            <button type="button" wire:click="clearFilters"
                class="text-xs font-bold text-blue-600 transition hover:text-blue-700">
                Limpiar filtros
            </button>
            @endif
        </div>

        <div class="grid gap-4 p-5 md:grid-cols-5">
            <div class="md:col-span-2">
                <label for="consultation-search" class="dt-label">Buscar paciente</label>
                <div class="relative">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m20 20-3.5-3.5" />
                    </svg>
                    <input id="consultation-search"
                        wire:model.live.debounce.300ms="search"
                        type="search"
                        placeholder="Nombre o apellido..."
                        autocomplete="off"
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

            <div>
                <label class="dt-label">Estado</label>
                <select wire:model.live="status" class="dt-select">
                    <option value="">Todos</option>
                    <option value="{{ Consultation::STATUS_DRAFT }}">En progreso</option>
                    <option value="{{ Consultation::STATUS_COMPLETED }}">Completada</option>
                </select>
            </div>
        </div>
    </section>

    {{-- DESKTOP TABLE --}}
    <section class="hidden overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm md:block">
        <div class="overflow-x-auto">
            <table class="dt-table min-w-full">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Paciente</th>
                        <th>Médico</th>
                        <th>Motivo</th>
                        <th>Estado</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($consultations as $consultation)
                    @php
                    $statusLabels = [
                    Consultation::STATUS_DRAFT => 'En progreso',
                    Consultation::STATUS_COMPLETED => 'Completada',
                    ];

                    $statusLabel = $statusLabels[$consultation->status]
                    ?? ucfirst((string) $consultation->status);

                    $continueRouteParameters = [
                    'uuid' => $consultation->patient->uuid,
                    ];

                    if ($consultation->appointment) {
                    $continueRouteParameters['appointment'] = $consultation->appointment->uuid;
                    }
                    @endphp

                    <tr wire:key="consultation-desktop-{{ $consultation->uuid }}"
                        class="{{ $consultation->status === Consultation::STATUS_DRAFT ? 'bg-amber-50/25' : '' }}">

                        <td class="whitespace-nowrap">
                            <p class="text-sm font-bold tabular-nums text-slate-900">
                                {{ $consultation->consultation_at->format('d/m/Y') }}
                            </p>
                            <p class="mt-0.5 text-xs tabular-nums text-slate-500">
                                {{ $consultation->consultation_at->format('H:i') }}
                            </p>
                        </td>

                        <td>
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-blue-50 to-violet-50 text-xs font-black text-blue-700 ring-1 ring-inset ring-blue-100">
                                    {{ strtoupper(substr($consultation->patient->first_name, 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-900">
                                        {{ $consultation->patient->first_name }}
                                        {{ $consultation->patient->last_name }}
                                        {{ $consultation->patient->second_last_name }}
                                    </p>

                                    @if ($consultation->status === Consultation::STATUS_DRAFT)
                                    <p class="mt-0.5 text-xs font-semibold text-amber-600">Atención pendiente</p>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <td>
                            <p class="text-sm font-medium text-slate-700">
                                Dr. {{ $consultation->doctorProfile->first_name }}
                                {{ $consultation->doctorProfile->last_name }}
                                {{ $consultation->doctorProfile->second_last_name }}
                            </p>

                            @if ($consultation->doctorProfile->specialty)
                            <p class="mt-0.5 text-xs text-slate-500">
                                {{ $consultation->doctorProfile->specialty->name }}
                            </p>
                            @endif
                        </td>

                        <td>
                            <p class="max-w-xs truncate text-sm text-slate-600"
                                title="{{ $consultation->reason }}">
                                {{ $consultation->reason ?: '—' }}
                            </p>

                            @if ($consultation->status === Consultation::STATUS_DRAFT)
                            <p class="mt-1 text-xs text-slate-400">
                                {{ $consultation->appointment ? 'Desde cita' : 'Consulta directa' }}
                            </p>
                            @endif
                        </td>

                        <td>
                            @if ($consultation->status === Consultation::STATUS_DRAFT)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700 ring-1 ring-inset ring-amber-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                {{ $statusLabel }}
                            </span>
                            @elseif ($consultation->status === Consultation::STATUS_COMPLETED)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                {{ $statusLabel }}
                            </span>
                            @else
                            <span class="dt-badge">{{ $statusLabel }}</span>
                            @endif
                        </td>

                        <td class="whitespace-nowrap text-right">
                            <div class="flex flex-wrap items-center justify-end gap-2">
                                @if ($consultation->status === Consultation::STATUS_DRAFT)
                                <a href="{{ route('consultations.create', $continueRouteParameters) }}"
                                    class="dt-btn dt-btn-primary !px-3 !py-1.5 !text-xs">
                                    Continuar consulta
                                </a>

                                @if ($consultation->appointment)
                                <a href="{{ route('appointments.show', ['uuid' => $consultation->appointment->uuid]) }}"
                                    class="dt-btn dt-btn-secondary !px-3 !py-1.5 !text-xs">
                                    Ver cita
                                </a>
                                @endif
                                @elseif ($consultation->status === Consultation::STATUS_COMPLETED)
                                <a href="{{ route('consultations.show', ['uuid' => $consultation->uuid]) }}"
                                    class="dt-btn dt-btn-secondary !px-3 !py-1.5 !text-xs">
                                    Ver
                                </a>

                                <a href="{{ route('prescriptions.create', ['uuid' => $consultation->uuid]) }}"
                                    class="dt-btn dt-btn-primary !px-3 !py-1.5 !text-xs">
                                    Receta
                                </a>
                                @endif

                                <a href="{{ route('patients.show', ['uuid' => $consultation->patient->uuid]) }}"
                                    class="rounded-lg px-2 py-1.5 text-xs font-bold text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">
                                    Paciente
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="!py-14 text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                                    <path d="M6 3h9l3 3v15H6z" />
                                    <path d="M9 11h6M9 15h4" stroke-linecap="round" />
                                </svg>
                            </div>
                            <p class="mt-3 text-sm font-semibold text-slate-700">
                                @if (trim($search) !== '' || $dateFrom || $dateTo || $status)
                                No encontramos consultas.
                                @else
                                Todavía no hay consultas.
                                @endif
                            </p>

                            @if (trim($search) !== '' || $dateFrom || $dateTo || $status)
                            <p class="mt-1 text-sm text-slate-500">Prueba modificando los filtros.</p>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($consultations->hasPages())
        <div class="border-t border-slate-100 px-6 py-4">
            {{ $consultations->links() }}
        </div>
        @endif
    </section>

    {{-- MOBILE CARDS --}}
    <div class="space-y-3 md:hidden">
        @forelse ($consultations as $consultation)
        @php
        $continueRouteParameters = ['uuid' => $consultation->patient->uuid];

        if ($consultation->appointment) {
        $continueRouteParameters['appointment'] = $consultation->appointment->uuid;
        }
        @endphp

        <article wire:key="consultation-mobile-{{ $consultation->uuid }}"
            class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="{{ $consultation->status === Consultation::STATUS_DRAFT ? 'bg-amber-400' : 'bg-emerald-500' }} h-1"></div>

            <div class="p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-blue-50 to-violet-50 text-sm font-black text-blue-700">
                            {{ strtoupper(substr($consultation->patient->first_name, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="truncate font-bold text-slate-900">
                                {{ $consultation->patient->first_name }}
                                {{ $consultation->patient->last_name }}
                            </p>
                            <p class="mt-0.5 text-xs text-slate-500">
                                {{ $consultation->consultation_at->format('d/m/Y · H:i') }}
                            </p>
                        </div>
                    </div>

                    @if ($consultation->status === Consultation::STATUS_DRAFT)
                    <span class="shrink-0 rounded-full bg-amber-50 px-2 py-1 text-[10px] font-bold text-amber-700">En progreso</span>
                    @else
                    <span class="shrink-0 rounded-full bg-emerald-50 px-2 py-1 text-[10px] font-bold text-emerald-700">Completada</span>
                    @endif
                </div>

                <div class="mt-4 grid gap-3 rounded-xl bg-slate-50 p-3">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Médico</p>
                        <p class="mt-0.5 text-sm text-slate-700">
                            Dr. {{ $consultation->doctorProfile->first_name }}
                            {{ $consultation->doctorProfile->last_name }}
                        </p>
                    </div>

                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Motivo</p>
                        <p class="mt-0.5 line-clamp-2 text-sm text-slate-700">{{ $consultation->reason ?: '—' }}</p>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    @if ($consultation->status === Consultation::STATUS_DRAFT)
                    <a href="{{ route('consultations.create', $continueRouteParameters) }}"
                        class="dt-btn dt-btn-primary flex-1 justify-center">
                        Continuar consulta
                    </a>

                    @if ($consultation->appointment)
                    <a href="{{ route('appointments.show', ['uuid' => $consultation->appointment->uuid]) }}"
                        class="dt-btn dt-btn-secondary">
                        Cita
                    </a>
                    @endif
                    @else
                    <a href="{{ route('consultations.show', ['uuid' => $consultation->uuid]) }}"
                        class="dt-btn dt-btn-secondary flex-1 justify-center">
                        Ver consulta
                    </a>
                    <a href="{{ route('prescriptions.create', ['uuid' => $consultation->uuid]) }}"
                        class="dt-btn dt-btn-primary">
                        Receta
                    </a>
                    @endif

                    <a href="{{ route('patients.show', ['uuid' => $consultation->patient->uuid]) }}"
                        class="dt-btn dt-btn-ghost">
                        Paciente
                    </a>
                </div>
            </div>
        </article>
        @empty
        <div class="rounded-2xl border border-slate-200 bg-white px-6 py-12 text-center shadow-sm">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                    <path d="M6 3h9l3 3v15H6z" />
                    <path d="M9 11h6M9 15h4" stroke-linecap="round" />
                </svg>
            </div>
            <p class="mt-3 font-semibold text-slate-700">
                @if (trim($search) !== '' || $dateFrom || $dateTo || $status)
                No encontramos consultas.
                @else
                Todavía no hay consultas.
                @endif
            </p>
            @if (trim($search) !== '' || $dateFrom || $dateTo || $status)
            <p class="mt-1 text-sm text-slate-500">Prueba modificando los filtros.</p>
            @endif
        </div>
        @endforelse

        @if ($consultations->hasPages())
        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
            {{ $consultations->links() }}
        </div>
        @endif
    </div>

</div>