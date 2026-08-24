<?php

use App\Models\Appointment;
use App\Services\AppointmentAvailabilityService;
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

            $this->appointment->reschedule(
                $startsAt,
                $endsAt
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

    {{-- VOLVER --}}
    <a
        href="{{ route(
            'appointments.show',
            [
                'uuid' => $appointment->uuid,
            ]
        ) }}"
        class="text-sm font-medium
               text-slate-500
               hover:text-slate-900">
        ← Volver a la cita
    </a>


    {{-- HEADER --}}
    <div class="mt-4">

        <h1
            class="text-2xl font-bold
                   tracking-tight
                   text-slate-900">
            Reprogramar cita
        </h1>

        <p
            class="mt-1 text-sm
                   text-slate-500">
            Selecciona una nueva fecha y horario para la cita.
        </p>

    </div>


    {{-- CITA ACTUAL --}}
    <section
        class="mt-6 rounded-xl
               border border-slate-200
               bg-white p-6
               shadow-sm">

        <div
            class="flex flex-col gap-5
                   sm:flex-row
                   sm:items-center
                   sm:justify-between">

            <div>

                <p
                    class="text-xs font-semibold
                           uppercase tracking-wide
                           text-slate-500">
                    Paciente
                </p>

                <p
                    class="mt-1 text-lg
                           font-semibold
                           text-slate-900">
                    {{ $appointment->patient->first_name }}
                    {{ $appointment->patient->last_name }}
                    {{ $appointment->patient->second_last_name }}
                </p>

                @if ($appointment->reason)

                <p
                    class="mt-1 text-sm
                               text-slate-500">
                    {{ $appointment->reason }}
                </p>

                @endif

            </div>


            <div
                class="rounded-xl
                       bg-slate-50
                       px-5 py-3
                       sm:text-right">

                <p
                    class="text-xs font-semibold
                           uppercase tracking-wide
                           text-slate-500">
                    Horario actual
                </p>

                <p
                    class="mt-1 font-semibold
                           text-slate-900">
                    {{ $appointment->starts_at->format('d/m/Y') }}
                </p>

                <p
                    class="text-sm
                           text-slate-600">
                    {{ $appointment->starts_at->format('H:i') }}
                    —
                    {{ $appointment->ends_at->format('H:i') }}
                </p>

            </div>

        </div>

    </section>


    <form
        wire:submit="rescheduleAppointment"
        class="mt-6 space-y-6">

        {{-- FECHA --}}
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
                    1. Nueva fecha
                </h2>

                <p
                    class="mt-1 text-sm
                           text-slate-500">
                    Elige el día al que deseas mover la cita.
                </p>

            </div>


            <div class="p-6">

                <div class="max-w-sm">

                    <label
                        class="mb-1 block
                               text-sm font-medium
                               text-slate-700">
                        Fecha
                    </label>

                    <input
                        wire:model.live="date"
                        type="date"
                        min="{{ now()->format('Y-m-d') }}"
                        class="w-full rounded-lg
                               border border-slate-300
                               px-3 py-2">

                    @error('date')

                    <p
                        class="mt-1 text-sm
                                   text-red-600">
                        {{ $message }}
                    </p>

                    @enderror

                </div>

            </div>

        </section>


        {{-- HORARIOS --}}
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
                    2. Nuevo horario
                </h2>

                <p
                    class="mt-1 text-sm
                           text-slate-500">
                    Solo aparecen horarios realmente disponibles.
                </p>

            </div>


            <div class="p-6">

                <div
                    wire:loading
                    wire:target="date"
                    class="py-8 text-center">
                    <p
                        class="text-sm
                               text-slate-500">
                        Consultando disponibilidad...
                    </p>
                </div>


                <div
                    wire:loading.remove
                    wire:target="date">

                    @if (! empty($availableSlots))

                    <div
                        class="grid grid-cols-2
                                   gap-3
                                   sm:grid-cols-3
                                   md:grid-cols-4
                                   lg:grid-cols-5">

                        @foreach (
                        $availableSlots
                        as $slotTime
                        )

                        <button
                            type="button"
                            wire:key="reschedule-{{ $date }}-{{ $slotTime }}"
                            wire:click="$set('time', '{{ $slotTime }}')"
                            class="rounded-lg
                                           border px-4 py-3
                                           text-sm font-semibold
                                           transition
                                           {{ $time === $slotTime
                                                ? 'border-slate-900 bg-slate-900 text-white'
                                                : 'border-slate-300 bg-white text-slate-700 hover:border-slate-400 hover:bg-slate-50'
                                           }}">
                            {{ $slotTime }}
                        </button>

                        @endforeach

                    </div>

                    @else

                    <div
                        class="rounded-lg
                                   border border-dashed
                                   border-slate-300
                                   bg-slate-50
                                   px-5 py-8
                                   text-center">

                        <p
                            class="font-medium
                                       text-slate-700">
                            No hay horarios disponibles
                        </p>

                        <p
                            class="mt-1 text-sm
                                       text-slate-500">
                            Selecciona otra fecha para consultar
                            disponibilidad.
                        </p>

                    </div>

                    @endif

                </div>


                @error('time')

                <p
                    class="mt-3 text-sm
                               text-red-600">
                    {{ $message }}
                </p>

                @enderror

            </div>

        </section>


        {{-- RESUMEN --}}
        @if ($date && $time)

        <section
            class="rounded-xl
                       border border-blue-200
                       bg-blue-50
                       px-5 py-4">

            <p
                class="text-xs font-semibold
                           uppercase tracking-wide
                           text-blue-600">
                Nuevo horario
            </p>

            <p
                class="mt-1 font-semibold
                           text-blue-900">
                {{ Carbon::parse($date)->format('d/m/Y') }}
                a las
                {{ $time }}
            </p>

            @if (
            $date
            === $appointment->starts_at->format('Y-m-d')
            && $time
            === $appointment->starts_at->format('H:i')
            )

            <p
                class="mt-1 text-sm
                               text-blue-700">
                Este es el horario actual de la cita.
            </p>

            @endif

        </section>

        @endif


        {{-- ACCIONES --}}
        <div
            class="flex flex-col-reverse
                   gap-3
                   sm:flex-row
                   sm:justify-end">

            <a
                href="{{ route(
                    'appointments.show',
                    [
                        'uuid' => $appointment->uuid,
                    ]
                ) }}"
                class="rounded-lg
                       border border-slate-300
                       px-4 py-2.5
                       text-center
                       text-sm font-medium
                       text-slate-700
                       hover:bg-slate-50">
                Cancelar
            </a>

            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="rescheduleAppointment"
                @disabled(! $date || ! $time)
                class="rounded-lg
                       bg-slate-900
                       px-5 py-2.5
                       text-sm font-semibold
                       text-white
                       disabled:cursor-not-allowed
                       disabled:opacity-50">

                <span
                    wire:loading.remove
                    wire:target="rescheduleAppointment">
                    Guardar nuevo horario
                </span>

                <span
                    wire:loading
                    wire:target="rescheduleAppointment">
                    Reprogramando...
                </span>

            </button>

        </div>

    </form>

</div>