<?php

use App\Models\Patient;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;

new
    #[Layout('layouts::app')]
    #[Title('Pacientes | DocTotal')]
    class extends Component
    {
        use WithPagination;

        public string $search = '';

        public int $perPage = 15;

        public bool $showCreateModal = false;

        public string $first_name = '';
        public string $last_name = '';
        public string $second_last_name = '';
        public string $birth_date = '';
        public string $sex = '';
        public string $email = '';
        public string $phone = '';
        public string $whatsapp = '';
        public string $blood_type = '';

        public function updatedSearch(): void
        {
            $this->resetPage();
        }

        #[Computed]
        public function patients()
        {
            return Patient::query()
                ->when(
                    $this->search !== '',
                    function ($query) {
                        $search = '%' . $this->search . '%';

                        $query->where(function ($query) use ($search) {
                            $query
                                ->where('first_name', 'like', $search)
                                ->orWhere('last_name', 'like', $search)
                                ->orWhere('second_last_name', 'like', $search)
                                ->orWhere('phone', 'like', $search)
                                ->orWhere('email', 'like', $search);
                        });
                    }
                )
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->paginate($this->perPage);
        }

        public function openCreateModal(): void
        {
            $this->resetPatientForm();
            $this->resetValidation();

            $this->showCreateModal = true;
        }

        public function closeCreateModal(): void
        {
            $this->showCreateModal = false;

            $this->resetPatientForm();
            $this->resetValidation();
        }

        private function resetPatientForm(): void
        {
            $this->reset([
                'first_name',
                'last_name',
                'second_last_name',
                'birth_date',
                'sex',
                'email',
                'phone',
                'whatsapp',
                'blood_type',
            ]);
        }

        public function createPatient(): void
        {
            $validated = $this->validate([
                'first_name' => ['required', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                'second_last_name' => ['nullable', 'string', 'max:100'],
                'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
                'sex' => ['nullable', 'in:male,female,other,unspecified'],
                'email' => ['nullable', 'email', 'max:190'],
                'phone' => ['nullable', 'string', 'max:30'],
                'whatsapp' => ['nullable', 'string', 'max:30'],
                'blood_type' => [
                    'nullable',
                    'in:A+,A-,B+,B-,AB+,AB-,O+,O-',
                ],
            ]);

            Patient::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'second_last_name' => $validated['second_last_name'] ?: null,
                'birth_date' => $validated['birth_date'] ?: null,
                'sex' => $validated['sex'] ?: null,
                'email' => $validated['email'] ?: null,
                'phone' => $validated['phone'] ?: null,
                'whatsapp' => $validated['whatsapp'] ?: null,
                'blood_type' => $validated['blood_type'] ?: null,
            ]);

            unset($this->patients);

            $this->showCreateModal = false;

            $this->resetPatientForm();

            session()->flash(
                'success',
                'Paciente registrado correctamente.'
            );

            $this->redirectRoute('patients.index');
        }
    };
?>

