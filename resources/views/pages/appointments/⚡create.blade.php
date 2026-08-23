<?php

use App\Models\Appointment;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Services\AppointmentAvailabilityService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
    #[Layout('layouts::app')]
    #[Title('Nueva cita | DocTotal')]
    class extends Component
    {
        public string $patientSearch = '';
        public ?int $patientId = null;

        public string $date = '';
        public string $time = '';
        public int $duration = 30;

        public string $reason = '';
        public string $notes = '';

        public bool $showQuickPatientModal = false;

        public string $quick_first_name = '';
        public string $quick_last_name = '';
        public string $quick_second_last_name = '';
        public string $quick_phone = '';
        public string $quick_email = '';
        public string $quick_birth_date = '';

        public function mount(): void
        {
            $this->date = now()
                ->addDay()
                ->format('Y-m-d');
        }

        public function updatedPatientSearch(): void
        {
            if (
                $this->patientId
                && trim($this->patientSearch) === ''
            ) {
                $this->patientId = null;
            }
        }

        public function updatedDate(): void
        {
            $this->time = '';
        }

        public function updatedDuration(): void
        {
            $this->time = '';
        }

        #[Computed]
        public function patientResults()
        {
            $search = trim($this->patientSearch);

            if (mb_strlen($search) < 2) {
                return collect();
            }

            return Patient::query()
                ->where(function ($query) use ($search) {
                    $query
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
                        )
                        ->orWhere(
                            'email',
                            'like',
                            '%' . $search . '%'
                        )
                        ->orWhere(
                            'phone',
                            'like',
                            '%' . $search . '%'
                        );
                })
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->limit(10)
                ->get();
        }

        #[Computed]
        public function selectedPatient(): ?Patient
        {
            if (! $this->patientId) {
                return null;
            }

            return Patient::query()
                ->find($this->patientId);
        }

        #[Computed]
        public function availableSlots()
        {
            if ($this->date === '') {
                return collect();
            }

            $doctor = DoctorProfile::query()
                ->where('user_id', auth()->id())
                ->first();

            if (! $doctor) {
                return collect();
            }

            return app(
                AppointmentAvailabilityService::class
            )->slotsForDate(
                $doctor,
                $this->date,
                $this->duration
            );
        }

        public function selectPatient(int $patientId): void
        {
            $patient = Patient::query()
                ->findOrFail($patientId);

            $this->patientId = $patient->id;

            $this->patientSearch = collect([
                $patient->first_name,
                $patient->last_name,
                $patient->second_last_name,
            ])
                ->filter()
                ->implode(' ');
        }

        public function clearPatient(): void
        {
            $this->patientId = null;
            $this->patientSearch = '';
        }

        public function saveAppointment(): void
        {
            $validated = $this->validate([
                'patientId' => [
                    'required',
                    'integer',
                ],

                'date' => [
                    'required',
                    'date',
                    'after_or_equal:today',
                ],

                'time' => [
                    'required',
                    'date_format:H:i',
                ],

                'duration' => [
                    'required',
                    'integer',
                    'min:10',
                    'max:240',
                ],

                'reason' => [
                    'nullable',
                    'string',
                    'max:500',
                ],

                'notes' => [
                    'nullable',
                    'string',
                    'max:5000',
                ],
            ]);

            $patient = Patient::query()
                ->findOrFail($validated['patientId']);

            $doctor = DoctorProfile::query()
                ->where('user_id', auth()->id())
                ->firstOrFail();

            $startsAt = Carbon::parse(
                $validated['date']
                    . ' '
                    . $validated['time']
            );

            $endsAt = $startsAt
                ->copy()
                ->addMinutes(
                    $validated['duration']
                );

            $available = app(
                AppointmentAvailabilityService::class
            )->isAvailable(
                $doctor,
                $startsAt,
                $validated['duration']
            );

            if (! $available) {
                $this->addError(
                    'time',
                    'El horario seleccionado ya no está disponible.'
                );

                return;
            }

            $appointment = Appointment::create([
                'patient_id' => $patient->id,
                'doctor_profile_id' => $doctor->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => 'scheduled',
                'reason' =>
                $validated['reason'] ?: null,
                'notes' =>
                $validated['notes'] ?: null,
            ]);

            session()->flash(
                'success',
                'Cita programada correctamente.'
            );

            $this->redirectRoute(
                'appointments.show',
                [
                    'uuid' => $appointment->uuid,
                ]
            );
        }

        public function openQuickPatientModal(): void
        {
            $this->resetValidation();

            $this->reset([
                'quick_first_name',
                'quick_last_name',
                'quick_second_last_name',
                'quick_phone',
                'quick_email',
                'quick_birth_date',
            ]);

            $this->showQuickPatientModal = true;
        }

        public function closeQuickPatientModal(): void
        {
            $this->showQuickPatientModal = false;

            $this->resetValidation();

            $this->reset([
                'quick_first_name',
                'quick_last_name',
                'quick_second_last_name',
                'quick_phone',
                'quick_email',
                'quick_birth_date',
            ]);
        }

        public function createQuickPatient(): void
        {
            $validated = $this->validate([
                'quick_first_name' => [
                    'required',
                    'string',
                    'max:100',
                ],

                'quick_last_name' => [
                    'required',
                    'string',
                    'max:100',
                ],

                'quick_second_last_name' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'quick_phone' => [
                    'nullable',
                    'string',
                    'max:30',
                ],

                'quick_email' => [
                    'nullable',
                    'email',
                    'max:190',
                ],

                'quick_birth_date' => [
                    'nullable',
                    'date',
                    'before_or_equal:today',
                ],
            ]);

            $patient = Patient::create([
                'first_name' => $validated['quick_first_name'],
                'last_name' => $validated['quick_last_name'],
                'second_last_name' =>
                $validated['quick_second_last_name'] ?: null,
                'phone' =>
                $validated['quick_phone'] ?: null,
                'email' =>
                $validated['quick_email'] ?: null,
                'birth_date' =>
                $validated['quick_birth_date'] ?: null,
            ]);

            $this->patientId = $patient->id;

            $this->patientSearch = collect([
                $patient->first_name,
                $patient->last_name,
                $patient->second_last_name,
            ])
                ->filter()
                ->implode(' ');

            $this->showQuickPatientModal = false;

            $this->reset([
                'quick_first_name',
                'quick_last_name',
                'quick_second_last_name',
                'quick_phone',
                'quick_email',
                'quick_birth_date',
            ]);

            $this->dispatch(
                'swal',
                title: 'Paciente creado',
                text: 'El paciente fue creado y seleccionado para la cita.',
                icon: 'success'
            );
        }
    };
