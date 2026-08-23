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

<div class="mx-auto max-w-4xl">

    <div class="mb-8">

        @if ($prescription->consultation)

        <a
            href="{{ route('consultations.show', [
                    'uuid' => $prescription->consultation->uuid
                ]) }}"
            class="text-sm font-medium text-slate-500
                       hover:text-slate-900">
            ← Volver a la consulta
        </a>

        @else

        <a
            href="{{ route('patients.show', [
                    'uuid' => $prescription->patient->uuid
                ]) }}"
            class="text-sm font-medium text-slate-500
                       hover:text-slate-900">
            ← Volver al expediente
        </a>

        @endif

        <div class="mt-3 flex flex-col gap-3 sm:flex-row
                    sm:items-start sm:justify-between">

            <div>

                <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                    Receta médica
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    {{ $prescription->patient->first_name }}
                    {{ $prescription->patient->last_name }}
                </p>

                <div class="mt-3 flex flex-wrap gap-2">

                    @if ($prescription->status === 'active')

                    <a
                        href="{{ route('prescriptions.edit', [
                                'uuid' => $prescription->uuid
                            ]) }}"
                        class="inline-flex items-center rounded-lg
                                border border-slate-300 px-4 py-2.5
                                text-sm font-semibold text-slate-700
                                hover:bg-slate-50">
                        Editar receta
                    </a>

                    @endif

                    <a
                        href="{{ route('prescriptions.print', [
                            'uuid' => $prescription->uuid
                        ]) }}"
                        target="_blank"
                        class="inline-flex items-center rounded-lg
                            border border-slate-300 px-4 py-2.5
                            text-sm font-semibold text-slate-700
                            hover:bg-slate-50">
                        Imprimir receta
                    </a>

                    <a
                        href="{{ route('prescriptions.pdf', [
                            'uuid' => $prescription->uuid
                        ]) }}"
                        class="inline-flex items-center rounded-lg
                            bg-slate-900 px-4 py-2.5
                            text-sm font-semibold text-white
                            hover:bg-slate-800">
                        Descargar PDF
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
                        class="inline-flex items-center rounded-lg
                                border border-red-200 px-4 py-2.5
                                text-sm font-semibold text-red-700
                                hover:bg-red-50">
                        Anular receta
                    </button>

                    @endif

                </div>

            </div>

            <div class="text-left sm:text-right">

                <p class="text-sm font-medium text-slate-900">
                    {{ $prescription->prescribed_at->format('d/m/Y') }}
                </p>

                <p class="text-sm text-slate-500">
                    {{ $prescription->prescribed_at->format('H:i') }}
                </p>

                <div class="mt-2">

                    @if ($prescription->status === 'cancelled')

                    <span
                        class="inline-flex items-center rounded-full
                                bg-red-50 px-2.5 py-1
                                text-xs font-semibold text-red-700
                                ring-1 ring-inset ring-red-200">
                        Anulada
                    </span>

                    @else

                    <span
                        class="inline-flex items-center rounded-full
                                bg-green-50 px-2.5 py-1
                                text-xs font-semibold text-green-700
                                ring-1 ring-inset ring-green-200">
                        Activa
                    </span>

                    @endif

                </div>

            </div>

        </div>

    </div>

    <div class="space-y-6">

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-6 py-4">

                <h2 class="font-semibold text-slate-900">
                    Médico
                </h2>

            </div>

            <div class="p-6">

                <p class="font-medium text-slate-900">
                    Dr.
                    {{ $prescription->doctorProfile->first_name }}
                    {{ $prescription->doctorProfile->last_name }}
                </p>

                @if ($prescription->doctorProfile->professional_license)

                <p class="mt-1 text-sm text-slate-500">
                    Cédula:
                    {{ $prescription->doctorProfile->professional_license }}
                </p>

                @endif

            </div>

        </div>

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-6 py-4">

                <h2 class="font-semibold text-slate-900">
                    Medicamentos
                </h2>

            </div>

            <div>

                @foreach ($prescription->items as $item)

                <div class="border-b border-slate-100
                                px-6 py-5 last:border-0">

                    <p class="font-semibold text-slate-900">
                        {{ $item->medication_name }}
                    </p>

                    <div class="mt-3 grid gap-3 text-sm
                                    text-slate-600 sm:grid-cols-2">

                        @if ($item->presentation)
                        <p>
                            <strong>Presentación:</strong>
                            {{ $item->presentation }}
                        </p>
                        @endif

                        @if ($item->dose)
                        <p>
                            <strong>Dosis:</strong>
                            {{ $item->dose }}
                        </p>
                        @endif

                        @if ($item->frequency)
                        <p>
                            <strong>Frecuencia:</strong>
                            {{ $item->frequency }}
                        </p>
                        @endif

                        @if ($item->duration)
                        <p>
                            <strong>Duración:</strong>
                            {{ $item->duration }}
                        </p>
                        @endif

                    </div>

                    @if ($item->instructions)

                    <p class="mt-3 whitespace-pre-line
                                      text-sm text-slate-600">
                        {{ $item->instructions }}
                    </p>

                    @endif

                </div>

                @endforeach

            </div>

        </div>

        @if ($prescription->general_instructions)

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="font-semibold text-slate-900">
                    Indicaciones generales
                </h2>
            </div>

            <div class="p-6">

                <p class="whitespace-pre-line text-sm text-slate-700">
                    {{ $prescription->general_instructions }}
                </p>

            </div>

        </div>

        @endif

    </div>

</div>