<div class="mx-auto max-w-7xl">

    {{-- PAGE HEADER --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <div class="flex items-center gap-3">

                <div
                    class="flex h-11 w-11 items-center justify-center
                           rounded-2xl bg-gradient-to-br from-blue-600 to-violet-600
                           text-white shadow-[0_10px_24px_rgba(79,70,229,0.20)]">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        class="h-5 w-5">
                        <circle cx="9" cy="8" r="4" />
                        <path d="M2.5 21a6.5 6.5 0 0 1 13 0" />
                        <path d="M18 8v6M15 11h6" stroke-linecap="round" />
                    </svg>

                </div>

                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-950">
                        Pacientes
                    </h1>

                    <p class="mt-1 text-sm text-slate-500">
                        Administra los pacientes de tu consultorio.
                    </p>
                </div>

            </div>
        </div>

        <button
            type="button"
            wire:click="openCreateModal"
            class="inline-flex items-center justify-center gap-2
                   rounded-2xl bg-gradient-to-r from-blue-600 to-violet-600
                   px-5 py-3 text-sm font-bold text-white
                   shadow-[0_10px_24px_rgba(79,70,229,0.20)]
                   hover:-translate-y-0.5 hover:from-blue-700 hover:to-violet-700">

            <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                class="h-4.5 w-4.5">
                <path d="M12 5v14M5 12h14" stroke-linecap="round" />
            </svg>

            Nuevo paciente
        </button>

    </div>


    {{-- SEARCH + CONTENT --}}
    <section class="dt-card overflow-hidden shadow-doctotal-md">

        {{-- SEARCH BAR --}}
        <div
            class="flex flex-col gap-4 border-b border-slate-200/80
                   bg-gradient-to-r from-white via-slate-50/70 to-blue-50/30
                   p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">

            <div class="relative w-full sm:max-w-xl">

                <div
                    class="pointer-events-none absolute inset-y-0 left-0
                           flex items-center pl-3.5 text-slate-400">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        class="h-4.5 w-4.5">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m20 20-3.5-3.5" stroke-linecap="round" />
                    </svg>

                </div>

                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar por nombre, teléfono o correo..."
                    class="dt-input pl-10">

            </div>

            <div class="flex items-center gap-2 text-xs text-slate-500">

                <span class="dt-badge dt-badge-neutral">
                    {{ $this->patients->total() }}
                    {{ $this->patients->total() === 1 ? 'paciente' : 'pacientes' }}
                </span>

                <span wire:loading wire:target="search" class="font-medium text-blue-600">
                    Buscando...
                </span>

            </div>

        </div>


        {{-- DESKTOP TABLE --}}
        <div class="hidden md:block">

            <div class="overflow-x-auto">

                <table class="min-w-full">

                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/80">

                            <th class="px-5 py-3.5 text-left text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">
                                Paciente
                            </th>

                            <th class="px-5 py-3.5 text-left text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">
                                Contacto
                            </th>

                            <th class="px-5 py-3.5 text-left text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">
                                Nacimiento
                            </th>

                            <th class="px-5 py-3.5 text-left text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">
                                Sangre
                            </th>

                            <th class="px-5 py-3.5 text-right text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">
                                Acciones
                            </th>

                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">

                        @forelse ($this->patients as $patient)

                        <tr
                            wire:key="patient-{{ $patient->id }}"
                            class="group hover:bg-blue-50/35">

                            <td class="px-5 py-4">

                                <div class="flex items-center gap-3">

                                    <div
                                        class="flex h-11 w-11 shrink-0 items-center justify-center
                                                   rounded-2xl bg-gradient-to-br from-blue-600 to-violet-600
                                                   text-sm font-bold text-white shadow-sm">

                                        {{ strtoupper(substr($patient->first_name ?? 'P', 0, 1)) }}

                                    </div>

                                    <div class="min-w-0">

                                        <p class="truncate font-semibold text-slate-900">
                                            {{ $patient->first_name }}
                                            {{ $patient->last_name }}
                                            {{ $patient->second_last_name }}
                                        </p>

                                        <div class="mt-1 flex flex-wrap items-center gap-2">

                                            @if ($patient->sex)
                                            <span class="text-xs text-slate-500">
                                                {{
                                                            [
                                                                'male' => 'Masculino',
                                                                'female' => 'Femenino',
                                                                'other' => 'Otro',
                                                                'unspecified' => 'No especificado',
                                                            ][$patient->sex] ?? 'No registrado'
                                                        }}
                                            </span>
                                            @endif

                                            @if ($patient->birth_date)
                                            <span class="text-xs text-slate-400">
                                                {{ $patient->birth_date->age }} años
                                            </span>
                                            @endif

                                        </div>

                                    </div>

                                </div>

                            </td>

                            <td class="px-5 py-4">

                                <div class="space-y-1">

                                    @if ($patient->phone)
                                    <p class="text-sm font-medium text-slate-700">
                                        {{ $patient->phone }}
                                    </p>
                                    @endif

                                    @if ($patient->email)
                                    <p class="max-w-[260px] truncate text-xs text-slate-500" title="{{ $patient->email }}">
                                        {{ $patient->email }}
                                    </p>
                                    @endif

                                    @if (! $patient->phone && ! $patient->email)
                                    <span class="text-sm text-slate-400">
                                        Sin contacto
                                    </span>
                                    @endif

                                </div>

                            </td>

                            <td class="whitespace-nowrap px-5 py-4">
                                <p class="text-sm font-medium text-slate-700">
                                    {{ $patient->birth_date?->format('d/m/Y') ?? '—' }}
                                </p>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4">

                                @if ($patient->blood_type)
                                <span class="dt-badge bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/10">
                                    {{ $patient->blood_type }}
                                </span>
                                @else
                                <span class="dt-badge dt-badge-neutral">
                                    Desconocido
                                </span>
                                @endif

                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-right">

                                <a
                                    href="{{ route('patients.show', $patient) }}"
                                    class="inline-flex items-center gap-2
                                               rounded-xl border border-slate-200 bg-white
                                               px-3.5 py-2 text-sm font-semibold text-slate-700
                                               shadow-sm hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">

                                    Ver expediente

                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        class="h-4 w-4">
                                        <path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>

                                </a>

                            </td>

                        </tr>

                        @empty

                        <tr>
                            <td colspan="5" class="px-6 py-14">

                                <div class="dt-empty-state border-0 bg-transparent py-8">

                                    <div
                                        class="flex h-12 w-12 items-center justify-center
                                                   rounded-2xl bg-blue-50 text-blue-600">

                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                            class="h-6 w-6">
                                            <circle cx="9" cy="8" r="4" />
                                            <path d="M2.5 21a6.5 6.5 0 0 1 13 0" />
                                            <path d="M18 8v6M15 11h6" stroke-linecap="round" />
                                        </svg>

                                    </div>

                                    <p class="mt-3 font-semibold text-slate-800">
                                        No hay pacientes
                                    </p>

                                    <p class="mt-1 text-sm text-slate-500">
                                        Registra tu primer paciente para comenzar.
                                    </p>

                                </div>

                            </td>
                        </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>


        {{-- MOBILE CARDS --}}
        <div class="space-y-3 bg-slate-50/60 p-3 md:hidden">

            @forelse ($this->patients as $patient)

            <article
                wire:key="patient-mobile-{{ $patient->id }}"
                class="rounded-2xl border border-slate-200/90 bg-white
                           p-4 shadow-doctotal-sm">

                <div class="flex items-start gap-3">

                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center
                                   rounded-2xl bg-gradient-to-br from-blue-600 to-violet-600
                                   text-sm font-bold text-white shadow-sm">

                        {{ strtoupper(substr($patient->first_name ?? 'P', 0, 1)) }}

                    </div>

                    <div class="min-w-0 flex-1">

                        <div class="flex items-start justify-between gap-3">

                            <div class="min-w-0">

                                <p class="font-semibold leading-5 text-slate-900">
                                    {{ $patient->first_name }}
                                    {{ $patient->last_name }}
                                    {{ $patient->second_last_name }}
                                </p>

                                <div class="mt-2 flex flex-wrap gap-2">

                                    @if ($patient->blood_type)
                                    <span class="dt-badge bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/10">
                                        {{ $patient->blood_type }}
                                    </span>
                                    @endif

                                    @if ($patient->birth_date)
                                    <span class="dt-badge dt-badge-neutral">
                                        {{ $patient->birth_date->age }} años
                                    </span>
                                    @endif

                                </div>

                            </div>

                        </div>

                        <div class="mt-4 space-y-2">

                            @if ($patient->phone)
                            <div class="flex items-center gap-2 text-sm text-slate-600">

                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    class="h-4 w-4 shrink-0 text-blue-500">
                                    <path
                                        d="M22 16.92v3a2 2 0 0 1-2.18 2
                                                   19.79 19.79 0 0 1-8.63-3.07
                                                   19.5 19.5 0 0 1-6-6
                                                   19.79 19.79 0 0 1-3.07-8.67
                                                   A2 2 0 0 1 4.11 2h3" />
                                </svg>

                                <span>{{ $patient->phone }}</span>

                            </div>
                            @endif

                            @if ($patient->email)
                            <div class="flex min-w-0 items-center gap-2 text-sm text-slate-600">

                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    class="h-4 w-4 shrink-0 text-violet-500">
                                    <rect x="3" y="5" width="18" height="14" rx="2" />
                                    <path d="m3 7 9 6 9-6" />
                                </svg>

                                <span class="truncate">{{ $patient->email }}</span>

                            </div>
                            @endif

                            <div class="flex items-center gap-2 text-sm text-slate-500">

                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    class="h-4 w-4 shrink-0 text-cyan-500">
                                    <rect x="4" y="5" width="16" height="15" rx="2" />
                                    <path d="M8 3v4M16 3v4M4 10h16" />
                                </svg>

                                <span>
                                    {{ $patient->birth_date?->format('d/m/Y') ?? 'Fecha no registrada' }}
                                </span>

                            </div>

                        </div>

                        <a
                            href="{{ route('patients.show', $patient) }}"
                            class="mt-4 inline-flex w-full items-center justify-center gap-2
                                       rounded-xl bg-blue-50 px-4 py-2.5
                                       text-sm font-semibold text-blue-700
                                       ring-1 ring-inset ring-blue-600/10
                                       hover:bg-blue-100">

                            Ver expediente

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                class="h-4 w-4">
                                <path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>

                        </a>

                    </div>

                </div>

            </article>

            @empty

            <div class="dt-empty-state bg-white">
                <p class="font-semibold text-slate-800">
                    No hay pacientes
                </p>

                <p class="mt-1 text-sm text-slate-500">
                    Registra tu primer paciente para comenzar.
                </p>
            </div>

            @endforelse

        </div>


        {{-- PAGINATION --}}
        @if ($this->patients->hasPages())
        <div class="border-t border-slate-200/80 bg-white px-4 py-4 sm:px-5">
            {{ $this->patients->links() }}
        </div>
        @endif

    </section>


    {{-- CREATE PATIENT MODAL --}}
    @if ($showCreateModal)

    <div
        class="fixed inset-0 z-50 flex items-center justify-center
                   bg-slate-950/55 p-3 backdrop-blur-[2px] sm:p-4">

        <div
            class="flex max-h-[94vh] w-full max-w-3xl flex-col
                       overflow-hidden rounded-[1.75rem]
                       border border-white/70 bg-white
                       shadow-[0_28px_80px_rgba(15,23,42,0.28)]">

            {{-- Header --}}
            <div
                class="flex shrink-0 items-start justify-between gap-4
                           border-b border-slate-200/80
                           bg-gradient-to-r from-blue-50/90 via-white to-violet-50/70
                           px-5 py-5 sm:px-6">

                <div class="flex items-start gap-4">

                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center
                                   rounded-2xl bg-blue-100 text-blue-600 shadow-sm">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            class="h-6 w-6">
                            <circle cx="9" cy="8" r="4" />
                            <path d="M2.5 21a6.5 6.5 0 0 1 13 0" />
                            <path d="M18 8v6M15 11h6" stroke-linecap="round" />
                        </svg>

                    </div>

                    <div>

                        <h2 class="text-xl font-bold tracking-tight text-slate-950">
                            Nuevo paciente
                        </h2>

                        <p class="mt-1 text-sm leading-6 text-slate-500">
                            Captura los datos generales y de contacto del paciente.
                        </p>

                    </div>

                </div>

                <button
                    type="button"
                    wire:click="closeCreateModal"
                    class="flex h-9 w-9 shrink-0 items-center justify-center
                               rounded-xl text-slate-400 hover:bg-white hover:text-slate-700">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        class="h-5 w-5">
                        <path d="M6 6l12 12M18 6 6 18" stroke-linecap="round" />
                    </svg>

                </button>

            </div>

            <form wire:submit="createPatient" class="flex min-h-0 flex-1 flex-col">

                <div class="min-h-0 flex-1 overflow-y-auto p-5 sm:p-6">

                    <div class="grid gap-5 sm:grid-cols-2">

                        <div>

                            <label class="dt-label">
                                Nombre *
                            </label>

                            <input
                                wire:model="first_name"
                                type="text"
                                placeholder="Nombre"
                                class="dt-input">

                            @error('first_name')
                            <p class="mt-1.5 text-sm font-medium text-red-600">
                                {{ $message }}
                            </p>
                            @enderror

                        </div>

                        <div>

                            <label class="dt-label">
                                Apellido paterno *
                            </label>

                            <input
                                wire:model="last_name"
                                type="text"
                                placeholder="Apellido paterno"
                                class="dt-input">

                            @error('last_name')
                            <p class="mt-1.5 text-sm font-medium text-red-600">
                                {{ $message }}
                            </p>
                            @enderror

                        </div>

                        <div>

                            <label class="dt-label">
                                Apellido materno
                            </label>

                            <input
                                wire:model="second_last_name"
                                type="text"
                                placeholder="Apellido materno"
                                class="dt-input">

                        </div>

                        <div>

                            <label class="dt-label">
                                Fecha de nacimiento
                            </label>

                            <input
                                wire:model="birth_date"
                                type="date"
                                class="dt-input">

                        </div>

                        <div>

                            <label class="dt-label">
                                Sexo
                            </label>

                            <select
                                wire:model="sex"
                                class="dt-select">

                                <option value="">Selecciona</option>
                                <option value="female">Femenino</option>
                                <option value="male">Masculino</option>
                                <option value="other">Otro</option>
                                <option value="unspecified">
                                    Prefiere no especificar
                                </option>

                            </select>

                        </div>

                        <div>

                            <label class="dt-label">
                                Tipo de sangre
                            </label>

                            <select
                                wire:model="blood_type"
                                class="dt-select">

                                <option value="">Desconocido</option>
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>

                            </select>

                        </div>

                        <div>

                            <label class="dt-label">
                                Teléfono
                            </label>

                            <input
                                wire:model="phone"
                                type="text"
                                placeholder="Número telefónico"
                                class="dt-input">

                        </div>

                        <div>

                            <label class="dt-label">
                                WhatsApp
                            </label>

                            <input
                                wire:model="whatsapp"
                                type="text"
                                placeholder="Número de WhatsApp"
                                class="dt-input">

                        </div>

                        <div class="sm:col-span-2">

                            <label class="dt-label">
                                Correo electrónico
                            </label>

                            <input
                                wire:model="email"
                                type="email"
                                placeholder="correo@ejemplo.com"
                                class="dt-input">

                            @error('email')
                            <p class="mt-1.5 text-sm font-medium text-red-600">
                                {{ $message }}
                            </p>
                            @enderror

                        </div>

                    </div>

                </div>

                <div
                    class="flex shrink-0 flex-col-reverse gap-3
                               border-t border-slate-200/80
                               bg-slate-50/70 px-5 py-4
                               sm:flex-row sm:justify-end sm:px-6">

                    <button
                        type="button"
                        wire:click="closeCreateModal"
                        class="dt-btn dt-btn-secondary">
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center justify-center gap-2
                                   rounded-xl bg-gradient-to-r from-blue-600 to-violet-600
                                   px-5 py-2.5 text-sm font-bold text-white
                                   shadow-[0_8px_20px_rgba(79,70,229,0.24)]
                                   hover:-translate-y-0.5 hover:from-blue-700 hover:to-violet-700
                                   disabled:pointer-events-none disabled:opacity-50">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            class="h-4.5 w-4.5">
                            <path d="m5 12 4 4L19 6" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>

                        <span wire:loading.remove wire:target="createPatient">
                            Guardar paciente
                        </span>

                        <span wire:loading wire:target="createPatient">
                            Guardando...
                        </span>

                    </button>

                </div>

            </form>

        </div>

    </div>

    @endif

</div>