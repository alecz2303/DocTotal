<?php

use App\Models\Appointment;
use App\Models\Consultation;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
    #[Layout('layouts::app')]
    #[Title('Cita | DocTotal')]
    class extends Component
    {
        public Appointment $appointment;

        public bool $showCancelModal = false;

        public string $cancellationReason = '';

        public function mount(string $uuid): void
        {
            $this->loadAppointment($uuid);
        }

        public function confirmAppointment(): void
        {
            $this->appointment->confirm();

            $this->refreshAppointment();

            $this->dispatch(
                'swal',
                title: 'Cita confirmada',
                text: 'La cita fue confirmada correctamente.',
                icon: 'success'
            );
        }

        public function checkInAppointment(): void
        {
            $this->appointment->checkIn();

            $this->refreshAppointment();

            $this->dispatch(
                'swal',
                title: 'Llegada registrada',
                text: 'El paciente fue registrado como presente.',
                icon: 'success'
            );
        }

        public function startAppointment(): void
        {
            $this->appointment->start();

            $consultation = Consultation::query()
                ->firstOrCreate(
                    [
                        'appointment_id' => $this->appointment->id,
                    ],
                    [
                        'patient_id' => $this->appointment->patient_id,
                        'doctor_profile_id' => $this->appointment->doctor_profile_id,
                        'consultation_at' => now(),
                        'reason' => $this->appointment->reason,
                        'status' => Consultation::STATUS_DRAFT,
                    ]
                );

            $this->redirectRoute(
                'consultations.create',
                [
                    'uuid' => $this->appointment->patient->uuid,
                    'appointment' => $this->appointment->uuid,
                ]
            );
        }

        public function openCancelModal(): void
        {
            $this->resetValidation();

            $this->cancellationReason = '';

            $this->showCancelModal = true;
        }

        public function closeCancelModal(): void
        {
            $this->showCancelModal = false;

            $this->cancellationReason = '';

            $this->resetValidation();
        }

        public function cancelAppointment(): void
        {
            $validated = $this->validate([
                'cancellationReason' => [
                    'nullable',
                    'string',
                    'max:500',
                ],
            ]);

            $this->appointment->cancel(
                $validated['cancellationReason'] ?: null
            );

            $this->showCancelModal = false;

            $this->cancellationReason = '';

            $this->refreshAppointment();

            $this->dispatch(
                'swal',
                title: 'Cita cancelada',
                text: 'La cita fue cancelada correctamente.',
                icon: 'success'
            );
        }

        public function markNoShow(): void
        {
            $this->appointment->markNoShow();

            $this->refreshAppointment();

            $this->dispatch(
                'swal',
                title: 'No se presentó',
                text: 'La cita fue marcada como no presentada.',
                icon: 'success'
            );
        }

        private function loadAppointment(string $uuid): void
        {
            $this->appointment = Appointment::query()
                ->with([
                    'patient',
                    'doctorProfile.specialty',
                    'consultation',
                ])
                ->where('uuid', $uuid)
                ->firstOrFail();
        }

        private function refreshAppointment(): void
        {
            $this->appointment->refresh();

            $this->appointment->load([
                'patient',
                'doctorProfile.specialty',
                'consultation',
            ]);
        }

        public function continueConsultation(): void
        {
            $this->appointment->loadMissing('consultation');

            $consultation = $this->appointment->consultation;

            if (! $consultation) {
                $consultation = Consultation::create([
                    'patient_id' => $this->appointment->patient_id,
                    'doctor_profile_id' => $this->appointment->doctor_profile_id,
                    'appointment_id' => $this->appointment->id,
                    'consultation_at' => now(),
                    'reason' => $this->appointment->reason,
                    'status' => Consultation::STATUS_DRAFT,
                ]);
            }

            if ($consultation->isCompleted()) {
                $this->redirectRoute(
                    'consultations.show',
                    [
                        'uuid' => $consultation->uuid,
                    ]
                );

                return;
            }

            $this->redirectRoute(
                'consultations.create',
                [
                    'uuid' => $this->appointment->patient->uuid,
                    'appointment' => $this->appointment->uuid,
                ]
            );
        }
    };
?>

@php
$statusLabels = [
'scheduled' => 'Programada',
'confirmed' => 'Confirmada',
'checked_in' => 'Paciente llegó',
'in_progress' => 'En atención',
'completed' => 'Completada',
'cancelled' => 'Cancelada',
'no_show' => 'No se presentó',
];

