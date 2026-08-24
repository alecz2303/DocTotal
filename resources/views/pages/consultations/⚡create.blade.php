<?php

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\DoctorProfile;
use App\Models\Patient;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
    #[Layout('layouts::app')]
    #[Title('Nueva consulta | DocTotal')]
    class extends Component
    {
        public Patient $patient;

        public ?Appointment $appointment = null;

        public string $consultation_at = '';
        public string $reason = '';

        public string $subjective = '';
        public string $objective = '';
        public string $assessment = '';
        public string $plan = '';

        public string $weight_kg = '';
        public string $height_cm = '';
        public string $systolic_bp = '';
        public string $diastolic_bp = '';
        public string $heart_rate = '';
        public string $respiratory_rate = '';
        public string $temperature_c = '';
        public string $oxygen_saturation = '';

        public function mount(string $uuid): void
        {
            $this->patient = Patient::query()
                ->where('uuid', $uuid)
                ->firstOrFail();

            $appointmentUuid = request()->query(
                'appointment'
            );

            if ($appointmentUuid) {
                $this->appointment = Appointment::query()
                    ->where(
                        'uuid',
                        $appointmentUuid
                    )
                    ->where(
                        'patient_id',
                        $this->patient->id
                    )
                    ->firstOrFail();

                if (
                    $this->appointment->status
                    !== Appointment::STATUS_IN_PROGRESS
                ) {
                    abort(404);
                }

                $this->reason =
                    $this->appointment->reason ?? '';
            }

            $this->consultation_at = now()
                ->format('Y-m-d\TH:i');
        }

        public function saveConsultation(): void
        {
            $validated = $this->validate([
                'consultation_at' => [
                    'required',
                    'date',
                ],

                'reason' => [
                    'nullable',
                    'string',
                    'max:500',
                ],

                'subjective' => [
                    'nullable',
                    'string',
                    'max:10000',
                ],

                'objective' => [
                    'nullable',
                    'string',
                    'max:10000',
                ],

                'assessment' => [
                    'nullable',
                    'string',
                    'max:10000',
                ],

                'plan' => [
                    'nullable',
                    'string',
                    'max:10000',
                ],

                'weight_kg' => [
                    'nullable',
                    'numeric',
                    'min:0',
                    'max:999.99',
                ],

                'height_cm' => [
                    'nullable',
                    'numeric',
                    'min:0',
                    'max:999.99',
                ],

                'systolic_bp' => [
                    'nullable',
                    'integer',
                    'min:1',
                    'max:300',
                ],

                'diastolic_bp' => [
                    'nullable',
                    'integer',
                    'min:1',
                    'max:200',
                ],

                'heart_rate' => [
                    'nullable',
                    'integer',
                    'min:1',
                    'max:300',
                ],

                'respiratory_rate' => [
                    'nullable',
                    'integer',
                    'min:1',
                    'max:100',
                ],

                'temperature_c' => [
                    'nullable',
                    'numeric',
                    'min:25',
                    'max:50',
                ],

                'oxygen_saturation' => [
                    'nullable',
                    'integer',
                    'min:1',
                    'max:100',
                ],
            ]);

            $doctor = DoctorProfile::query()
                ->where('user_id', auth()->id())
                ->firstOrFail();

            $consultation = Consultation::create([
                'patient_id' => $this->patient->id,
                'doctor_profile_id' => $doctor->id,

                'appointment_id' =>
                $this->appointment?->id,

                'consultation_at' =>
                $validated['consultation_at'],

                'reason' =>
                $validated['reason'] ?: null,

                'subjective' =>
                $validated['subjective'] ?: null,

                'objective' =>
                $validated['objective'] ?: null,

                'assessment' =>
                $validated['assessment'] ?: null,

                'plan' =>
                $validated['plan'] ?: null,

                'weight_kg' =>
                $validated['weight_kg'] ?: null,

                'height_cm' =>
                $validated['height_cm'] ?: null,

                'systolic_bp' =>
                $validated['systolic_bp'] ?: null,

                'diastolic_bp' =>
                $validated['diastolic_bp'] ?: null,

                'heart_rate' =>
                $validated['heart_rate'] ?: null,

                'respiratory_rate' =>
                $validated['respiratory_rate'] ?: null,

                'temperature_c' =>
                $validated['temperature_c'] ?: null,

                'oxygen_saturation' =>
                $validated['oxygen_saturation'] ?: null,

                'status' => 'completed',
            ]);

            /*
         * Si la consulta se inició desde una cita,
         * la cita se completa automáticamente.
         */
            if ($this->appointment) {
                $this->appointment->complete();
            }

            $this->redirectRoute(
                'consultations.show',
                [
                    'uuid' => $consultation->uuid,
                ]
            );
        }
    };
