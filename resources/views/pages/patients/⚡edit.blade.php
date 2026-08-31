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

<div class="dt-page">

    {{-- Header --}}
    <div class="dt-page-header">

        <div>

            <a
                href="{{ route('patients.show', ['uuid' => $patient->uuid]) }}"
                class="inline-flex items-center gap-2 text-sm font-medium
                       text-slate-500 transition hover:text-slate-900">

                <svg
                    class="h-4 w-4"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8">

                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M15 18l-6-6 6-6" />

                </svg>

                Volver al expediente

            </a>

            <div class="mt-4">

                <div class="flex flex-wrap items-center gap-3">

                    <h1 class="dt-page-title">
                        Editar paciente
                    </h1>

                    <span
                        class="inline-flex items-center rounded-full
                               border border-blue-100 bg-blue-50
                               px-2.5 py-1 text-xs font-semibold
                               text-blue-700">

                        Datos generales

                    </span>

                </div>

                <p class="dt-page-subtitle">
                    Actualiza la información general y de contacto del paciente.
                </p>

            </div>

        </div>

    </div>

    {{-- Patient summary --}}
    <div
        class="mb-6 rounded-2xl border border-slate-200
               bg-gradient-to-r from-white to-slate-50
               p-5 shadow-sm">

        <div class="flex items-center gap-4">

            <div
                class="flex h-12 w-12 shrink-0 items-center
                       justify-center rounded-2xl
                       bg-gradient-to-br from-blue-600 to-violet-600
                       text-lg font-bold text-white
                       shadow-lg shadow-blue-600/20">

                {{ strtoupper(substr($patient->first_name, 0, 1)) }}

            </div>

            <div class="min-w-0">

                <p class="truncate text-base font-semibold text-slate-950">
                    {{ $patient->first_name }}
                    {{ $patient->last_name }}
                    {{ $patient->second_last_name }}
                </p>

                <p class="mt-0.5 text-sm text-slate-500">
                    Modifica únicamente los datos que necesites actualizar.
                </p>

            </div>

        </div>

    </div>

    <form
        wire:submit="updatePatient"
        class="space-y-6">

        {{-- Personal information --}}
        <section class="dt-card">

            <div class="dt-card-header">

                <div class="flex items-center gap-3">

                    <div
                        class="flex h-10 w-10 items-center justify-center
                               rounded-xl bg-blue-50 text-blue-600">

                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8">

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M9.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM17 8h4M19 6v4" />

                        </svg>

                    </div>

                    <div>

                        <h2 class="text-base font-semibold text-slate-950">
                            Información personal
                        </h2>

                        <p class="mt-0.5 text-sm text-slate-500">
                            Nombre, fecha de nacimiento y datos clínicos básicos.
                        </p>

                    </div>

                </div>

            </div>

            <div class="dt-card-body">

                <div class="grid gap-5 sm:grid-cols-2">

                    {{-- First name --}}
                    <div>

                        <label
                            for="first_name"
                            class="dt-label">

                            Nombre
                            <span class="text-rose-500">*</span>

                        </label>

                        <input
                            id="first_name"
                            wire:model="first_name"
                            type="text"
                            autocomplete="given-name"
                            class="dt-input
                                   @error('first_name')
                                       border-rose-300 focus:border-rose-500 focus:ring-rose-100
                                   @enderror">

                        @error('first_name')

                        <p class="mt-1.5 text-xs text-rose-600">
                            {{ $message }}
                        </p>

                        @enderror

                    </div>

                    {{-- Last name --}}
                    <div>

                        <label
                            for="last_name"
                            class="dt-label">

                            Apellido paterno
                            <span class="text-rose-500">*</span>

                        </label>

                        <input
                            id="last_name"
                            wire:model="last_name"
                            type="text"
                            autocomplete="family-name"
                            class="dt-input
                                   @error('last_name')
                                       border-rose-300 focus:border-rose-500 focus:ring-rose-100
                                   @enderror">

                        @error('last_name')

                        <p class="mt-1.5 text-xs text-rose-600">
                            {{ $message }}
                        </p>

                        @enderror

                    </div>

                    {{-- Second last name --}}
                    <div>

                        <label
                            for="second_last_name"
                            class="dt-label">

                            Apellido materno

                        </label>

                        <input
                            id="second_last_name"
                            wire:model="second_last_name"
                            type="text"
                            class="dt-input">

                    </div>

                    {{-- Birth date --}}
                    <div>

                        <label
                            for="birth_date"
                            class="dt-label">

                            Fecha de nacimiento

                        </label>

                        <div class="relative">

                            <span
                                class="pointer-events-none absolute
                                       left-3 top-1/2 -translate-y-1/2
                                       text-slate-400">

                                <svg
                                    class="h-4.5 w-4.5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8">

                                    <rect
                                        x="3"
                                        y="5"
                                        width="18"
                                        height="16"
                                        rx="2" />

                                    <path
                                        stroke-linecap="round"
                                        d="M16 3v4M8 3v4M3 10h18" />

                                </svg>

                            </span>

                            <input
                                id="birth_date"
                                wire:model="birth_date"
                                type="date"
                                class="dt-input pl-10
                                       @error('birth_date')
                                           border-rose-300 focus:border-rose-500 focus:ring-rose-100
                                       @enderror">

                        </div>

                        @error('birth_date')

                        <p class="mt-1.5 text-xs text-rose-600">
                            {{ $message }}
                        </p>

                        @enderror

                    </div>

                    {{-- Sex --}}
                    <div>

                        <label
                            for="sex"
                            class="dt-label">

                            Sexo

                        </label>

                        <select
                            id="sex"
                            wire:model="sex"
                            class="dt-select">

                            <option value="">
                                Selecciona
                            </option>

                            <option value="female">
                                Femenino
                            </option>

                            <option value="male">
                                Masculino
                            </option>

                            <option value="other">
                                Otro
                            </option>

                            <option value="unspecified">
                                Prefiere no especificar
                            </option>

                        </select>

                    </div>

                    {{-- Blood type --}}
                    <div>

                        <label
                            for="blood_type"
                            class="dt-label">

                            Tipo de sangre

                        </label>

                        <select
                            id="blood_type"
                            wire:model="blood_type"
                            class="dt-select">

                            <option value="">
                                Desconocido
                            </option>

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

                </div>

            </div>

        </section>

        {{-- Contact information --}}
        <section class="dt-card">

            <div class="dt-card-header">

                <div class="flex items-center gap-3">

                    <div
                        class="flex h-10 w-10 items-center justify-center
                               rounded-xl bg-violet-50 text-violet-600">

                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8">

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M4 4h4l2 5-3 2a15 15 0 0 0 6 6l2-3 5 2v4a2 2 0 0 1-2 2C9.7 22 2 14.3 2 6a2 2 0 0 1 2-2Z" />

                        </svg>

                    </div>

                    <div>

                        <h2 class="text-base font-semibold text-slate-950">
                            Información de contacto
                        </h2>

                        <p class="mt-0.5 text-sm text-slate-500">
                            Datos para comunicación con el paciente.
                        </p>

                    </div>

                </div>

            </div>

            <div class="dt-card-body">

                <div class="grid gap-5 sm:grid-cols-2">

                    {{-- Phone --}}
                    <div>

                        <label
                            for="phone"
                            class="dt-label">

                            Teléfono

                        </label>

                        <div class="relative">

                            <span
                                class="pointer-events-none absolute
                                       left-3 top-1/2 -translate-y-1/2
                                       text-slate-400">

                                <svg
                                    class="h-4.5 w-4.5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8">

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M4 4h4l2 5-3 2a15 15 0 0 0 6 6l2-3 5 2v4a2 2 0 0 1-2 2C9.7 22 2 14.3 2 6a2 2 0 0 1 2-2Z" />

                                </svg>

                            </span>

                            <input
                                id="phone"
                                wire:model="phone"
                                type="text"
                                autocomplete="tel"
                                class="dt-input pl-10">

                        </div>

                    </div>

                    {{-- WhatsApp --}}
                    <div>

                        <label
                            for="whatsapp"
                            class="dt-label">

                            WhatsApp

                        </label>

                        <div class="relative">

                            <span
                                class="pointer-events-none absolute
                                       left-3 top-1/2 -translate-y-1/2
                                       text-emerald-500">

                                <svg
                                    class="h-4.5 w-4.5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8">

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M21 11.5a8.4 8.4 0 0 1-9 8.4 8.7 8.7 0 0 1-3.8-.9L3 20.5l1.5-5A8.4 8.4 0 1 1 21 11.5Z" />

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M8.5 8.5c.5 3 2 4.5 5 5" />

                                </svg>

                            </span>

                            <input
                                id="whatsapp"
                                wire:model="whatsapp"
                                type="text"
                                autocomplete="tel"
                                class="dt-input pl-10">

                        </div>

                    </div>

                    {{-- Email --}}
                    <div class="sm:col-span-2">

                        <label
                            for="email"
                            class="dt-label">

                            Correo electrónico

                        </label>

                        <div class="relative">

                            <span
                                class="pointer-events-none absolute
                                       left-3 top-1/2 -translate-y-1/2
                                       text-slate-400">

                                <svg
                                    class="h-4.5 w-4.5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8">

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M4 6h16v12H4zM4 7l8 6 8-6" />

                                </svg>

                            </span>

                            <input
                                id="email"
                                wire:model="email"
                                type="email"
                                autocomplete="email"
                                class="dt-input pl-10
                                       @error('email')
                                           border-rose-300 focus:border-rose-500 focus:ring-rose-100
                                       @enderror">

                        </div>

                        @error('email')

                        <p class="mt-1.5 text-xs text-rose-600">
                            {{ $message }}
                        </p>

                        @enderror

                    </div>

                </div>

            </div>

        </section>

        {{-- Actions --}}
        <div
            class="flex flex-col-reverse gap-3
                   rounded-2xl border border-slate-200
                   bg-white p-4 shadow-sm
                   sm:flex-row sm:items-center sm:justify-between">

            <p class="text-xs text-slate-500">
                Los cambios se reflejarán inmediatamente en el expediente.
            </p>

            <div class="flex flex-col gap-3 sm:flex-row">

                <a
                    href="{{ route('patients.show', ['uuid' => $patient->uuid]) }}"
                    class="dt-btn dt-btn-secondary">

                    Cancelar

                </a>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="updatePatient"
                    class="dt-btn dt-btn-primary min-w-[150px]
                           disabled:cursor-not-allowed disabled:opacity-50">

                    <span
                        wire:loading.remove
                        wire:target="updatePatient"
                        class="inline-flex items-center gap-2">

                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8">

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M5 12l4 4L19 6" />

                        </svg>

                        Guardar cambios

                    </span>

                    <span
                        wire:loading
                        wire:target="updatePatient"
                        class="inline-flex items-center gap-2">

                        <svg
                            class="h-4 w-4 animate-spin"
                            viewBox="0 0 24 24"
                            fill="none">

                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="9"
                                stroke="currentColor"
                                stroke-width="3" />

                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M21 12a9 9 0 0 0-9-9v3a6 6 0 0 1 6 6h3Z" />

                        </svg>

                        Guardando...

                    </span>

                </button>

            </div>

        </div>

    </form>

</div>