$statusClasses = [
'scheduled' =>
'bg-blue-50 text-blue-700 ring-blue-200',

'confirmed' =>
'bg-indigo-50 text-indigo-700 ring-indigo-200',

'checked_in' =>
'bg-amber-50 text-amber-700 ring-amber-200',

'in_progress' =>
'bg-orange-50 text-orange-700 ring-orange-200',

'completed' =>
'bg-green-50 text-green-700 ring-green-200',

'cancelled' =>
'bg-red-50 text-red-700 ring-red-200',

'no_show' =>
'bg-slate-100 text-slate-700 ring-slate-300',
];

$statusLabel =
$statusLabels[$appointment->status]
?? ucfirst($appointment->status);

$statusClass =
$statusClasses[$appointment->status]
?? 'bg-slate-50 text-slate-700 ring-slate-200';
@endphp

<div class="mx-auto max-w-6xl">

    {{-- REGRESO --}}
    <a
        href="{{ route('appointments.index') }}"
        class="text-sm font-medium
               text-slate-500
               hover:text-slate-900">
        ← Volver a agenda
    </a>


    {{-- HEADER --}}
    <div
        class="mt-4 flex flex-col gap-5
               rounded-xl border border-slate-200
               bg-white p-6 shadow-sm
               lg:flex-row lg:items-start
               lg:justify-between">

        <div>

            <div
                class="flex flex-wrap
                       items-center gap-3">

                <h1
                    class="text-2xl font-bold
                           text-slate-900">
                    Cita
                </h1>

                <span
                    class="inline-flex rounded-full
                           px-2.5 py-1
                           text-xs font-semibold
                           ring-1 ring-inset
                           {{ $statusClass }}">
                    {{ $statusLabel }}
                </span>

            </div>

            <p
                class="mt-3 text-lg
                       font-semibold
                       text-slate-800">
                {{ $appointment->patient->first_name }}
                {{ $appointment->patient->last_name }}
                {{ $appointment->patient->second_last_name }}
            </p>

            <p
                class="mt-1 text-sm
                       text-slate-500">
                {{ ucfirst(
                    $appointment
                        ->starts_at
                        ->locale('es')
                        ->translatedFormat(
                            'l d \d\e F \d\e Y'
                        )
                ) }}
            </p>

            <p
                class="mt-1 text-sm
                       font-medium
                       text-slate-700">
                {{ $appointment->starts_at->format('H:i') }}
                —
                {{ $appointment->ends_at->format('H:i') }}
            </p>

        </div>


        {{-- ACCIONES --}}
        <div
            class="flex flex-wrap
                   justify-start gap-2
                   lg:max-w-xl
                   lg:justify-end">

            <a
                href="{{ route(
                    'patients.show',
                    [
                        'uuid' =>
                            $appointment->patient->uuid
                    ]
                ) }}"
                class="inline-flex items-center
                       rounded-lg
                       border border-slate-300
                       px-4 py-2.5
                       text-sm font-semibold
                       text-slate-700
                       hover:bg-slate-50">
                Ver paciente
            </a>

            @if ($appointment->canEditDetails())

            <a
                href="{{ route(
                        'appointments.edit',
                        [
                            'uuid' => $appointment->uuid,
                        ]
                    ) }}"
                class="inline-flex items-center
                        rounded-lg
                        border border-slate-300
                        bg-white
                        px-4 py-2.5
                        text-sm font-semibold
                        text-slate-700
                        hover:bg-slate-50">
                Editar
            </a>

            @endif

            @if ($appointment->canReschedule())

            <a
                href="{{ route(
                        'appointments.reschedule',
                        [
                            'uuid' => $appointment->uuid,
                        ]
                    ) }}"
                class="inline-flex items-center
                        rounded-lg
                        border border-blue-200
                        bg-blue-50
                        px-4 py-2.5
                        text-sm font-semibold
                        text-blue-700
                        hover:bg-blue-100">
                Reprogramar
            </a>

            @endif

            @if ($appointment->canConfirm())

            <button
                type="button"
                wire:click="confirmAppointment"
                class="inline-flex items-center
                           rounded-lg
                           border border-indigo-200
                           bg-indigo-50
                           px-4 py-2.5
                           text-sm font-semibold
                           text-indigo-700
                           hover:bg-indigo-100">
                Confirmar
            </button>

            @endif


            @if ($appointment->canCheckIn())

            <button
                type="button"
                wire:click="checkInAppointment"
                class="inline-flex items-center
                           rounded-lg
                           border border-amber-200
                           bg-amber-50
                           px-4 py-2.5
                           text-sm font-semibold
                           text-amber-700
                           hover:bg-amber-100">
                Paciente llegó
            </button>

            @endif


            @if ($appointment->canStart())

            <button
                type="button"
                wire:click="startAppointment"
                class="inline-flex items-center
                           rounded-lg
                           bg-slate-900
                           px-4 py-2.5
                           text-sm font-semibold
                           text-white
                           hover:bg-slate-800">
                Iniciar consulta
            </button>

            @endif

            @if (
            $appointment->status
            === Appointment::STATUS_IN_PROGRESS
            )

            <button
                type="button"
                wire:click="continueConsultation"
                class="inline-flex items-center
                    rounded-lg
                    bg-slate-900
                    px-4 py-2.5
                    text-sm font-semibold
                    text-white
                    hover:bg-slate-800">
                Continuar consulta
            </button>

            @endif


            @if ($appointment->canMarkNoShow())

            <button
                type="button"
                wire:click="markNoShow"
                wire:confirm="¿Marcar esta cita como no presentada?"
                class="inline-flex items-center
                           rounded-lg
                           border border-slate-300
                           px-4 py-2.5
                           text-sm font-semibold
                           text-slate-600
                           hover:bg-slate-50">
                No se presentó
            </button>

            @endif


            @if ($appointment->canCancel())

            <button
                type="button"
                wire:click="openCancelModal"
                class="inline-flex items-center
                           rounded-lg
                           border border-red-200
                           px-4 py-2.5
                           text-sm font-semibold
                           text-red-700
                           hover:bg-red-50">
                Cancelar
            </button>

            @endif

        </div>

    </div>


    {{-- CONTENIDO --}}
    <div
        class="mt-6 grid gap-6
               lg:grid-cols-[1.4fr_1fr]">

        {{-- INFORMACIÓN --}}
        <section
            class="rounded-xl
                   border border-slate-200
                   bg-white shadow-sm">

            <div
                class="border-b border-slate-200
                       px-6 py-4">
                <h2
                    class="font-semibold
                           text-slate-900">
                    Información de la cita
                </h2>
            </div>

            <div class="space-y-5 p-6">

                <div>

                    <p
                        class="text-xs font-semibold
                               uppercase tracking-wide
                               text-slate-500">
                        Motivo
                    </p>

                    <p
                        class="mt-1 text-sm
                               text-slate-800">
                        {{ $appointment->reason
                            ?: 'Sin motivo registrado' }}
                    </p>

                </div>


                <div>

                    <p
                        class="text-xs font-semibold
                               uppercase tracking-wide
                               text-slate-500">
                        Notas internas
                    </p>

                    <p
                        class="mt-1 whitespace-pre-line
                               text-sm text-slate-700">
                        {{ $appointment->notes
                            ?: 'Sin notas registradas' }}
                    </p>

                </div>


                @if (
                $appointment->status
                === Appointment::STATUS_CANCELLED
                )

                <div
                    class="rounded-lg
                               border border-red-100
                               bg-red-50 p-4">

                    <p
                        class="text-xs font-semibold
                                   uppercase tracking-wide
                                   text-red-500">
                        Motivo de cancelación
                    </p>

                    <p
                        class="mt-1 text-sm
                                   text-red-700">
                        {{ $appointment->cancellation_reason
                                ?: 'Sin motivo registrado' }}
                    </p>

                </div>

                @endif

            </div>

        </section>


        {{-- PACIENTE --}}
        <section
            class="rounded-xl
                   border border-slate-200
                   bg-white shadow-sm">

            <div
                class="border-b border-slate-200
                       px-6 py-4">
                <h2
                    class="font-semibold
                           text-slate-900">
                    Paciente
                </h2>
            </div>

            <div class="space-y-4 p-6">

                <div>

                    <p
                        class="text-sm font-semibold
                               text-slate-900">
                        {{ $appointment->patient->first_name }}
                        {{ $appointment->patient->last_name }}
                        {{ $appointment->patient->second_last_name }}
                    </p>

                </div>

                @if ($appointment->patient->phone)

                <div>

                    <p
                        class="text-xs font-semibold
                                   uppercase tracking-wide
                                   text-slate-500">
                        Teléfono
                    </p>

                    <p
                        class="mt-1 text-sm
                                   text-slate-700">
                        {{ $appointment->patient->phone }}
                    </p>

                </div>

                @endif

                @if ($appointment->patient->email)

                <div>

                    <p
                        class="text-xs font-semibold
                                   uppercase tracking-wide
                                   text-slate-500">
                        Correo
                    </p>

                    <p
                        class="mt-1 text-sm
                                   text-slate-700">
                        {{ $appointment->patient->email }}
                    </p>

                </div>

                @endif

                <a
                    href="{{ route(
                        'patients.show',
                        [
                            'uuid' =>
                                $appointment->patient->uuid
                        ]
                    ) }}"
                    class="inline-flex
                           text-sm font-semibold
                           text-slate-600
                           hover:text-slate-900">
                    Abrir expediente →
                </a>

            </div>

        </section>

    </div>


    {{-- TIMELINE --}}
    <section
        class="mt-6
               rounded-xl
               border border-slate-200
               bg-white shadow-sm">

        <div
            class="border-b border-slate-200
                   px-6 py-4">
            <h2
                class="font-semibold
                       text-slate-900">
                Seguimiento
            </h2>
        </div>

        <div
            class="grid gap-4
                   p-6
                   sm:grid-cols-2
                   lg:grid-cols-5">

            <div>

                <p
                    class="text-xs font-semibold
                           uppercase tracking-wide
                           text-slate-500">
                    Programada
                </p>

                <p
                    class="mt-1 text-sm
                           text-slate-700">
                    {{ $appointment->created_at->format(
                        'd/m/Y H:i'
                    ) }}
                </p>

            </div>

            <div>

                <p
                    class="text-xs font-semibold
                           uppercase tracking-wide
                           text-slate-500">
                    Confirmada
                </p>

                <p
                    class="mt-1 text-sm
                           text-slate-700">
                    {{ $appointment->confirmed_at
                        ? $appointment->confirmed_at->format(
                            'd/m/Y H:i'
                        )
                        : '—' }}
                </p>

            </div>

            <div>

                <p
                    class="text-xs font-semibold
                           uppercase tracking-wide
                           text-slate-500">
                    Llegada
                </p>

                <p
                    class="mt-1 text-sm
                           text-slate-700">
                    {{ $appointment->checked_in_at
                        ? $appointment->checked_in_at->format(
                            'd/m/Y H:i'
                        )
                        : '—' }}
                </p>

            </div>

            <div>

                <p
                    class="text-xs font-semibold
                           uppercase tracking-wide
                           text-slate-500">
                    Inicio
                </p>

                <p
                    class="mt-1 text-sm
                           text-slate-700">
                    {{ $appointment->started_at
                        ? $appointment->started_at->format(
                            'd/m/Y H:i'
                        )
                        : '—' }}
                </p>

            </div>

            <div>

                <p
                    class="text-xs font-semibold
                           uppercase tracking-wide
                           text-slate-500">
                    Finalización
                </p>

                <p
                    class="mt-1 text-sm
                           text-slate-700">
                    {{ $appointment->completed_at
                        ? $appointment->completed_at->format(
                            'd/m/Y H:i'
                        )
                        : '—' }}
                </p>

            </div>

        </div>

    </section>


    {{-- MODAL CANCELACIÓN --}}
    @if ($showCancelModal)

    <div
        class="fixed inset-0 z-50
                   flex items-center
                   justify-center
                   bg-slate-950/50
                   p-4">

        <div
            class="w-full max-w-lg
                       rounded-2xl
                       bg-white shadow-2xl">

            <div
                class="border-b
                           border-slate-200
                           px-6 py-4">

                <h2
                    class="text-lg font-semibold
                               text-slate-900">
                    Cancelar cita
                </h2>

                <p
                    class="mt-1 text-sm
                               text-slate-500">
                    Puedes registrar el motivo de cancelación.
                </p>

            </div>

            <form
                wire:submit="cancelAppointment">

                <div class="p-6">

                    <label
                        class="mb-1 block
                                   text-sm font-medium">
                        Motivo
                    </label>

                    <textarea
                        wire:model="cancellationReason"
                        rows="4"
                        maxlength="500"
                        placeholder="Ej. El paciente solicitó reprogramar..."
                        class="w-full rounded-lg
                                   border border-slate-300
                                   px-3 py-2"></textarea>

                    @error('cancellationReason')

                    <p
                        class="mt-1 text-sm
                                       text-red-600">
                        {{ $message }}
                    </p>

                    @enderror

                </div>

                <div
                    class="flex justify-end gap-3
                               border-t border-slate-200
                               px-6 py-4">

                    <button
                        type="button"
                        wire:click="closeCancelModal"
                        class="rounded-lg
                                   border border-slate-300
                                   px-4 py-2.5
                                   text-sm font-medium
                                   text-slate-700
                                   hover:bg-slate-50">
                        Volver
                    </button>

                    <button
                        type="submit"
                        class="rounded-lg
                                   bg-red-600
                                   px-4 py-2.5
                                   text-sm font-semibold
                                   text-white
                                   hover:bg-red-700">
                        Cancelar cita
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