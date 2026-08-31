<?php

use App\Models\Prescription;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
    #[Layout('layouts::app')]
    #[Title('Receta | DocTotal')]
    class extends Component
    {
        public Prescription $prescription;

        public function mount(string $uuid): void
        {
            $this->prescription = Prescription::query()
                ->with([
                    'patient',
                    'doctorProfile',
                    'consultation',
                    'items',
                ])
                ->where('uuid', $uuid)
                ->firstOrFail();
        }

        public function cancelPrescription(): void
        {
            if ($this->prescription->status !== 'active') {
                return;
            }

            $this->prescription->update([
                'status' => 'cancelled',
            ]);

            $this->prescription->refresh();

            $this->dispatch(
                'swal',
                title: 'Receta anulada',
                text: 'La receta fue anulada correctamente.',
                icon: 'success'
            );
        }
    };
?>

<div class="mx-auto max-w-5xl">

    {{-- BACK --}}
    <div class="mb-5">
        @if ($prescription->consultation)
        <a href="{{ route('consultations.show', ['uuid' => $prescription->consultation->uuid]) }}"
            class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-blue-600">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" class="h-4 w-4">
                <path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            Volver a la consulta
        </a>
        @else
        <a href="{{ route('patients.show', ['uuid' => $prescription->patient->uuid]) }}"
            class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-blue-600">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" class="h-4 w-4">
                <path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            Volver al expediente
        </a>
        @endif
    </div>

    {{-- HERO / ACTIONS --}}
    <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
        <div class="{{ $prescription->status === 'cancelled'
            ? 'bg-gradient-to-r from-rose-500 to-red-500'
            : 'bg-gradient-to-r from-cyan-500 via-blue-500 to-violet-500' }} h-1"></div>

        <div class="p-5 sm:p-6">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex min-w-0 items-start gap-4">
                    <div class="flex h-13 w-13 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-500 to-blue-600 text-white shadow-sm">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-6 w-6">
                            <path d="M7 3h10v18H7z" />
                            <path d="M10 8h4M10 12h4M10 16h3" stroke-linecap="round" />
                        </svg>
                    </div>

                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-2xl font-bold tracking-tight text-slate-950">Receta médica</h1>

                            @if ($prescription->status === 'cancelled')
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-1 text-[11px] font-bold text-rose-700 ring-1 ring-inset ring-rose-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                Anulada
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                Activa
                            </span>
                            @endif
                        </div>

                        <p class="mt-1 truncate text-base font-semibold text-slate-700">
                            {{ $prescription->patient->first_name }}
                            {{ $prescription->patient->last_name }}
                        </p>

                        <p class="mt-1 text-sm text-slate-500">
                            Emitida el
                            {{ $prescription->prescribed_at->locale('es')->translatedFormat('d \d\e F \d\e Y') }}
                            · {{ $prescription->prescribed_at->format('H:i') }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2 lg:max-w-md lg:justify-end">
                    @if ($prescription->status === 'active')
                    <a href="{{ route('prescriptions.edit', ['uuid' => $prescription->uuid]) }}"
                        class="dt-btn dt-btn-secondary">
                        Editar
                    </a>
                    @endif

                    <a href="{{ route('prescriptions.print', ['uuid' => $prescription->uuid]) }}"
                        target="_blank"
                        class="dt-btn dt-btn-secondary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                            <path d="M7 9V3h10v6M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2" />
                            <path d="M7 14h10v7H7z" />
                        </svg>
                        Imprimir
                    </a>

                    <a href="{{ route('prescriptions.pdf', ['uuid' => $prescription->uuid]) }}"
                        class="dt-btn dt-btn-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                            <path d="M12 3v12M8 11l4 4 4-4M5 20h14" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        PDF
                    </a>

                    @if ($prescription->status === 'active')
                    <button
                        type="button"
                        x-data
                        x-on:click="
                                Swal.fire({
                                    title: '¿Anular receta?',
                                    text: 'La receta quedará marcada como anulada y conservará su historial.',
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonText: 'Sí, anular',
                                    cancelButtonText: 'Cancelar',
                                    confirmButtonColor: '#dc2626'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        $wire.cancelPrescription()
                                    }
                                })
                            "
                        class="dt-btn dt-btn-danger">
                        Anular
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @if ($prescription->status === 'cancelled')
    <div class="mb-6 flex gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-800">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" class="mt-0.5 h-5 w-5 shrink-0">
            <circle cx="12" cy="12" r="9" />
            <path d="M9 9l6 6M15 9l-6 6" stroke-linecap="round" />
        </svg>
        <div>
            <p class="text-sm font-bold">Esta receta fue anulada</p>
            <p class="mt-0.5 text-sm text-rose-700">Se conserva en el expediente como parte del historial clínico.</p>
        </div>
    </div>
    @endif

    {{-- DOCUMENT --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        {{-- PATIENT / DOCTOR --}}
        <div class="grid border-b border-slate-100 md:grid-cols-2 md:divide-x md:divide-slate-100">
            <div class="p-5 sm:p-6">
                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Paciente</p>
                <p class="mt-2 text-lg font-bold text-slate-950">
                    {{ $prescription->patient->first_name }}
                    {{ $prescription->patient->last_name }}
                </p>
                <p class="mt-1 text-sm text-slate-500">
                    Prescripción del {{ $prescription->prescribed_at->format('d/m/Y') }}
                </p>
            </div>

            <div class="border-t border-slate-100 p-5 sm:p-6 md:border-t-0">
                <div class="flex items-start gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4.5 w-4.5">
                            <path d="M12 21s7-3.5 7-10V5l-7-2-7 2v6c0 6.5 7 10 7 10Z" />
                            <path d="M9 11h6M12 8v6" stroke-linecap="round" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Médico</p>
                        <p class="mt-1 font-bold text-slate-900">
                            Dr. {{ $prescription->doctorProfile->first_name }}
                            {{ $prescription->doctorProfile->last_name }}
                        </p>

                        @if ($prescription->doctorProfile->professional_license)
                        <p class="mt-1 text-sm text-slate-500">
                            Cédula profesional: {{ $prescription->doctorProfile->professional_license }}
                        </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- MEDICATIONS --}}
        <section>
            <div class="flex items-center gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-4 sm:px-6">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4.5 w-4.5">
                        <path d="m8 16 8-8a4 4 0 0 1 5.7 5.7l-8 8A4 4 0 0 1 8 16Z" />
                        <path d="m10.5 13.5 4 4" stroke-linecap="round" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-slate-950">Medicamentos</h2>
                    <p class="text-xs text-slate-500">{{ $prescription->items->count() }} medicamento(s) prescritos.</p>
                </div>
            </div>

            <div>
                @foreach ($prescription->items as $item)
                <article class="border-b border-slate-100 px-5 py-5 last:border-0 sm:px-6">
                    <div class="flex items-start gap-4">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-violet-600 text-xs font-black text-white">
                            {{ $loop->iteration }}
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="text-base font-bold text-slate-950">{{ $item->medication_name }}</p>

                            @if ($item->presentation)
                            <p class="mt-1 text-sm font-medium text-slate-500">{{ $item->presentation }}</p>
                            @endif

                            @if ($item->dose || $item->frequency || $item->duration)
                            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                @if ($item->dose)
                                <div class="rounded-xl bg-blue-50/70 px-3 py-2.5">
                                    <p class="text-[10px] font-bold uppercase tracking-wide text-blue-500">Dosis</p>
                                    <p class="mt-1 text-sm font-bold text-slate-800">{{ $item->dose }}</p>
                                </div>
                                @endif

                                @if ($item->frequency)
                                <div class="rounded-xl bg-cyan-50/70 px-3 py-2.5">
                                    <p class="text-[10px] font-bold uppercase tracking-wide text-cyan-600">Frecuencia</p>
                                    <p class="mt-1 text-sm font-bold text-slate-800">{{ $item->frequency }}</p>
                                </div>
                                @endif

                                @if ($item->duration)
                                <div class="rounded-xl bg-violet-50/70 px-3 py-2.5">
                                    <p class="text-[10px] font-bold uppercase tracking-wide text-violet-500">Duración</p>
                                    <p class="mt-1 text-sm font-bold text-slate-800">{{ $item->duration }}</p>
                                </div>
                                @endif
                            </div>
                            @endif

                            @if ($item->instructions)
                            <div class="mt-4 rounded-xl border border-amber-100 bg-amber-50/60 px-4 py-3">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-amber-600">Indicaciones</p>
                                <p class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $item->instructions }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </article>
                @endforeach
            </div>
        </section>

        {{-- GENERAL INSTRUCTIONS --}}
        @if ($prescription->general_instructions)
        <section class="border-t border-slate-100 bg-emerald-50/25 px-5 py-5 sm:px-6">
            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4.5 w-4.5">
                        <path d="M6 4h12v16H6z" />
                        <path d="M9 9h6M9 13h6" stroke-linecap="round" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-slate-950">Indicaciones generales</h2>
                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">
                        {{ $prescription->general_instructions }}
                    </p>
                </div>
            </div>
        </section>
        @endif
    </div>

</div>