<?php

use App\Models\Appointment;
use App\Services\AppointmentAvailabilityService;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
    #[Layout('layouts::app')]
    #[Title('Reprogramar cita | DocTotal')]
    class extends Component
    {
        public Appointment $appointment;

        public string $date = '';

        public string $time = '';

        public array $availableSlots = [];

        public function mount(string $uuid): void
        {
            $this->appointment = Appointment::query()
                ->with([
                    'patient',
                    'doctorProfile.specialty',
                ])
                ->where('uuid', $uuid)
                ->firstOrFail();

            abort_unless(
                $this->appointment->canReschedule(),
                404
            );

            $this->date = $this->appointment
                ->starts_at
                ->format('Y-m-d');

            $this->time = $this->appointment
                ->starts_at
                ->format('H:i');

            $this->refreshAvailableSlots();
        }

        public function updatedDate(): void
        {
            $this->time = '';

            $this->refreshAvailableSlots();
        }

        private function refreshAvailableSlots(): void
        {
            if (! $this->date) {
                $this->availableSlots = [];

                return;
            }

            $duration = (int) $this->appointment
                ->starts_at
                ->diffInMinutes(
                    $this->appointment->ends_at
                );

            $this->availableSlots = app(
                AppointmentAvailabilityService::class
            )
                ->slotsForDate(
                    $this->appointment->doctorProfile,
                    $this->date,
                    $duration,
                    $this->appointment
                )
                ->map(
                    fn($slot) => $slot->format('H:i')
                )
                ->values()
                ->all();
        }

        public function rescheduleAppointment(): void
        {
            $validated = $this->validate([
                'date' => [
                    'required',
                    'date',
                    'after_or_equal:today',
                ],

                'time' => [
                    'required',
                    'date_format:H:i',
                ],
            ]);

            $startsAt = Carbon::parse(
                $validated['date']
                    . ' '
                    . $validated['time']
            );

            /*
         * Incluso si el usuario dejó abierta esta pantalla
         * durante un rato, no permitimos reprogramar a una
         * hora que ya quedó en el pasado.
         */
            if (
                ! $startsAt->greaterThan(
                    now()->addMinutes(5)
                )
            ) {
                $this->addError(
                    'time',
                    'Selecciona un horario futuro disponible.'
                );

                $this->refreshAvailableSlots();

                return;
            }

            $duration = (int) $this->appointment
                ->starts_at
                ->diffInMinutes(
                    $this->appointment->ends_at
                );

            $availability = app(
                AppointmentAvailabilityService::class
            );

            /*
         * Revalidamos disponibilidad justo antes de guardar.
         * Así evitamos una doble reserva si otro usuario tomó
         * el horario mientras esta pantalla estaba abierta.
         */
            if (
                ! $availability->isAvailable(
                    $this->appointment->doctorProfile,
                    $startsAt,
                    $duration,
                    $this->appointment
                )
            ) {
                $this->addError(
                    'time',
                    'Este horario ya no está disponible.'
                );

                $this->refreshAvailableSlots();

                return;
            }

            $endsAt = $startsAt
                ->copy()
                ->addMinutes($duration);

            $previousStartsAt = $this->appointment
                ->starts_at
                ->copy();

            $previousEndsAt = $this->appointment
                ->ends_at
                ->copy();

            $this->appointment->reschedule(
                $startsAt,
                $endsAt
            );

            app(AuditLogger::class)->safeLog(
                action: 'appointment.rescheduled',
                auditable: $this->appointment,
                description: 'Cita reprogramada.',
                metadata: [
                    'previous_starts_at' =>
                    $previousStartsAt->toISOString(),

                    'previous_ends_at' =>
                    $previousEndsAt->toISOString(),

                    'new_starts_at' =>
                    $startsAt->toISOString(),

                    'new_ends_at' =>
                    $endsAt->toISOString(),
                ],
            );

            session()->flash(
                'success',
                'La cita fue reprogramada correctamente.'
            );

            $this->redirectRoute(
                'appointments.show',
                [
                    'uuid' => $this->appointment->uuid,
                ]
            );
        }
    };
