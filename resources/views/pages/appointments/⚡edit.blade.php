<?php

use App\Models\Appointment;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
    #[Layout('layouts::app')]
    #[Title('Editar cita | DocTotal')]
    class extends Component
    {
        public Appointment $appointment;

        public string $reason = '';
        public string $notes = '';

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
                $this->appointment->canEditDetails(),
                404
            );

            $this->reason =
                $this->appointment->reason ?? '';

            $this->notes =
                $this->appointment->notes ?? '';
        }

        public function saveAppointment(): void
        {
            $validated = $this->validate([
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

            $this->appointment->updateDetails(
                $validated['reason'] ?: null,
                $validated['notes'] ?: null
            );

            session()->flash(
                'success',
                'La cita fue actualizada correctamente.'
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


    <div class="mt-4">

        <h1
            class="text-2xl font-bold
                   tracking-tight
                   text-slate-900">
            Editar cita
        </h1>

        <p
            class="mt-1 text-sm
                   text-slate-500">
            Actualiza la información administrativa de la cita.
        </p>

    </div>


    {{-- CITA --}}
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
                    Fecha y horario
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
        wire:submit="saveAppointment"
        class="mt-6 space-y-6">

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

                <p
                    class="mt-1 text-sm
                           text-slate-500">
                    Aquí puedes modificar el motivo y las notas internas.
                </p>

            </div>


            <div
                class="grid gap-5 p-6">

                <div>

                    <label
                        class="mb-1 block
                               text-sm font-medium
                               text-slate-700">
                        Motivo
                    </label>

                    <input
                        wire:model="reason"
                        type="text"
                        maxlength="500"
                        placeholder="Ej. Consulta general"
                        class="w-full rounded-lg
                               border border-slate-300
                               px-3 py-2">

                    @error('reason')

                    <p
                        class="mt-1 text-sm
                                   text-red-600">
                        {{ $message }}
                    </p>

                    @enderror

                </div>


                <div>

                    <label
                        class="mb-1 block
                               text-sm font-medium
                               text-slate-700">
                        Notas internas
                    </label>

                    <textarea
                        wire:model="notes"
                        rows="5"
                        maxlength="5000"
                        placeholder="Información interna relacionada con la cita..."
                        class="w-full rounded-lg
                               border border-slate-300
                               px-3 py-2"></textarea>

                    @error('notes')

                    <p
                        class="mt-1 text-sm
                                   text-red-600">
                        {{ $message }}
                    </p>

                    @enderror

                </div>

            </div>

        </section>


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
                class="rounded-lg
                       bg-slate-900
                       px-5 py-2.5
                       text-sm font-semibold
                       text-white
                       disabled:opacity-50">

                <span
                    wire:loading.remove
                    wire:target="saveAppointment">
                    Guardar cambios
                </span>

                <span
                    wire:loading
                    wire:target="saveAppointment">
                    Guardando...
                </span>

            </button>

        </div>

    </form>

</div>