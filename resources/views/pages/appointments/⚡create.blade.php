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

    {{-- PAGE HEADER --}}
    <div class="mb-6">
        <a
            href="{{ route('appointments.index') }}"
            class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-blue-600">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" class="h-4 w-4">
                <path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            Volver a agenda
        </a>

        <div class="mt-4 flex items-start gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-violet-600 text-white shadow-sm shadow-blue-200">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5.5 w-5.5">
                    <rect x="3" y="5" width="18" height="16" rx="2" />
                    <path d="M8 3v4M16 3v4M3 10h18M12 14v4M10 16h4" stroke-linecap="round" />
                </svg>
            </div>

            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-950">Nueva cita</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Programa una cita utilizando únicamente los horarios disponibles.
                </p>
            </div>
        </div>
    </div>

    <form wire:submit="saveAppointment" class="space-y-6">

        {{-- PATIENT --}}
        <section class="overflow-visible rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-50 text-cyan-600">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4.5 w-4.5">
                        <circle cx="12" cy="8" r="4" />
                        <path d="M4 21a8 8 0 0 1 16 0" stroke-linecap="round" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-slate-950">Paciente</h2>
                    <p class="text-xs text-slate-500">Busca un paciente existente o crea uno rápidamente.</p>
                </div>
            </div>

            <div class="p-5 sm:p-6">
                @if ($this->selectedPatient)
                <div class="flex flex-col gap-4 rounded-2xl border border-cyan-100 bg-gradient-to-r from-cyan-50/80 to-blue-50/70 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-500 to-blue-600 text-sm font-bold text-white shadow-sm">
                            {{ strtoupper(substr($this->selectedPatient->first_name, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-slate-950">
                                {{ $this->selectedPatient->first_name }}
                                {{ $this->selectedPatient->last_name }}
                                {{ $this->selectedPatient->second_last_name }}
                            </p>
                            @if ($this->selectedPatient->email)
                            <p class="mt-0.5 truncate text-sm text-slate-500">
                                {{ $this->selectedPatient->email }}
                            </p>
                            @endif
                        </div>
                    </div>

                    <button
                        type="button"
                        wire:click="clearPatient"
                        class="inline-flex self-start rounded-xl border border-cyan-200 bg-white px-3 py-2 text-sm font-semibold text-cyan-700 transition hover:bg-cyan-50 sm:self-auto">
                        Cambiar
                    </button>
                </div>
                @else
                <div class="relative">
                    <label class="dt-label">Buscar paciente *</label>

                    <div class="relative">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                            class="pointer-events-none absolute left-3 top-1/2 h-4.5 w-4.5 -translate-y-1/2 text-slate-400">
                            <circle cx="11" cy="11" r="7" />
                            <path d="m20 20-3.5-3.5" stroke-linecap="round" />
                        </svg>
                        <input
                            wire:model.live.debounce.300ms="patientSearch"
                            type="search"
                            autocomplete="off"
                            placeholder="Nombre, apellido, correo o teléfono..."
                            class="dt-input pl-10">
                    </div>

                    @error('patientId')
                    <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                    @enderror

                    @if (mb_strlen(trim($patientSearch)) >= 2)
                    <div class="absolute z-30 mt-2 max-h-80 w-full overflow-y-auto rounded-2xl border border-slate-200 bg-white p-1.5 shadow-2xl shadow-slate-200/70">
                        @forelse ($this->patientResults as $patient)
                        <button
                            type="button"
                            wire:click="selectPatient({{ $patient->id }})"
                            class="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left transition hover:bg-blue-50">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-xs font-bold text-blue-600">
                                {{ strtoupper(substr($patient->first_name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">
                                    {{ $patient->first_name }}
                                    {{ $patient->last_name }}
                                    {{ $patient->second_last_name }}
                                </p>
                                @if ($patient->email || $patient->phone)
                                <p class="mt-0.5 truncate text-xs text-slate-500">
                                    @if ($patient->email){{ $patient->email }}@endif
                                    @if ($patient->email && $patient->phone) · @endif
                                    @if ($patient->phone){{ $patient->phone }}@endif
                                </p>
                                @endif
                            </div>
                        </button>
                        @empty
                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-slate-800">No encontramos pacientes.</p>
                            <p class="mt-1 text-xs text-slate-500">Puedes crear uno sin salir de esta cita.</p>
                            <button
                                type="button"
                                wire:click="openQuickPatientModal"
                                class="mt-3 inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700">
                                <span class="text-base leading-none">+</span>
                                Crear paciente
                            </button>
                        </div>
                        @endforelse

                        @if ($this->patientResults->isNotEmpty())
                        <div class="border-t border-slate-100 px-2 pt-2">
                            <button
                                type="button"
                                wire:click="openQuickPatientModal"
                                class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-xs font-semibold text-blue-600 transition hover:bg-blue-50">
                                <span class="text-base leading-none">+</span>
                                Crear paciente nuevo
                            </button>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </section>

        {{-- DATE / TIME --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4.5 w-4.5">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M12 7v5l3 2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-slate-950">Fecha y horario</h2>
                    <p class="text-xs text-slate-500">Solo se muestran horarios realmente disponibles.</p>
                </div>
            </div>

            <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                <div>
                    <label class="dt-label">Fecha *</label>
                    <input
                        wire:model.live="date"
                        type="date"
                        min="{{ now()->format('Y-m-d') }}"
                        class="dt-input">
                    @error('date')
                    <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="dt-label">Duración *</label>
                    <select wire:model.live="duration" class="dt-select">
                        <option value="15">15 minutos</option>
                        <option value="30">30 minutos</option>
                        <option value="45">45 minutos</option>
                        <option value="60">60 minutos</option>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <label class="text-sm font-semibold text-slate-700">Horario disponible *</label>
                        @if ($this->availableSlots->isNotEmpty())
                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-100">
                            {{ $this->availableSlots->count() }} horario(s)
                        </span>
                        @endif
                    </div>

                    @if ($this->availableSlots->isNotEmpty())
                    <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8">
                        @foreach ($this->availableSlots as $slot)
                        @php $slotValue = $slot->format('H:i'); @endphp
                        <button
                            type="button"
                            wire:click="$set('time', '{{ $slotValue }}')"
                            class="rounded-xl border px-3 py-2.5 text-sm font-bold tabular-nums transition
                                           {{ $time === $slotValue
                                                ? 'border-violet-500 bg-gradient-to-r from-blue-600 to-violet-600 text-white shadow-sm shadow-violet-200'
                                                : 'border-slate-200 bg-white text-slate-700 hover:border-violet-200 hover:bg-violet-50 hover:text-violet-700'
                                           }}">
                            {{ $slotValue }}
                        </button>
                        @endforeach
                    </div>
                    @else
                    <div class="rounded-2xl border border-dashed border-amber-200 bg-amber-50/50 px-4 py-7 text-center">
                        <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                                <circle cx="12" cy="12" r="9" />
                                <path d="M12 8v5M12 17h.01" stroke-linecap="round" />
                            </svg>
                        </div>
                        <p class="mt-3 text-sm font-semibold text-slate-800">No hay horarios disponibles.</p>
                        <p class="mt-1 text-xs text-slate-500">Prueba con otra fecha o duración.</p>
                    </div>
                    @endif

                    @error('time')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </section>

        {{-- APPOINTMENT INFO --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4.5 w-4.5">
                        <path d="M5 4h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" />
                        <path d="M7 9h10M7 13h7" stroke-linecap="round" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-slate-950">Información de la cita</h2>
                    <p class="text-xs text-slate-500">Agrega el motivo y cualquier nota interna necesaria.</p>
                </div>
            </div>

            <div class="grid gap-5 p-5 sm:p-6">
                <div>
                    <label class="dt-label">Motivo</label>
                    <input
                        wire:model="reason"
                        type="text"
                        maxlength="500"
                        placeholder="Ej. Consulta general"
                        class="dt-input">
                </div>

                <div>
                    <label class="dt-label">Notas internas</label>
                    <textarea
                        wire:model="notes"
                        rows="4"
                        placeholder="Información interna relacionada con la cita..."
                        class="dt-textarea"></textarea>
                </div>
            </div>
        </section>

        {{-- ACTIONS --}}
        <div class="sticky bottom-3 z-10 flex flex-col-reverse gap-2 rounded-2xl border border-slate-200/80 bg-white/95 p-3 shadow-lg shadow-slate-200/60 backdrop-blur sm:flex-row sm:justify-end">
            <a
                href="{{ route('appointments.index') }}"
                class="dt-btn dt-btn-secondary text-center">
                Cancelar
            </a>

            <button
                type="submit"
                wire:loading.attr="disabled"
                class="dt-btn dt-btn-primary inline-flex items-center justify-center gap-2 disabled:opacity-50">
                <svg wire:loading.remove wire:target="saveAppointment" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" class="h-4 w-4">
                    <path d="M12 5v14M5 12h14" stroke-linecap="round" />
                </svg>

                <span wire:loading.remove wire:target="saveAppointment">Programar cita</span>
                <span wire:loading wire:target="saveAppointment">Guardando...</span>
            </button>
        </div>
    </form>

    {{-- QUICK PATIENT MODAL --}}
    @if ($showQuickPatientModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm">
        <div class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-2xl">
            <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-100 bg-white px-6 py-5">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-cyan-50 text-cyan-600">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                            <circle cx="10" cy="8" r="4" />
                            <path d="M3 21a7 7 0 0 1 14 0M19 8v6M16 11h6" stroke-linecap="round" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-950">Crear paciente</h2>
                        <p class="mt-1 text-sm text-slate-500">Captura los datos básicos para continuar con la cita.</p>
                    </div>
                </div>

                <button
                    type="button"
                    wire:click="closeQuickPatientModal"
                    aria-label="Cerrar"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" class="h-5 w-5">
                        <path d="m7 7 10 10M17 7 7 17" stroke-linecap="round" />
                    </svg>
                </button>
            </div>

            <form wire:submit="createQuickPatient">
                <div class="grid gap-5 p-6 sm:grid-cols-2">
                    <div>
                        <label class="dt-label">Nombre *</label>
                        <input wire:model="quick_first_name" type="text" autocomplete="given-name" class="dt-input">
                        @error('quick_first_name')
                        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="dt-label">Apellido paterno *</label>
                        <input wire:model="quick_last_name" type="text" autocomplete="family-name" class="dt-input">
                        @error('quick_last_name')
                        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="dt-label">Apellido materno</label>
                        <input wire:model="quick_second_last_name" type="text" class="dt-input">
                    </div>

                    <div>
                        <label class="dt-label">Fecha de nacimiento</label>
                        <input
                            wire:model="quick_birth_date"
                            type="date"
                            max="{{ now()->format('Y-m-d') }}"
                            class="dt-input">
                        @error('quick_birth_date')
                        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="dt-label">Teléfono</label>
                        <input
                            wire:model="quick_phone"
                            type="text"
                            autocomplete="tel"
                            placeholder="Ej. 9611234567"
                            class="dt-input">
                    </div>

                    <div>
                        <label class="dt-label">Correo</label>
                        <input
                            wire:model="quick_email"
                            type="email"
                            autocomplete="email"
                            placeholder="paciente@ejemplo.com"
                            class="dt-input">
                        @error('quick_email')
                        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="sticky bottom-0 flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50/95 px-6 py-4 backdrop-blur sm:flex-row sm:justify-end">
                    <button type="button" wire:click="closeQuickPatientModal" class="dt-btn dt-btn-secondary">
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="dt-btn dt-btn-primary disabled:opacity-50">
                        <span wire:loading.remove wire:target="createQuickPatient">Crear y seleccionar</span>
                        <span wire:loading wire:target="createQuickPatient">Guardando...</span>
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