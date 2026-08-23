<?php

use App\Models\Patient;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
    #[Layout('layouts::app')]
    #[Title('Editar paciente | DocTotal')]
    class extends Component
    {
        public Patient $patient;

        public string $first_name = '';
        public string $last_name = '';
        public string $second_last_name = '';
        public string $birth_date = '';
        public string $sex = '';
        public string $email = '';
        public string $phone = '';
        public string $whatsapp = '';
        public string $blood_type = '';

        public function mount(string $uuid): void
        {
            $this->patient = Patient::query()
                ->where('uuid', $uuid)
                ->firstOrFail();

            $this->first_name = $this->patient->first_name;
            $this->last_name = $this->patient->last_name;
            $this->second_last_name = $this->patient->second_last_name ?? '';
            $this->birth_date = $this->patient->birth_date?->format('Y-m-d') ?? '';
            $this->sex = $this->patient->sex ?? '';
            $this->email = $this->patient->email ?? '';
            $this->phone = $this->patient->phone ?? '';
            $this->whatsapp = $this->patient->whatsapp ?? '';
            $this->blood_type = $this->patient->blood_type ?? '';
        }

        public function updatePatient(): void
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

            $this->patient->update([
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

            session()->flash(
                'success',
                'Paciente actualizado correctamente.'
            );

            $this->redirectRoute(
                'patients.show',
                ['uuid' => $this->patient->uuid]
            );
        }
    };
?>

<div class="mx-auto max-w-4xl">

    <div class="mb-8">

        <a
            href="{{ route('patients.show', ['uuid' => $patient->uuid]) }}"
            class="text-sm font-medium text-slate-500 hover:text-slate-900">
            ← Volver al expediente
        </a>

        <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-900">
            Editar paciente
        </h1>

        <p class="mt-1 text-sm text-slate-500">
            Actualiza los datos generales del paciente.
        </p>

    </div>

    <form
        wire:submit="updatePatient"
        class="rounded-xl border border-slate-200 bg-white shadow-sm">

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

                @error('birth_date')
                <p class="mt-1 text-sm text-red-600">
                    {{ $message }}
                </p>
                @enderror
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

        <div
            class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
            <a
                href="{{ route('patients.show', ['uuid' => $patient->uuid]) }}"
                class="rounded-lg border border-slate-300 px-4 py-2
                       text-sm font-medium text-slate-700">
                Cancelar
            </a>

            <button
                type="submit"
                wire:loading.attr="disabled"
                class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold
                       text-white disabled:opacity-50">
                <span
                    wire:loading.remove
                    wire:target="updatePatient">
                    Guardar cambios
                </span>

                <span
                    wire:loading
                    wire:target="updatePatient">
                    Guardando...
                </span>
            </button>
        </div>

    </form>

</div>