?>

<div class="mx-auto max-w-5xl">

    {{-- ENCABEZADO --}}
    <div class="mb-8">

        <a
            href="{{ $appointment
                ? route('appointments.show', [
                    'uuid' => $appointment->uuid,
                ])
                : route('patients.show', [
                    'uuid' => $patient->uuid,
                ])
            }}"
            class="text-sm font-medium text-slate-500 hover:text-slate-900">
            {{ $appointment
                ? '← Volver a la cita'
                : '← Volver al expediente'
            }}
        </a>

        <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-900">
            Nueva consulta
        </h1>

        <p class="mt-1 text-sm text-slate-500">
            {{ $patient->first_name }}
            {{ $patient->last_name }}
            {{ $patient->second_last_name }}
        </p>

    </div>


    {{-- CITA ASOCIADA --}}
    @if ($appointment)

    <div
        class="mb-6 rounded-xl
                   border border-orange-200
                   bg-orange-50
                   px-5 py-4">

        <div
            class="flex flex-col gap-2
                       sm:flex-row
                       sm:items-center
                       sm:justify-between">

            <div>

                <p
                    class="text-sm font-semibold
                               text-orange-800">
                    Consulta iniciada desde una cita
                </p>

                <p
                    class="mt-1 text-sm
                               text-orange-700">
                    {{ $appointment->starts_at->format('d/m/Y H:i') }}
                    —
                    {{ $appointment->ends_at->format('H:i') }}

                    @if ($appointment->reason)
                    · {{ $appointment->reason }}
                    @endif
                </p>

            </div>

            <span
                class="inline-flex self-start
                           rounded-full
                           bg-orange-100
                           px-3 py-1
                           text-xs font-semibold
                           text-orange-700
                           sm:self-auto">
                En atención
            </span>

        </div>

    </div>

    @endif


    <form
        wire:submit="saveConsultation"
        class="space-y-6">

        {{-- DATOS DE LA CONSULTA --}}
        <div
            class="rounded-xl border
                   border-slate-200
                   bg-white shadow-sm">

            <div
                class="border-b border-slate-200
                       px-6 py-4">
                <h2 class="font-semibold text-slate-900">
                    Datos de la consulta
                </h2>
            </div>

            <div
                class="grid gap-5 p-6
                       sm:grid-cols-2">

                <div>

                    <label
                        class="mb-1 block
                               text-sm font-medium">
                        Fecha y hora *
                    </label>

                    <input
                        wire:model="consultation_at"
                        type="datetime-local"
                        class="w-full rounded-lg
                               border border-slate-300
                               px-3 py-2">

                    @error('consultation_at')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                    @enderror

                </div>

                <div>

                    <label
                        class="mb-1 block
                               text-sm font-medium">
                        Motivo de consulta
                    </label>

                    <input
                        wire:model="reason"
                        type="text"
                        placeholder="Ej. Dolor de cabeza"
                        class="w-full rounded-lg
                               border border-slate-300
                               px-3 py-2">

                </div>

            </div>

        </div>


        {{-- SIGNOS VITALES --}}
        <div
            class="rounded-xl border
                   border-slate-200
                   bg-white shadow-sm">

            <div
                class="border-b border-slate-200
                       px-6 py-4">
                <h2 class="font-semibold text-slate-900">
                    Signos vitales
                </h2>
            </div>

            <div
                class="grid gap-5 p-6
                       sm:grid-cols-2
                       lg:grid-cols-4">

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Peso (kg)
                    </label>

                    <input
                        wire:model="weight_kg"
                        type="number"
                        step="0.01"
                        class="w-full rounded-lg border
                               border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Estatura (cm)
                    </label>

                    <input
                        wire:model="height_cm"
                        type="number"
                        step="0.01"
                        class="w-full rounded-lg border
                               border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Presión sistólica
                    </label>

                    <input
                        wire:model="systolic_bp"
                        type="number"
                        class="w-full rounded-lg border
                               border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Presión diastólica
                    </label>

                    <input
                        wire:model="diastolic_bp"
                        type="number"
                        class="w-full rounded-lg border
                               border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Frecuencia cardiaca
                    </label>

                    <input
                        wire:model="heart_rate"
                        type="number"
                        class="w-full rounded-lg border
                               border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Frecuencia respiratoria
                    </label>

                    <input
                        wire:model="respiratory_rate"
                        type="number"
                        class="w-full rounded-lg border
                               border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Temperatura °C
                    </label>

                    <input
                        wire:model="temperature_c"
                        type="number"
                        step="0.1"
                        class="w-full rounded-lg border
                               border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Saturación O₂ %
                    </label>

                    <input
                        wire:model="oxygen_saturation"
                        type="number"
                        class="w-full rounded-lg border
                               border-slate-300 px-3 py-2">
                </div>

            </div>

        </div>


        {{-- NOTA CLÍNICA --}}
        <div
            class="rounded-xl border
                   border-slate-200
                   bg-white shadow-sm">

            <div
                class="border-b border-slate-200
                       px-6 py-4">
                <h2 class="font-semibold text-slate-900">
                    Nota clínica
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Estructura SOAP para documentar la consulta.
                </p>
            </div>

            <div
                class="grid gap-5 p-6
                       sm:grid-cols-2">

                <div>

                    <label class="mb-1 block text-sm font-medium">
                        Subjetivo
                    </label>

                    <textarea
                        wire:model="subjective"
                        rows="6"
                        placeholder="Síntomas, evolución, antecedentes relevantes..."
                        class="w-full rounded-lg border
                               border-slate-300 px-3 py-2"></textarea>

                </div>

                <div>

                    <label class="mb-1 block text-sm font-medium">
                        Objetivo
                    </label>

                    <textarea
                        wire:model="objective"
                        rows="6"
                        placeholder="Exploración física, hallazgos..."
                        class="w-full rounded-lg border
                               border-slate-300 px-3 py-2"></textarea>

                </div>

                <div>

                    <label class="mb-1 block text-sm font-medium">
                        Evaluación / diagnóstico
                    </label>

                    <textarea
                        wire:model="assessment"
                        rows="6"
                        placeholder="Impresión diagnóstica..."
                        class="w-full rounded-lg border
                               border-slate-300 px-3 py-2"></textarea>

                </div>

                <div>

                    <label class="mb-1 block text-sm font-medium">
                        Plan
                    </label>

                    <textarea
                        wire:model="plan"
                        rows="6"
                        placeholder="Tratamiento, estudios, seguimiento..."
                        class="w-full rounded-lg border
                               border-slate-300 px-3 py-2"></textarea>

                </div>

            </div>

        </div>


        {{-- ACCIONES --}}
        <div class="flex justify-end gap-3">

            <a
                href="{{ $appointment
                    ? route('appointments.show', [
                        'uuid' => $appointment->uuid,
                    ])
                    : route('patients.show', [
                        'uuid' => $patient->uuid,
                    ])
                }}"
                class="rounded-lg border
                       border-slate-300
                       px-4 py-2.5
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
                    wire:target="saveConsultation">
                    Guardar consulta
                </span>

                <span
                    wire:loading
                    wire:target="saveConsultation">
                    Guardando...
                </span>

            </button>

        </div>

    </form>

</div>