?>

<div class="mx-auto max-w-5xl">

    {{-- BACK --}}
    <a
        href="{{ route('appointments.show', ['uuid' => $appointment->uuid]) }}"
        class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-blue-600">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" class="h-4 w-4">
            <path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        Volver a la cita
    </a>

    {{-- HEADER --}}
    <div class="mt-4 flex items-start gap-3">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-violet-600 text-white shadow-sm shadow-blue-200">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5.5 w-5.5">
                <rect x="3" y="5" width="18" height="16" rx="2" />
                <path d="M8 3v4M16 3v4M3 10h18" stroke-linecap="round" />
                <path d="m9 15 2 2 4-4" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </div>

        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-950">Reprogramar cita</h1>
            <p class="mt-1 text-sm text-slate-500">
                Selecciona una nueva fecha y horario para la cita.
            </p>
        </div>
    </div>

    {{-- CURRENT APPOINTMENT --}}
    <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
        <div class="h-1 bg-gradient-to-r from-cyan-500 via-blue-500 to-violet-500"></div>

        <div class="grid gap-4 p-5 sm:grid-cols-[1fr_auto] sm:items-center sm:p-6">
            <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-500 to-blue-600 text-sm font-bold text-white shadow-sm">
                    {{ strtoupper(substr($appointment->patient->first_name, 0, 1)) }}
                </div>

                <div class="min-w-0">
                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-400">Paciente</p>
                    <p class="mt-1 truncate text-lg font-semibold text-slate-950">
                        {{ $appointment->patient->first_name }}
                        {{ $appointment->patient->last_name }}
                        {{ $appointment->patient->second_last_name }}
                    </p>

                    @if ($appointment->reason)
                    <p class="mt-0.5 truncate text-sm text-slate-500">{{ $appointment->reason }}</p>
                    @endif
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 sm:min-w-56 sm:text-right">
                <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-400">Horario actual</p>
                <p class="mt-1 font-semibold text-slate-900">{{ $appointment->starts_at->format('d/m/Y') }}</p>
                <p class="text-sm font-semibold tabular-nums text-slate-600">
                    {{ $appointment->starts_at->format('H:i') }} – {{ $appointment->ends_at->format('H:i') }}
                </p>
            </div>
        </div>
    </section>

    <form wire:submit="rescheduleAppointment" class="mt-6 space-y-6">

        {{-- DATE --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <span class="text-sm font-bold">1</span>
                </div>

                <div>
                    <h2 class="font-semibold text-slate-950">Nueva fecha</h2>
                    <p class="text-xs text-slate-500">Elige el día al que deseas mover la cita.</p>
                </div>
            </div>

            <div class="p-5 sm:p-6">
                <div class="max-w-sm">
                    <label class="dt-label">Fecha</label>
                    <input
                        wire:model.live="date"
                        type="date"
                        min="{{ now()->format('Y-m-d') }}"
                        class="dt-input">

                    @error('date')
                    <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </section>

        {{-- SLOTS --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                    <span class="text-sm font-bold">2</span>
                </div>

                <div>
                    <h2 class="font-semibold text-slate-950">Nuevo horario</h2>
                    <p class="text-xs text-slate-500">Solo aparecen horarios realmente disponibles.</p>
                </div>
            </div>

            <div class="p-5 sm:p-6">
                <div wire:loading wire:target="date" class="py-10 text-center">
                    <div class="mx-auto h-7 w-7 animate-spin rounded-full border-2 border-slate-200 border-t-violet-600"></div>
                    <p class="mt-3 text-sm font-medium text-slate-500">Consultando disponibilidad...</p>
                </div>

                <div wire:loading.remove wire:target="date">
                    @if (! empty($availableSlots))
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-slate-700">Selecciona una hora</p>
                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-100">
                            {{ count($availableSlots) }} horario(s)
                        </span>
                    </div>

                    <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8">
                        @foreach ($availableSlots as $slotTime)
                        <button
                            type="button"
                            wire:key="reschedule-{{ $date }}-{{ $slotTime }}"
                            wire:click="$set('time', '{{ $slotTime }}')"
                            class="rounded-xl border px-3 py-2.5 text-sm font-bold tabular-nums transition
                                           {{ $time === $slotTime
                                                ? 'border-violet-500 bg-gradient-to-r from-blue-600 to-violet-600 text-white shadow-sm shadow-violet-200'
                                                : 'border-slate-200 bg-white text-slate-700 hover:border-violet-200 hover:bg-violet-50 hover:text-violet-700'
                                           }}">
                            {{ $slotTime }}
                        </button>
                        @endforeach
                    </div>
                    @else
                    <div class="rounded-2xl border border-dashed border-amber-200 bg-amber-50/50 px-5 py-8 text-center">
                        <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                                <circle cx="12" cy="12" r="9" />
                                <path d="M12 8v5M12 17h.01" stroke-linecap="round" />
                            </svg>
                        </div>
                        <p class="mt-3 font-semibold text-slate-800">No hay horarios disponibles</p>
                        <p class="mt-1 text-sm text-slate-500">
                            Selecciona otra fecha para consultar disponibilidad.
                        </p>
                    </div>
                    @endif
                </div>

                @error('time')
                <p class="mt-3 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </section>

        {{-- SUMMARY --}}
        @if ($date && $time)
        @php
        $isCurrentSchedule =
        $date === $appointment->starts_at->format('Y-m-d')
        && $time === $appointment->starts_at->format('H:i');
        @endphp

        <section class="overflow-hidden rounded-2xl border {{ $isCurrentSchedule ? 'border-amber-200 bg-amber-50/70' : 'border-emerald-200 bg-emerald-50/70' }}">
            <div class="flex items-center gap-4 px-5 py-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $isCurrentSchedule ? 'bg-amber-100 text-amber-600' : 'bg-emerald-100 text-emerald-600' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" class="h-5 w-5">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M12 7v5l3 2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>

                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] {{ $isCurrentSchedule ? 'text-amber-600' : 'text-emerald-600' }}">
                        {{ $isCurrentSchedule ? 'Horario actual' : 'Nuevo horario seleccionado' }}
                    </p>

                    <p class="mt-1 font-bold {{ $isCurrentSchedule ? 'text-amber-900' : 'text-emerald-900' }}">
                        {{ Carbon::parse($date)->locale('es')->translatedFormat('d \d\e F \d\e Y') }}
                        · {{ $time }}
                    </p>

                    @if ($isCurrentSchedule)
                    <p class="mt-1 text-sm text-amber-700">
                        Selecciona un horario diferente para reprogramar la cita.
                    </p>
                    @endif
                </div>
            </div>
        </section>
        @endif

        {{-- ACTIONS --}}
        <div class="sticky bottom-3 z-10 flex flex-col-reverse gap-2 rounded-2xl border border-slate-200/80 bg-white/95 p-3 shadow-lg shadow-slate-200/60 backdrop-blur sm:flex-row sm:justify-end">
            <a
                href="{{ route('appointments.show', ['uuid' => $appointment->uuid]) }}"
                class="dt-btn dt-btn-secondary text-center">
                Cancelar
            </a>

            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="rescheduleAppointment"
                @disabled(! $date || ! $time)
                class="dt-btn dt-btn-primary inline-flex items-center justify-center gap-2 disabled:cursor-not-allowed disabled:opacity-50">
                <svg
                    wire:loading.remove
                    wire:target="rescheduleAppointment"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.9"
                    class="h-4 w-4">
                    <path d="M5 12.5 9.2 17 19 7" stroke-linecap="round" stroke-linejoin="round" />
                </svg>

                <span wire:loading.remove wire:target="rescheduleAppointment">Guardar nuevo horario</span>
                <span wire:loading wire:target="rescheduleAppointment">Reprogramando...</span>
            </button>
        </div>

    </form>

</div>