?>

<div class="mx-auto max-w-5xl">

    <div class="mb-8">

        <a
            href="{{ route('dashboard') }}"
            class="text-sm font-medium
                   text-slate-500
                   hover:text-slate-900">
            ← Volver
        </a>

        <h1
            class="mt-3 text-2xl font-bold
                   tracking-tight text-slate-900">
            Nueva cita
        </h1>

        <p class="mt-1 text-sm text-slate-500">
            Programa una cita utilizando los horarios disponibles.
        </p>

    </div>

    <form
        wire:submit="saveAppointment"
        class="space-y-6">

        <section
            class="rounded-xl border
                   border-slate-200
                   bg-white shadow-sm">

            <div
                class="border-b
                       border-slate-200
                       px-6 py-4">
                <h2
                    class="font-semibold
                           text-slate-900">
                    Paciente
                </h2>
            </div>

            <div class="p-6">

                @if ($this->selectedPatient)

                <div
                    class="flex items-center
                               justify-between
                               rounded-lg
                               border border-slate-200
                               bg-slate-50
                               px-4 py-3">

                    <div>

                        <p
                            class="font-semibold
                                       text-slate-900">
                            {{ $this->selectedPatient->first_name }}
                            {{ $this->selectedPatient->last_name }}
                            {{ $this->selectedPatient->second_last_name }}
                        </p>

                        @if ($this->selectedPatient->email)

                        <p
                            class="mt-1
                                           text-sm
                                           text-slate-500">
                            {{ $this->selectedPatient->email }}
                        </p>

                        @endif

                    </div>

                    <button
                        type="button"
                        wire:click="clearPatient"
                        class="text-sm
                                   font-semibold
                                   text-slate-600
                                   hover:text-slate-900">
                        Cambiar
                    </button>

                </div>

                @else

                <div class="relative">

                    <label
                        class="mb-1
                                   block
                                   text-sm
                                   font-medium">
                        Buscar paciente *
                    </label>

                    <input
                        wire:model.live.debounce.300ms="patientSearch"
                        type="search"
                        autocomplete="off"
                        placeholder="Nombre, apellido, correo o teléfono..."
                        class="w-full
                                   rounded-lg
                                   border
                                   border-slate-300
                                   px-3 py-2.5">

                    @error('patientId')

                    <p
                        class="mt-1
                                       text-sm
                                       text-red-600">
                        {{ $message }}
                    </p>

                    @enderror

                    @if (
                    mb_strlen(
                    trim($patientSearch)
                    ) >= 2
                    )

                    <div
                        class="absolute z-20
                                       mt-1
                                       max-h-80
                                       w-full
                                       overflow-y-auto
                                       rounded-lg
                                       border
                                       border-slate-200
                                       bg-white
                                       shadow-xl">

                        @forelse (
                        $this->patientResults
                        as $patient
                        )

                        <button
                            type="button"
                            wire:click="selectPatient(
                                            {{ $patient->id }}
                                        )"
                            class="block
                                               w-full
                                               border-b
                                               border-slate-100
                                               px-4 py-3
                                               text-left
                                               last:border-0
                                               hover:bg-slate-50">

                            <p
                                class="text-sm
                                                   font-semibold
                                                   text-slate-900">
                                {{ $patient->first_name }}
                                {{ $patient->last_name }}
                                {{ $patient->second_last_name }}
                            </p>

                            @if (
                            $patient->email
                            || $patient->phone
                            )

                            <p
                                class="mt-1
                                                       text-xs
                                                       text-slate-500">
                                @if ($patient->email)
                                {{ $patient->email }}
                                @endif

                                @if (
                                $patient->email
                                && $patient->phone
                                )
                                ·
                                @endif

                                @if ($patient->phone)
                                {{ $patient->phone }}
                                @endif
                            </p>

                            @endif

                        </button>

                        @empty

                        <div class="px-4 py-4">

                            <p
                                class="text-sm
                                        font-medium
                                        text-slate-700">
                                No encontramos pacientes.
                            </p>

                            <p
                                class="mt-1
                                        text-xs
                                        text-slate-500">
                                Puedes crear un paciente sin salir de la cita.
                            </p>

                            <button
                                type="button"
                                wire:click="openQuickPatientModal"
                                class="mt-3
                                        inline-flex items-center
                                        rounded-lg
                                        bg-slate-900
                                        px-3 py-2
                                        text-xs
                                        font-semibold
                                        text-white
                                        hover:bg-slate-800">
                                + Crear paciente
                            </button>

                        </div>

                        @endforelse

                        <div class="mt-3">

                            <button
                                type="button"
                                wire:click="openQuickPatientModal"
                                class="text-sm
                                    font-semibold
                                    text-slate-600
                                    hover:text-slate-900">
                                + Crear paciente nuevo
                            </button>

                        </div>

                    </div>

                    @endif

                </div>

                @endif

            </div>

        </section>

        <section
            class="rounded-xl
                   border border-slate-200
                   bg-white shadow-sm">

            <div
                class="border-b
                       border-slate-200
                       px-6 py-4">
                <h2
                    class="font-semibold
                           text-slate-900">
                    Fecha y horario
                </h2>

                <p
                    class="mt-1
                           text-sm
                           text-slate-500">
                    Solo se muestran horarios realmente disponibles.
                </p>
            </div>

            <div
                class="grid gap-5
                       p-6
                       sm:grid-cols-2">

                <div>

                    <label
                        class="mb-1
                               block
                               text-sm
                               font-medium">
                        Fecha *
                    </label>

                    <input
                        wire:model.live="date"
                        type="date"
                        min="{{ now()->format('Y-m-d') }}"
                        class="w-full
                               rounded-lg
                               border
                               border-slate-300
                               px-3 py-2">

                    @error('date')
                    <p
                        class="mt-1
                                   text-sm
                                   text-red-600">
                        {{ $message }}
                    </p>
                    @enderror

                </div>

                <div>

                    <label
                        class="mb-1
                               block
                               text-sm
                               font-medium">
                        Duración *
                    </label>

                    <select
                        wire:model.live="duration"
                        class="w-full
                               rounded-lg
                               border
                               border-slate-300
                               px-3 py-2">
                        <option value="15">
                            15 minutos
                        </option>

                        <option value="30">
                            30 minutos
                        </option>

                        <option value="45">
                            45 minutos
                        </option>

                        <option value="60">
                            60 minutos
                        </option>
                    </select>

                </div>

                <div class="sm:col-span-2">

                    <label
                        class="mb-2
                               block
                               text-sm
                               font-medium">
                        Horario disponible *
                    </label>

                    @if (
                    $this->availableSlots->isNotEmpty()
                    )

                    <div
                        class="grid gap-2
                                   sm:grid-cols-4
                                   lg:grid-cols-6">

                        @foreach (
                        $this->availableSlots
                        as $slot
                        )

                        @php
                        $slotValue =
                        $slot->format('H:i');
                        @endphp

                        <button
                            type="button"
                            wire:click="$set(
                                        'time',
                                        '{{ $slotValue }}'
                                    )"
                            class="rounded-lg
                                           border
                                           px-3 py-2
                                           text-sm
                                           font-semibold
                                           transition
                                           {{ $time === $slotValue
                                                ? 'border-slate-900 bg-slate-900 text-white'
                                                : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50'
                                           }}">
                            {{ $slotValue }}
                        </button>

                        @endforeach

                    </div>

                    @else

                    <div
                        class="rounded-lg
                                   border
                                   border-dashed
                                   border-slate-300
                                   px-4 py-6
                                   text-center">

                        <p
                            class="text-sm
                                       font-medium
                                       text-slate-700">
                            No hay horarios disponibles.
                        </p>

                        <p
                            class="mt-1
                                       text-xs
                                       text-slate-500">
                            Prueba con otra fecha o duración.
                        </p>

                    </div>

                    @endif

                    @error('time')
                    <p
                        class="mt-2
                                   text-sm
                                   text-red-600">
                        {{ $message }}
                    </p>
                    @enderror

                </div>

            </div>

        </section>

        <section
            class="rounded-xl
                   border border-slate-200
                   bg-white shadow-sm">

            <div
                class="border-b
                       border-slate-200
                       px-6 py-4">
                <h2
                    class="font-semibold
                           text-slate-900">
                    Información de la cita
                </h2>
            </div>

            <div
                class="grid gap-5
                       p-6">

                <div>

                    <label
                        class="mb-1
                               block
                               text-sm
                               font-medium">
                        Motivo
                    </label>

                    <input
                        wire:model="reason"
                        type="text"
                        maxlength="500"
                        placeholder="Ej. Consulta general"
                        class="w-full
                               rounded-lg
                               border
                               border-slate-300
                               px-3 py-2">

                </div>

                <div>

                    <label
                        class="mb-1
                               block
                               text-sm
                               font-medium">
                        Notas internas
                    </label>

                    <textarea
                        wire:model="notes"
                        rows="4"
                        placeholder="Información interna relacionada con la cita..."
                        class="w-full
                               rounded-lg
                               border
                               border-slate-300
                               px-3 py-2"></textarea>

                </div>

            </div>

        </section>

        <div
            class="flex
                   justify-end gap-3">

            <a
                href="{{ route('dashboard') }}"
                class="rounded-lg
                       border border-slate-300
                       px-4 py-2.5
                       text-sm
                       font-medium
                       text-slate-700">
                Cancelar
            </a>

            <button
                type="submit"
                wire:loading.attr="disabled"
                class="rounded-lg
                       bg-slate-900
                       px-5 py-2.5
                       text-sm
                       font-semibold
                       text-white
                       hover:bg-slate-800
                       disabled:opacity-50">

                <span
                    wire:loading.remove
                    wire:target="saveAppointment">
                    Programar cita
                </span>

                <span
                    wire:loading
                    wire:target="saveAppointment">
                    Guardando...
                </span>

            </button>

        </div>

    </form>

    @if ($showQuickPatientModal)

    <div
        class="fixed inset-0 z-50
                flex items-center
                justify-center
                bg-slate-950/50
                p-4">

        <div
            class="w-full
                    max-w-2xl
                    rounded-2xl
                    bg-white
                    shadow-2xl">

            <div
                class="flex items-center
                        justify-between
                        border-b
                        border-slate-200
                        px-6 py-4">

                <div>

                    <h2
                        class="text-lg
                                font-semibold
                                text-slate-900">
                        Crear paciente
                    </h2>

                    <p
                        class="mt-1
                                text-sm
                                text-slate-500">
                        Captura los datos básicos para continuar con la cita.
                    </p>

                </div>

                <button
                    type="button"
                    wire:click="closeQuickPatientModal"
                    class="text-slate-400
                            hover:text-slate-700">
                    ✕
                </button>

            </div>

            <form
                wire:submit="createQuickPatient">

                <div
                    class="grid gap-5
                            p-6
                            sm:grid-cols-2">

                    <div>

                        <label
                            class="mb-1
                                    block
                                    text-sm
                                    font-medium">
                            Nombre *
                        </label>

                        <input
                            wire:model="quick_first_name"
                            type="text"
                            autocomplete="given-name"
                            class="w-full
                                    rounded-lg
                                    border
                                    border-slate-300
                                    px-3 py-2">

                        @error('quick_first_name')
                        <p
                            class="mt-1
                                        text-sm
                                        text-red-600">
                            {{ $message }}
                        </p>
                        @enderror

                    </div>

                    <div>

                        <label
                            class="mb-1
                                    block
                                    text-sm
                                    font-medium">
                            Apellido paterno *
                        </label>

                        <input
                            wire:model="quick_last_name"
                            type="text"
                            autocomplete="family-name"
                            class="w-full
                                    rounded-lg
                                    border
                                    border-slate-300
                                    px-3 py-2">

                        @error('quick_last_name')
                        <p
                            class="mt-1
                                        text-sm
                                        text-red-600">
                            {{ $message }}
                        </p>
                        @enderror

                    </div>

                    <div>

                        <label
                            class="mb-1
                                    block
                                    text-sm
                                    font-medium">
                            Apellido materno
                        </label>

                        <input
                            wire:model="quick_second_last_name"
                            type="text"
                            class="w-full
                                    rounded-lg
                                    border
                                    border-slate-300
                                    px-3 py-2">

                    </div>

                    <div>

                        <label
                            class="mb-1
                                    block
                                    text-sm
                                    font-medium">
                            Fecha de nacimiento
                        </label>

                        <input
                            wire:model="quick_birth_date"
                            type="date"
                            max="{{ now()->format('Y-m-d') }}"
                            class="w-full
                                    rounded-lg
                                    border
                                    border-slate-300
                                    px-3 py-2">

                        @error('quick_birth_date')
                        <p
                            class="mt-1
                                        text-sm
                                        text-red-600">
                            {{ $message }}
                        </p>
                        @enderror

                    </div>

                    <div>

                        <label
                            class="mb-1
                                    block
                                    text-sm
                                    font-medium">
                            Teléfono
                        </label>

                        <input
                            wire:model="quick_phone"
                            type="text"
                            autocomplete="tel"
                            placeholder="Ej. 9611234567"
                            class="w-full
                                    rounded-lg
                                    border
                                    border-slate-300
                                    px-3 py-2">

                    </div>

                    <div>

                        <label
                            class="mb-1
                                    block
                                    text-sm
                                    font-medium">
                            Correo
                        </label>

                        <input
                            wire:model="quick_email"
                            type="email"
                            autocomplete="email"
                            placeholder="paciente@ejemplo.com"
                            class="w-full
                                    rounded-lg
                                    border
                                    border-slate-300
                                    px-3 py-2">

                        @error('quick_email')
                        <p
                            class="mt-1
                                        text-sm
                                        text-red-600">
                            {{ $message }}
                        </p>
                        @enderror

                    </div>

                </div>

                <div
                    class="flex
                            justify-end
                            gap-3
                            border-t
                            border-slate-200
                            px-6 py-4">

                    <button
                        type="button"
                        wire:click="closeQuickPatientModal"
                        class="rounded-lg
                                border
                                border-slate-300
                                px-4 py-2.5
                                text-sm
                                font-medium
                                text-slate-700
                                hover:bg-slate-50">
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="rounded-lg
                                bg-slate-900
                                px-4 py-2.5
                                text-sm
                                font-semibold
                                text-white
                                hover:bg-slate-800
                                disabled:opacity-50">
                        <span
                            wire:loading.remove
                            wire:target="createQuickPatient">
                            Crear y seleccionar
                        </span>

                        <span
                            wire:loading
                            wire:target="createQuickPatient">
                            Guardando...
                        </span>
                    </button>

                </div>

            </form>

        </div>

    </div>

    @endif

</div>

@script
<script>
    $wire.on('swal', (event) => {
        Swal.fire({
            title: event.title,
            text: event.text,
            icon: event.icon,
            confirmButtonText: 'Aceptar'
        });
    });
</script>
@endscript