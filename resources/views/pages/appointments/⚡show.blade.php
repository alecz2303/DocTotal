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
                    'communications' => fn($query) => $query
                        ->latest('created_at'),
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
                'communications' => fn($query) => $query
                    ->latest('created_at'),
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
'scheduled' => 'bg-blue-50 text-blue-700 ring-blue-200',
'confirmed' => 'bg-violet-50 text-violet-700 ring-violet-200',
'checked_in' => 'bg-amber-50 text-amber-700 ring-amber-200',
'in_progress' => 'bg-orange-50 text-orange-700 ring-orange-200',
'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
'cancelled' => 'bg-rose-50 text-rose-700 ring-rose-200',
'no_show' => 'bg-slate-100 text-slate-700 ring-slate-300',
];

$statusAccent = [
'scheduled' => 'from-blue-500 to-cyan-500',
'confirmed' => 'from-violet-500 to-indigo-500',
'checked_in' => 'from-amber-400 to-orange-500',
'in_progress' => 'from-orange-500 to-rose-500',
'completed' => 'from-emerald-500 to-teal-500',
'cancelled' => 'from-rose-500 to-pink-500',
'no_show' => 'from-slate-400 to-slate-500',
];

$statusLabel = $statusLabels[$appointment->status] ?? ucfirst($appointment->status);
$statusClass = $statusClasses[$appointment->status] ?? 'bg-slate-50 text-slate-700 ring-slate-200';
$statusGradient = $statusAccent[$appointment->status] ?? 'from-blue-500 to-violet-500';
@endphp

