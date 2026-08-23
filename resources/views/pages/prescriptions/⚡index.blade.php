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

<div class="mx-auto max-w-6xl">

    <div class="mb-8">

        <h1 class="text-2xl font-bold tracking-tight text-slate-900">
            Recetas
        </h1>

        <p class="mt-1 text-sm text-slate-500">
            Consulta las recetas emitidas en tu consultorio.
        </p>

    </div>

    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

        <div class="grid gap-4 md:grid-cols-4">

            <div class="md:col-span-2">

                <label class="mb-1 block text-sm font-medium">
                    Buscar paciente
                </label>

                <input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    placeholder="Nombre, apellido o correo..."
                    class="w-full rounded-lg border border-slate-300 px-3 py-2">

            </div>

            <div>

                <label class="mb-1 block text-sm font-medium">
                    Desde
                </label>

                <input
                    wire:model.live="dateFrom"
                    type="date"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2">

            </div>

            <div>

                <label class="mb-1 block text-sm font-medium">
                    Hasta
                </label>

                <input
                    wire:model.live="dateTo"
                    type="date"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2">

            </div>

        </div>

        @if ($search || $dateFrom || $dateTo)

        <div class="mt-4 flex justify-end">

            <button
                type="button"
                wire:click="clearFilters"
                class="text-sm font-semibold text-slate-600
                           hover:text-slate-900">
                Limpiar filtros
            </button>

        </div>

        @endif

    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

        <div class="hidden border-b border-slate-200 bg-slate-50 px-6 py-3 md:grid
                    md:grid-cols-[1.5fr_1fr_1fr_110px_110px_120px]
                    md:gap-4">

            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                Paciente
            </div>

            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                Fecha
            </div>

            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                Médico
            </div>

            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                Medicamentos
            </div>

            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                Estado
            </div>

            <div></div>

        </div>

        @forelse ($this->prescriptions as $prescription)

        <div
            class="border-b border-slate-100 px-6 py-5 last:border-0
                   md:grid
                   md:grid-cols-[1.5fr_1fr_1fr_110px_110px_120px]
                   md:items-center md:gap-4">

            <div>

                <p class="font-medium text-slate-900">
                    {{ $prescription->patient->first_name }}
                    {{ $prescription->patient->last_name }}
                    {{ $prescription->patient->second_last_name }}
                </p>

                @if ($prescription->patient->email)
                <p class="mt-1 text-sm text-slate-500">
                    {{ $prescription->patient->email }}
                </p>
                @endif

            </div>

            <div class="mt-3 md:mt-0">

                <p class="text-sm font-medium text-slate-700">
                    {{ $prescription->prescribed_at->format('d/m/Y') }}
                </p>

                <p class="text-xs text-slate-500">
                    {{ $prescription->prescribed_at->format('H:i') }}
                </p>

            </div>

            <div class="mt-3 md:mt-0">

                <p class="text-sm text-slate-700">
                    {{ $prescription->doctorProfile->first_name }}
                    {{ $prescription->doctorProfile->last_name }}
                </p>

            </div>

            <div class="mt-3 md:mt-0">

                <span
                    class="inline-flex rounded-full bg-slate-100
                               px-2.5 py-1 text-xs font-medium text-slate-600">
                    {{ $prescription->items_count }}
                </span>

            </div>

            <div class="mt-3 md:mt-0">

                @if ($prescription->status === 'cancelled')

                <span
                    class="inline-flex rounded-full
                            bg-red-50 px-2.5 py-1
                            text-xs font-semibold text-red-700
                            ring-1 ring-inset ring-red-200">
                    Anulada
                </span>

                @else

                <span
                    class="inline-flex rounded-full
                            bg-green-50 px-2.5 py-1
                            text-xs font-semibold text-green-700
                            ring-1 ring-inset ring-green-200">
                    Activa
                </span>

                @endif

            </div>

            <div class="mt-4 md:mt-0 md:text-right">

                <a
                    href="{{ route('prescriptions.show', [
                            'uuid' => $prescription->uuid
                        ]) }}"
                    class="text-sm font-semibold text-slate-700
                               hover:text-slate-900">
                    Ver receta
                </a>

            </div>

        </div>

        @empty

        <div class="px-6 py-16 text-center">

            <p class="font-medium text-slate-700">
                No se encontraron recetas
            </p>

            <p class="mt-1 text-sm text-slate-500">
                Las recetas emitidas aparecerán aquí.
            </p>

        </div>

        @endforelse

    </div>

    @if ($this->prescriptions->hasPages())

    <div class="mt-6">
        {{ $this->prescriptions->links() }}
    </div>

    @endif

</div>