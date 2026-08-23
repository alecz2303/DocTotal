<?php

use App\Models\Consultation;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
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

<div class="mx-auto max-w-7xl">

    <div class="mb-8">

        <h1 class="text-2xl font-bold tracking-tight text-slate-900">
            Consultas
        </h1>

        <p class="mt-1 text-sm text-slate-500">
            Historial general de consultas médicas.
        </p>

    </div>

    <div
        class="mb-6 rounded-xl border border-slate-200
               bg-white p-5 shadow-sm">

        <div class="grid gap-4 md:grid-cols-5">

            <div class="md:col-span-2">

                <label
                    for="consultation-search"
                    class="mb-1 block text-sm font-medium">
                    Buscar paciente
                </label>

                <input
                    id="consultation-search"
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    placeholder="Nombre o apellido..."
                    autocomplete="off"
                    class="w-full rounded-lg border
                           border-slate-300 px-3 py-2
                           text-sm text-slate-900
                           placeholder:text-slate-400
                           focus:border-slate-500
                           focus:outline-none focus:ring-1
                           focus:ring-slate-500">

            </div>

            <div>

                <label class="mb-1 block text-sm font-medium">
                    Desde
                </label>

                <input
                    wire:model.live="dateFrom"
                    type="date"
                    class="w-full rounded-lg border
                           border-slate-300 px-3 py-2
                           text-sm text-slate-900">

            </div>

            <div>

                <label class="mb-1 block text-sm font-medium">
                    Hasta
                </label>

                <input
                    wire:model.live="dateTo"
                    type="date"
                    class="w-full rounded-lg border
                           border-slate-300 px-3 py-2
                           text-sm text-slate-900">

            </div>

            <div>

                <label class="mb-1 block text-sm font-medium">
                    Estado
                </label>

                <select
                    wire:model.live="status"
                    class="w-full rounded-lg border
                           border-slate-300 px-3 py-2
                           text-sm text-slate-900">

                    <option value="">
                        Todos
                    </option>

                    <option value="completed">
                        Completada
                    </option>

                    <option value="cancelled">
                        Cancelada
                    </option>

                </select>

            </div>

        </div>

        @if (
        $search
        || $dateFrom
        || $dateTo
        || $status
        )

        <div class="mt-4 flex justify-end">

            <button
                type="button"
                wire:click="clearFilters"
                class="text-sm font-semibold
                           text-slate-600
                           hover:text-slate-900">
                Limpiar filtros
            </button>

        </div>

        @endif

    </div>

    <div
        class="overflow-hidden rounded-xl
               border border-slate-200
               bg-white shadow-sm">

        <div class="overflow-x-auto">

            <table class="min-w-full divide-y divide-slate-200">

                <thead class="bg-slate-50">

                    <tr>

                        <th
                            class="px-6 py-3 text-left
                                   text-xs font-semibold uppercase
                                   tracking-wide text-slate-500">
                            Fecha
                        </th>

                        <th
                            class="px-6 py-3 text-left
                                   text-xs font-semibold uppercase
                                   tracking-wide text-slate-500">
                            Paciente
                        </th>

                        <th
                            class="px-6 py-3 text-left
                                   text-xs font-semibold uppercase
                                   tracking-wide text-slate-500">
                            Médico
                        </th>

                        <th
                            class="px-6 py-3 text-left
                                   text-xs font-semibold uppercase
                                   tracking-wide text-slate-500">
                            Motivo
                        </th>

                        <th
                            class="px-6 py-3 text-left
                                   text-xs font-semibold uppercase
                                   tracking-wide text-slate-500">
                            Estado
                        </th>

                        <th
                            class="px-6 py-3 text-right
                                   text-xs font-semibold uppercase
                                   tracking-wide text-slate-500">
                            Acciones
                        </th>

                    </tr>

                </thead>

                <tbody
                    class="divide-y divide-slate-100 bg-white">

                    @forelse ($consultations as $consultation)

                    @php
                    $statusLabels = [
                    'completed' => 'Completada',
                    'cancelled' => 'Cancelada',
                    ];

                    $statusClasses = [
                    'completed' =>
                    'bg-green-50 text-green-700 ring-green-200',

                    'cancelled' =>
                    'bg-red-50 text-red-700 ring-red-200',
                    ];

                    $statusLabel =
                    $statusLabels[$consultation->status]
                    ?? ucfirst(
                    (string) $consultation->status
                    );

                    $statusClass =
                    $statusClasses[$consultation->status]
                    ?? 'bg-slate-50 text-slate-700 ring-slate-200';
                    @endphp

                    <tr
                        wire:key="consultation-{{ $consultation->uuid }}"
                        class="hover:bg-slate-50">

                        <td
                            class="whitespace-nowrap
                                       px-6 py-4">

                            <p
                                class="text-sm font-medium
                                           text-slate-900">
                                {{ $consultation->consultation_at->format('d/m/Y') }}
                            </p>

                            <p class="text-xs text-slate-500">
                                {{ $consultation->consultation_at->format('H:i') }}
                            </p>

                        </td>

                        <td class="px-6 py-4">

                            <p
                                class="text-sm font-semibold
                                           text-slate-900">
                                {{ $consultation->patient->first_name }}
                                {{ $consultation->patient->last_name }}
                                {{ $consultation->patient->second_last_name }}
                            </p>

                        </td>

                        <td class="px-6 py-4">

                            <p class="text-sm text-slate-700">
                                Dr.
                                {{ $consultation->doctorProfile->first_name }}
                                {{ $consultation->doctorProfile->last_name }}
                                {{ $consultation->doctorProfile->second_last_name }}
                            </p>

                            @if (
                            $consultation->doctorProfile->specialty
                            )

                            <p class="mt-1 text-xs text-slate-500">
                                {{ $consultation->doctorProfile->specialty->name }}
                            </p>

                            @endif

                        </td>

                        <td class="px-6 py-4">

                            <p
                                class="max-w-md truncate
                                           text-sm text-slate-600"
                                title="{{ $consultation->reason }}">
                                {{ $consultation->reason ?: '—' }}
                            </p>

                        </td>

                        <td class="px-6 py-4">

                            <span
                                class="inline-flex rounded-full
                                           px-2.5 py-1
                                           text-xs font-semibold
                                           ring-1 ring-inset
                                           {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>

                        </td>

                        <td
                            class="whitespace-nowrap
                                px-6 py-4 text-right">

                            <div
                                class="flex flex-wrap items-center
                                    justify-end gap-2">

                                <a
                                    href="{{ route(
                                        'consultations.show',
                                        [
                                            'uuid' =>
                                                $consultation->uuid
                                        ]
                                    ) }}"
                                    class="inline-flex items-center
                                        rounded-lg border border-slate-300
                                        px-3 py-1.5
                                        text-xs font-semibold text-slate-700
                                        hover:bg-slate-50">
                                    Ver
                                </a>

                                <a
                                    href="{{ route(
                                        'prescriptions.create',
                                        [
                                            'uuid' =>
                                                $consultation->uuid
                                        ]
                                    ) }}"
                                    class="inline-flex items-center
                                        rounded-lg bg-slate-900
                                        px-3 py-1.5
                                        text-xs font-semibold text-white
                                        hover:bg-slate-800">
                                    Receta
                                </a>

                                <a
                                    href="{{ route(
                                        'patients.show',
                                        [
                                            'uuid' =>
                                                $consultation->patient->uuid
                                        ]
                                    ) }}"
                                    class="inline-flex items-center
                                        px-2 py-1.5
                                        text-xs font-semibold text-slate-500
                                        hover:text-slate-900">
                                    Paciente
                                </a>

                            </div>

                        </td>

                    </tr>

                    @empty

                    <tr>

                        <td
                            colspan="6"
                            class="px-6 py-14 text-center">

                            <p
                                class="text-sm font-medium
                                           text-slate-700">
                                @if (
                                trim($search) !== ''
                                || $dateFrom
                                || $dateTo
                                || $status
                                )
                                No encontramos consultas.
                                @else
                                Todavía no hay consultas.
                                @endif
                            </p>

                            @if (
                            trim($search) !== ''
                            || $dateFrom
                            || $dateTo
                            || $status
                            )

                            <p
                                class="mt-1 text-sm
                                               text-slate-500">
                                Prueba modificando los filtros.
                            </p>

                            @endif

                        </td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        @if ($consultations->hasPages())

        <div
            class="border-t border-slate-200
                       px-6 py-4">
            {{ $consultations->links() }}
        </div>

        @endif

    </div>

</div>