<div class="mx-auto max-w-6xl">

    <a
        href="{{ route('appointments.index') }}"
        class="inline-flex items-center gap-2 rounded-lg px-1 py-1 text-sm font-semibold text-slate-500 transition hover:text-blue-600">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" class="h-4 w-4">
            <path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        Volver a agenda
    </a>

    {{-- HERO --}}
    <section class="relative mt-4 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r {{ $statusGradient }}"></div>

        <div class="p-5 sm:p-6">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 ring-1 ring-blue-100">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5.5 w-5.5">
                                <rect x="3" y="5" width="18" height="16" rx="2" />
                                <path d="M8 3v4M16 3v4M3 10h18" stroke-linecap="round" />
                            </svg>
                        </div>

                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="text-2xl font-bold tracking-tight text-slate-950">Cita</h1>
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusClass }}">
                                    {{ $statusLabel }}
                                </span>
                            </div>

                            <p class="mt-1 text-sm text-slate-500">Detalle y seguimiento de la cita médica.</p>
                        </div>
                    </div>

                    <div class="mt-5">
                        <p class="text-xl font-bold text-slate-900">
                            {{ $appointment->patient->first_name }}
                            {{ $appointment->patient->last_name }}
                            {{ $appointment->patient->second_last_name }}
                        </p>

                        <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-sm">
                            <span class="inline-flex items-center gap-2 text-slate-600">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4 text-blue-500">
                                    <rect x="3" y="5" width="18" height="16" rx="2" />
                                    <path d="M8 3v4M16 3v4M3 10h18" />
                                </svg>
                                {{ ucfirst($appointment->starts_at->locale('es')->translatedFormat('l d \d\e F \d\e Y')) }}
                            </span>

                            <span class="inline-flex items-center gap-2 font-semibold tabular-nums text-slate-800">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4 text-violet-500">
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="M12 7v5l3 2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                {{ $appointment->starts_at->format('H:i') }} – {{ $appointment->ends_at->format('H:i') }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- ACTIONS --}}
                <div class="flex flex-wrap gap-2 xl:max-w-xl xl:justify-end">
                    <a
                        href="{{ route('patients.show', ['uuid' => $appointment->patient->uuid]) }}"
                        class="dt-btn dt-btn-secondary">
                        Ver paciente
                    </a>

                    @if ($appointment->canEditDetails())
                    <a
                        href="{{ route('appointments.edit', ['uuid' => $appointment->uuid]) }}"
                        class="dt-btn dt-btn-secondary">
                        Editar
                    </a>
                    @endif

                    @if ($appointment->canReschedule())
                    <a
                        href="{{ route('appointments.reschedule', ['uuid' => $appointment->uuid]) }}"
                        class="inline-flex items-center justify-center rounded-xl border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-700 transition hover:bg-blue-100">
                        Reprogramar
                    </a>
                    @endif

                    @if ($appointment->canConfirm())
                    <button
                        type="button"
                        wire:click="confirmAppointment"
                        class="inline-flex items-center justify-center rounded-xl border border-violet-200 bg-violet-50 px-4 py-2.5 text-sm font-semibold text-violet-700 transition hover:bg-violet-100">
                        Confirmar
                    </button>
                    @endif

                    @if ($appointment->canCheckIn())
                    <button
                        type="button"
                        wire:click="checkInAppointment"
                        class="inline-flex items-center justify-center rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-700 transition hover:bg-amber-100">
                        Paciente llegó
                    </button>
                    @endif

                    @if ($appointment->canStart())
                    <button
                        type="button"
                        wire:click="startAppointment"
                        class="dt-btn dt-btn-primary">
                        Iniciar consulta
                    </button>
                    @endif

                    @if ($appointment->status === Appointment::STATUS_IN_PROGRESS)
                    <button
                        type="button"
                        wire:click="continueConsultation"
                        class="dt-btn dt-btn-primary">
                        Continuar consulta
                    </button>
                    @endif

                    @if ($appointment->canMarkNoShow())
                    <button
                        type="button"
                        wire:click="markNoShow"
                        wire:confirm="¿Marcar esta cita como no presentada?"
                        class="dt-btn dt-btn-secondary">
                        No se presentó
                    </button>
                    @endif

                    @if ($appointment->canCancel())
                    <button
                        type="button"
                        wire:click="openCancelModal"
                        class="inline-flex items-center justify-center rounded-xl border border-rose-200 bg-white px-4 py-2.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-50">
                        Cancelar
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- CONTENT --}}
    <div class="mt-6 grid gap-6 lg:grid-cols-[1.35fr_0.85fr]">

        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4.5 w-4.5">
                        <path d="M5 4h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" />
                        <path d="M7 9h10M7 13h7" stroke-linecap="round" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-slate-950">Información de la cita</h2>
                    <p class="text-xs text-slate-500">Motivo y notas registradas.</p>
                </div>
            </div>

            <div class="grid gap-5 p-5 sm:p-6">
                <div class="rounded-xl border border-blue-100 bg-blue-50/50 p-4">
                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-blue-500">Motivo</p>
                    <p class="mt-2 text-sm leading-6 text-slate-800">
                        {{ $appointment->reason ?: 'Sin motivo registrado' }}
                    </p>
                </div>

                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-400">Notas internas</p>
                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">
                        {{ $appointment->notes ?: 'Sin notas registradas' }}
                    </p>
                </div>

                @if ($appointment->status === Appointment::STATUS_CANCELLED)
                <div class="rounded-xl border border-rose-100 bg-rose-50 p-4">
                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-rose-500">Motivo de cancelación</p>
                    <p class="mt-2 text-sm text-rose-700">
                        {{ $appointment->cancellation_reason ?: 'Sin motivo registrado' }}
                    </p>
                </div>
                @endif
            </div>
        </section>

        {{-- PATIENT --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-50 text-cyan-600">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4.5 w-4.5">
                        <circle cx="12" cy="8" r="4" />
                        <path d="M4 21a8 8 0 0 1 16 0" stroke-linecap="round" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-slate-950">Paciente</h2>
                    <p class="text-xs text-slate-500">Datos de contacto.</p>
                </div>
            </div>

            <div class="p-5 sm:p-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-500 to-blue-600 text-sm font-bold text-white shadow-sm">
                        {{ strtoupper(substr($appointment->patient->first_name, 0, 1)) }}
                    </div>

                    <div class="min-w-0">
                        <p class="truncate font-semibold text-slate-950">
                            {{ $appointment->patient->first_name }}
                            {{ $appointment->patient->last_name }}
                            {{ $appointment->patient->second_last_name }}
                        </p>
                        <p class="mt-0.5 text-xs text-slate-500">Paciente de DocTotal</p>
                    </div>
                </div>

                <div class="mt-5 space-y-3">
                    @if ($appointment->patient->phone)
                    <div class="flex items-center gap-3 rounded-xl bg-slate-50 px-3 py-2.5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                                <path d="M7 3H5a2 2 0 0 0-2 2c0 8.8 7.2 16 16 16a2 2 0 0 0 2-2v-2l-4-1-2 2c-3.5-1.5-6-4-7.5-7.5l2-2L7 3Z" stroke-linejoin="round" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Teléfono</p>
                            <p class="text-sm font-medium text-slate-700">{{ $appointment->patient->phone }}</p>
                        </div>
                    </div>
                    @endif

                    @if ($appointment->patient->email)
                    <div class="flex items-center gap-3 rounded-xl bg-slate-50 px-3 py-2.5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-50 text-violet-600">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                                <rect x="3" y="5" width="18" height="14" rx="2" />
                                <path d="m4 7 8 6 8-6" stroke-linejoin="round" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Correo</p>
                            <p class="truncate text-sm font-medium text-slate-700">{{ $appointment->patient->email }}</p>
                        </div>
                    </div>
                    @endif
                </div>

                <a
                    href="{{ route('patients.show', ['uuid' => $appointment->patient->uuid]) }}"
                    class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 transition hover:text-blue-700">
                    Abrir expediente
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" class="h-4 w-4">
                        <path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </a>
            </div>
        </section>
    </div>

    {{-- TIMELINE --}}
    <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
        <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4.5 w-4.5">
                    <path d="M4 12h4l2-5 4 10 2-5h4" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <div>
                <h2 class="font-semibold text-slate-950">Seguimiento</h2>
                <p class="text-xs text-slate-500">Evolución del estado de la cita.</p>
            </div>
        </div>

        @php
        $timeline = [
        ['Programada', $appointment->created_at, 'bg-blue-500', 'bg-blue-50 text-blue-600'],
        ['Confirmada', $appointment->confirmed_at, 'bg-violet-500', 'bg-violet-50 text-violet-600'],
        ['Llegada', $appointment->checked_in_at, 'bg-amber-500', 'bg-amber-50 text-amber-600'],
        ['Inicio', $appointment->started_at, 'bg-orange-500', 'bg-orange-50 text-orange-600'],
        ['Finalización', $appointment->completed_at, 'bg-emerald-500', 'bg-emerald-50 text-emerald-600'],
        ];
        @endphp

        <div class="grid gap-3 p-5 sm:grid-cols-2 sm:p-6 lg:grid-cols-5">
            @foreach ($timeline as [$label, $timestamp, $dotClass, $boxClass])
            <div class="relative rounded-xl border border-slate-100 bg-slate-50/60 p-4">
                <div class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full {{ $timestamp ? $dotClass : 'bg-slate-300' }}"></span>
                    <p class="text-[11px] font-bold uppercase tracking-wider {{ $timestamp ? 'text-slate-600' : 'text-slate-400' }}">
                        {{ $label }}
                    </p>
                </div>
                <p class="mt-2 text-sm font-semibold tabular-nums {{ $timestamp ? 'text-slate-800' : 'text-slate-400' }}">
                    {{ $timestamp ? $timestamp->format('d/m/Y H:i') : '—' }}
                </p>
            </div>
            @endforeach
        </div>
    </section>

    {{-- COMMUNICATIONS --}}
    <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
        <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    class="h-4.5 w-4.5">
                    <path
                        d="M4 5h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" />
                    <path
                        d="m4 7 8 6 8-6"
                        stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
            </div>

            <div>
                <h2 class="font-semibold text-slate-950">
                    Comunicaciones
                </h2>

                <p class="text-xs text-slate-500">
                    Historial de recordatorios y comunicaciones de esta cita.
                </p>
            </div>
        </div>

        @if ($appointment->communications->isEmpty())
        <div class="px-5 py-10 text-center sm:px-6">
            <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    class="h-5 w-5">
                    <path
                        d="M4 5h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" />
                    <path
                        d="m4 7 8 6 8-6"
                        stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
            </div>

            <p class="mt-3 text-sm font-semibold text-slate-700">
                Sin comunicaciones registradas
            </p>

            <p class="mt-1 text-xs text-slate-500">
                Los recordatorios generados para esta cita aparecerán aquí.
            </p>
        </div>
        @else
        @php
        $communicationStatusLabels = [
        'pending' => 'Pendiente',
        'sent' => 'Enviada',
        'failed' => 'Fallida',
        'cancelled' => 'Cancelada',
        ];

        $communicationStatusClasses = [
        'pending' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'sent' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'failed' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'cancelled' => 'bg-slate-100 text-slate-600 ring-slate-200',
        ];

        $channelLabels = [
        'whatsapp' => 'WhatsApp',
        'email' => 'Correo',
        'sms' => 'SMS',
        ];

        $typeLabels = [
        'appointment_reminder' => 'Recordatorio de cita',
        'appointment_confirmation' => 'Confirmación de cita',
        ];
        @endphp

        <div class="divide-y divide-slate-100">
            @foreach ($appointment->communications as $communication)
            @php
            $communicationStatusLabel =
            $communicationStatusLabels[$communication->status]
            ?? ucfirst($communication->status);

            $communicationStatusClass =
            $communicationStatusClasses[$communication->status]
            ?? 'bg-slate-50 text-slate-700 ring-slate-200';

            $channelLabel =
            $channelLabels[$communication->channel]
            ?? ucfirst($communication->channel);

            $typeLabel =
            $typeLabels[$communication->type]
            ?? ucfirst(
            str_replace(
            '_',
            ' ',
            $communication->type
            )
            );
            @endphp

            <article class="p-5 sm:p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-semibold text-slate-900">
                                {{ $typeLabel }}
                            </p>

                            <span
                                class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $communicationStatusClass }}">
                                {{ $communicationStatusLabel }}
                            </span>
                        </div>

                        <div class="mt-2 flex flex-wrap gap-x-5 gap-y-2 text-sm text-slate-500">
                            <span>
                                <span class="font-medium text-slate-700">
                                    Canal:
                                </span>

                                {{ $channelLabel }}
                            </span>

                            <span>
                                <span class="font-medium text-slate-700">
                                    Destinatario:
                                </span>

                                {{ $communication->recipient }}
                            </span>
                        </div>
                    </div>

                    <div class="shrink-0 text-left lg:text-right">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Creada
                        </p>

                        <p class="mt-1 text-sm font-medium tabular-nums text-slate-700">
                            {{ $communication->created_at->format('d/m/Y H:i') }}
                        </p>
                    </div>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl bg-slate-50 px-4 py-3">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Programada
                        </p>

                        <p class="mt-1 text-sm font-semibold tabular-nums text-slate-700">
                            {{ $communication->scheduled_for?->format('d/m/Y H:i') ?? '—' }}
                        </p>
                    </div>

                    <div class="rounded-xl bg-slate-50 px-4 py-3">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Enviada
                        </p>

                        <p class="mt-1 text-sm font-semibold tabular-nums text-slate-700">
                            {{ $communication->sent_at?->format('d/m/Y H:i') ?? '—' }}
                        </p>
                    </div>

                    <div class="rounded-xl bg-slate-50 px-4 py-3">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Intentos
                        </p>

                        <p class="mt-1 text-sm font-semibold text-slate-700">
                            {{ $communication->attempt_count }}
                        </p>
                    </div>

                    <div class="rounded-xl bg-slate-50 px-4 py-3">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Próximo intento
                        </p>

                        <p class="mt-1 text-sm font-semibold tabular-nums text-slate-700">
                            {{ $communication->next_attempt_at?->format('d/m/Y H:i') ?? '—' }}
                        </p>
                    </div>
                </div>

                @if ($communication->isCancelled())
                <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Cancelada
                        </p>

                        @if ($communication->cancelled_at)
                        <p class="text-xs font-medium tabular-nums text-slate-500">
                            {{ $communication->cancelled_at->format('d/m/Y H:i') }}
                        </p>
                        @endif
                    </div>

                    <p class="mt-2 text-sm text-slate-600">
                        {{ $communication->cancellation_reason ?: 'Sin motivo registrado.' }}
                    </p>
                </div>
                @endif

                @if ($communication->isFailed() && $communication->last_error)
                <div class="mt-4 rounded-xl border border-rose-100 bg-rose-50 p-4">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-rose-500">
                        Último error
                    </p>

                    <p class="mt-2 break-words text-sm text-rose-700">
                        {{ $communication->last_error }}
                    </p>
                </div>
                @endif
            </article>
            @endforeach
        </div>
        @endif
    </section>

    {{-- CANCEL MODAL --}}
    @if ($showCancelModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm">
        <div class="w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
            <div class="flex items-start gap-3 border-b border-slate-100 px-6 py-5">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                        <circle cx="12" cy="12" r="9" />
                        <path d="m9 9 6 6M15 9l-6 6" stroke-linecap="round" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-slate-950">Cancelar cita</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Puedes registrar el motivo de cancelación.
                    </p>
                </div>
            </div>

            <form wire:submit="cancelAppointment">
                <div class="p-6">
                    <label class="dt-label">Motivo</label>
                    <textarea
                        wire:model="cancellationReason"
                        rows="4"
                        maxlength="500"
                        placeholder="Ej. El paciente solicitó reprogramar..."
                        class="dt-textarea"></textarea>

                    @error('cancellationReason')
                    <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end gap-3 border-t border-slate-100 bg-slate-50/70 px-6 py-4">
                    <button type="button" wire:click="closeCancelModal" class="dt-btn dt-btn-secondary">
                        Volver
                    </button>

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700">
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