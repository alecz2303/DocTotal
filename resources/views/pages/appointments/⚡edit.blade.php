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
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500 to-blue-600 text-white shadow-sm shadow-violet-200">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5.5 w-5.5">
                <path d="M4 20h4l10-10a2.8 2.8 0 0 0-4-4L4 16v4Z" stroke-linejoin="round" />
                <path d="m13 7 4 4" />
            </svg>
        </div>

        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-950">
                Editar cita
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                Actualiza la información administrativa de la cita.
            </p>
        </div>
    </div>

    {{-- APPOINTMENT SUMMARY --}}
    <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
        <div class="h-1 bg-gradient-to-r from-blue-500 via-violet-500 to-cyan-500"></div>

        <div class="grid gap-4 p-5 sm:grid-cols-[1fr_auto] sm:items-center sm:p-6">
            <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-500 to-blue-600 text-sm font-bold text-white shadow-sm">
                    {{ strtoupper(substr($appointment->patient->first_name, 0, 1)) }}
                </div>

                <div class="min-w-0">
                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-400">
                        Paciente
                    </p>

                    <p class="mt-1 truncate text-lg font-semibold text-slate-950">
                        {{ $appointment->patient->first_name }}
                        {{ $appointment->patient->last_name }}
                        {{ $appointment->patient->second_last_name }}
                    </p>
                </div>
            </div>

            <div class="rounded-2xl border border-blue-100 bg-blue-50/70 px-4 py-3 sm:min-w-56 sm:text-right">
                <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-blue-500">
                    Fecha y horario
                </p>

                <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 sm:justify-end">
                    <span class="font-semibold text-slate-900">
                        {{ $appointment->starts_at->format('d/m/Y') }}
                    </span>

                    <span class="text-sm font-semibold tabular-nums text-blue-700">
                        {{ $appointment->starts_at->format('H:i') }}
                        –
                        {{ $appointment->ends_at->format('H:i') }}
                    </span>
                </div>
            </div>
        </div>
    </section>

    {{-- FORM --}}
    <form wire:submit="saveAppointment" class="mt-6 space-y-6">

        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4.5 w-4.5">
                        <path d="M5 4h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" />
                        <path d="M7 9h10M7 13h7" stroke-linecap="round" />
                    </svg>
                </div>

                <div>
                    <h2 class="font-semibold text-slate-950">
                        Información de la cita
                    </h2>
                    <p class="text-xs text-slate-500">
                        Aquí puedes modificar el motivo y las notas internas.
                    </p>
                </div>
            </div>

            <div class="grid gap-5 p-5 sm:p-6">
                <div>
                    <label class="dt-label">
                        Motivo
                    </label>

                    <input
                        wire:model="reason"
                        type="text"
                        maxlength="500"
                        placeholder="Ej. Consulta general"
                        class="dt-input">

                    @error('reason')
                    <p class="mt-1 text-sm text-rose-600">
                        {{ $message }}
                    </p>
                    @enderror
                </div>

                <div>
                    <div class="mb-1 flex items-center justify-between gap-3">
                        <label class="dt-label mb-0">
                            Notas internas
                        </label>

                        <span class="text-[11px] font-medium text-slate-400">
                            Solo visibles para el personal
                        </span>
                    </div>

                    <textarea
                        wire:model="notes"
                        rows="5"
                        maxlength="5000"
                        placeholder="Información interna relacionada con la cita..."
                        class="dt-textarea"></textarea>

                    @error('notes')
                    <p class="mt-1 text-sm text-rose-600">
                        {{ $message }}
                    </p>
                    @enderror
                </div>
            </div>
        </section>

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
                class="dt-btn dt-btn-primary inline-flex items-center justify-center gap-2 disabled:opacity-50">
                <svg
                    wire:loading.remove
                    wire:target="saveAppointment"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.9"
                    class="h-4 w-4">
                    <path d="M5 12.5 9.2 17 19 7" stroke-linecap="round" stroke-linejoin="round" />
                </svg>

                <span wire:loading.remove wire:target="saveAppointment">
                    Guardar cambios
                </span>

                <span wire:loading wire:target="saveAppointment">
                    Guardando...
                </span>
            </button>
        </div>

    </form>

</div>