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

    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                Pacientes
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Administra los pacientes de tu consultorio.
            </p>
        </div>

        <button
            type="button"
            wire:click="openCreateModal"
            class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold
                text-white hover:bg-slate-800">
            Nuevo paciente
        </button>

    </div>

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 p-4">

            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Buscar por nombre, teléfono o correo..."
                class="w-full max-w-md rounded-lg border border-slate-300 px-3 py-2 text-sm">

        </div>

        <div class="overflow-x-auto">

            <table class="min-w-full divide-y divide-slate-200">

                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Paciente
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Contacto
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Fecha de nacimiento
                        </th>

                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Acciones
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 bg-white">

                    @forelse ($this->patients as $patient)

                    <tr wire:key="patient-{{ $patient->id }}">

                        <td class="whitespace-nowrap px-6 py-4">

                            <div class="font-medium text-slate-900">
                                {{ $patient->first_name }}
                                {{ $patient->last_name }}
                                {{ $patient->second_last_name }}
                            </div>

                            @if ($patient->blood_type)
                            <div class="mt-1 text-xs text-slate-500">
                                Tipo de sangre:
                                {{ $patient->blood_type }}
                            </div>
                            @endif

                        </td>

                        <td class="px-6 py-4 text-sm text-slate-600">

                            @if ($patient->phone)
                            <div>{{ $patient->phone }}</div>
                            @endif

                            @if ($patient->email)
                            <div class="text-slate-400">
                                {{ $patient->email }}
                            </div>
                            @endif

                        </td>

                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                            {{ $patient->birth_date?->format('d/m/Y') ?? '—' }}
                        </td>

                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm">

                            <a
                                href="{{ route('patients.show', $patient) }}"
                                class="font-medium text-slate-700 hover:text-slate-900">
                                Ver
                            </a>

                        </td>

                    </tr>

                    @empty

                    <tr>
                        <td
                            colspan="4"
                            class="px-6 py-12 text-center">
                            <p class="font-medium text-slate-700">
                                No hay pacientes.
                            </p>

                            <p class="mt-1 text-sm text-slate-500">
                                Registra tu primer paciente para comenzar.
                            </p>
                        </td>
                    </tr>

                    @endforelse

                    @if ($this->patients->hasPages())
                    <div class="border-t border-slate-200 px-6 py-4">
                        {{ $this->patients->links() }}
                    </div>
                    @endif

                </tbody>

            </table>

        </div>

        @if ($this->patients()->hasPages())
        <div class="border-t border-slate-200 px-6 py-4">
            {{ $this->patients()->links() }}
        </div>
        @endif

        @if ($showCreateModal)

        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">

            <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-xl">

                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">

                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">
                            Nuevo paciente
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Captura los datos generales del paciente.
                        </p>
                    </div>

                    <button
                        type="button"
                        wire:click="closeCreateModal"
                        class="text-2xl leading-none text-slate-400 hover:text-slate-700">
                        ×
                    </button>

                </div>

                <form wire:submit="createPatient">

                    <div class="grid gap-5 p-6 sm:grid-cols-2">

                        <div>
                            <label class="mb-1 block text-sm font-medium">
                                Nombre *
                            </label>

                            <input
                                wire:model="first_name"
                                type="text"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2">

                            @error('first_name')
                            <p class="mt-1 text-sm text-red-600">
                                {{ $message }}
                            </p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">
                                Apellido paterno *
                            </label>

                            <input
                                wire:model="last_name"
                                type="text"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2">

                            @error('last_name')
                            <p class="mt-1 text-sm text-red-600">
                                {{ $message }}
                            </p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">
                                Apellido materno
                            </label>

                            <input
                                wire:model="second_last_name"
                                type="text"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">
                                Fecha de nacimiento
                            </label>

                            <input
                                wire:model="birth_date"
                                type="date"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">
                                Sexo
                            </label>

                            <select
                                wire:model="sex"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2">
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
                            <label class="mb-1 block text-sm font-medium">
                                Tipo de sangre
                            </label>

                            <select
                                wire:model="blood_type"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2">
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
                            <label class="mb-1 block text-sm font-medium">
                                Teléfono
                            </label>

                            <input
                                wire:model="phone"
                                type="text"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">
                                WhatsApp
                            </label>

                            <input
                                wire:model="whatsapp"
                                type="text"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-medium">
                                Correo electrónico
                            </label>

                            <input
                                wire:model="email"
                                type="email"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2">

                            @error('email')
                            <p class="mt-1 text-sm text-red-600">
                                {{ $message }}
                            </p>
                            @enderror
                        </div>

                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">

                        <button
                            type="button"
                            wire:click="closeCreateModal"
                            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium">
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold
                                    text-white disabled:opacity-50">
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

</div>