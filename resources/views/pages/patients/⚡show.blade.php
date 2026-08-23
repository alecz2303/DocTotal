<?php

use App\Models\Patient;
use App\Models\PatientEmergencyContact;
use App\Models\PatientMedicalHistory;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
    #[Layout('layouts::app')]
    #[Title('Expediente del paciente | DocTotal')]
    class extends Component
    {
        public Patient $patient;

        public bool $showEmergencyContactModal = false;

        public string $emergency_contact_name = '';
        public string $emergency_contact_relationship = '';
        public string $emergency_contact_phone = '';
        public string $emergency_contact_email = '';
        public bool $emergency_contact_is_primary = false;

        public bool $showMedicalHistoryModal = false;

        public string $allergies_text = '';
        public string $current_medications_text = '';
        public string $chronic_conditions_text = '';
        public string $surgeries_text = '';
        public string $family_history_text = '';
        public string $personal_history_text = '';
        public string $gynecological_history_text = '';
        public string $habits_text = '';
        public string $other_notes = '';

        public ?int $editingEmergencyContactId = null;

        public function mount(string $uuid): void
        {
            $this->patient = Patient::query()
                ->where('uuid', $uuid)
                ->firstOrFail();
        }

        public function createEmergencyContact(): void
        {
            $validated = $this->validate([
                'emergency_contact_name' => [
                    'required',
                    'string',
                    'max:150',
                ],
                'emergency_contact_relationship' => [
                    'nullable',
                    'string',
                    'max:100',
                ],
                'emergency_contact_phone' => [
                    'required',
                    'string',
                    'max:30',
                ],
                'emergency_contact_email' => [
                    'nullable',
                    'email',
                    'max:190',
                ],
                'emergency_contact_is_primary' => [
                    'boolean',
                ],
            ]);

            if ($validated['emergency_contact_is_primary']) {
                PatientEmergencyContact::query()
                    ->where('patient_id', $this->patient->id)
                    ->update([
                        'is_primary' => false,
                    ]);
            }

            PatientEmergencyContact::create([
                'patient_id' => $this->patient->id,
                'name' => $validated['emergency_contact_name'],
                'relationship' =>
                $validated['emergency_contact_relationship'] ?: null,
                'phone' => $validated['emergency_contact_phone'],
                'email' => $validated['emergency_contact_email'] ?: null,
                'is_primary' =>
                $validated['emergency_contact_is_primary'],
            ]);

            $this->patient->unsetRelation('emergencyContacts');

            $this->showEmergencyContactModal = false;

            $this->resetEmergencyContactForm();

            session()->flash(
                'success',
                'Contacto de emergencia registrado correctamente.'
            );

            $this->redirectRoute(
                'patients.show',
                ['uuid' => $this->patient->uuid]
            );
        }

        public function openEmergencyContactModal(): void
        {
            $this->editingEmergencyContactId = null;

            $this->resetEmergencyContactForm();
            $this->resetValidation();

            $this->showEmergencyContactModal = true;
        }

        public function editEmergencyContact(int $contactId): void
        {
            $contact = PatientEmergencyContact::query()
                ->where('patient_id', $this->patient->id)
                ->findOrFail($contactId);

            $this->editingEmergencyContactId = $contact->id;

            $this->emergency_contact_name = $contact->name;
            $this->emergency_contact_relationship = $contact->relationship ?? '';
            $this->emergency_contact_phone = $contact->phone;
            $this->emergency_contact_email = $contact->email ?? '';
            $this->emergency_contact_is_primary = $contact->is_primary;

            $this->resetValidation();

            $this->showEmergencyContactModal = true;
        }

        public function closeEmergencyContactModal(): void
        {
            $this->showEmergencyContactModal = false;

            $this->editingEmergencyContactId = null;

            $this->resetEmergencyContactForm();
            $this->resetValidation();
        }

        private function resetEmergencyContactForm(): void
        {
            $this->reset([
                'emergency_contact_name',
                'emergency_contact_relationship',
                'emergency_contact_phone',
                'emergency_contact_email',
                'emergency_contact_is_primary',
            ]);
        }

        private function validateEmergencyContact(): array
        {
            return $this->validate([
                'emergency_contact_name' => [
                    'required',
                    'string',
                    'max:150',
                ],

                'emergency_contact_relationship' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'emergency_contact_phone' => [
                    'required',
                    'string',
                    'max:30',
                ],

                'emergency_contact_email' => [
                    'nullable',
                    'email',
                    'max:190',
                ],

                'emergency_contact_is_primary' => [
                    'boolean',
                ],
            ]);
        }

        public function saveEmergencyContact(): void
        {
            $validated = $this->validateEmergencyContact();

            if ($validated['emergency_contact_is_primary']) {
                PatientEmergencyContact::query()
                    ->where('patient_id', $this->patient->id)
                    ->when(
                        $this->editingEmergencyContactId,
                        fn($query) => $query->where(
                            'id',
                            '!=',
                            $this->editingEmergencyContactId
                        )
                    )
                    ->update([
                        'is_primary' => false,
                    ]);
            }

            if ($this->editingEmergencyContactId) {
                $contact = PatientEmergencyContact::query()
                    ->where('patient_id', $this->patient->id)
                    ->findOrFail($this->editingEmergencyContactId);

                $contact->update([
                    'name' => $validated['emergency_contact_name'],

                    'relationship' =>
                    $validated['emergency_contact_relationship'] ?: null,

                    'phone' => $validated['emergency_contact_phone'],

                    'email' =>
                    $validated['emergency_contact_email'] ?: null,

                    'is_primary' =>
                    $validated['emergency_contact_is_primary'],
                ]);

                $message = 'Contacto de emergencia actualizado correctamente.';
            } else {
                PatientEmergencyContact::create([
                    'patient_id' => $this->patient->id,

                    'name' => $validated['emergency_contact_name'],

                    'relationship' =>
                    $validated['emergency_contact_relationship'] ?: null,

                    'phone' => $validated['emergency_contact_phone'],

                    'email' =>
                    $validated['emergency_contact_email'] ?: null,

                    'is_primary' =>
                    $validated['emergency_contact_is_primary'],
                ]);

                $message = 'Contacto de emergencia registrado correctamente.';
            }

            $this->patient->unsetRelation('emergencyContacts');

            $this->showEmergencyContactModal = false;

            $this->editingEmergencyContactId = null;

            $this->resetEmergencyContactForm();

            session()->flash(
                'success',
                $message
            );

            $this->redirectRoute(
                'patients.show',
                ['uuid' => $this->patient->uuid]
            );
        }

        public function deleteEmergencyContact(int $contactId): void
        {
            $contact = PatientEmergencyContact::query()
                ->where('patient_id', $this->patient->id)
                ->findOrFail($contactId);

            $contact->delete();

            $this->patient->unsetRelation('emergencyContacts');

            session()->flash(
                'success',
                'Contacto de emergencia eliminado correctamente.'
            );

            $this->redirectRoute(
                'patients.show',
                ['uuid' => $this->patient->uuid]
            );
        }

        public function openMedicalHistoryModal(): void
        {
            $history = PatientMedicalHistory::query()
                ->where('patient_id', $this->patient->id)
                ->first();

            $this->allergies_text = $history?->allergies_text ?? '';
            $this->current_medications_text = $history?->current_medications_text ?? '';
            $this->chronic_conditions_text = $history?->chronic_conditions_text ?? '';
            $this->surgeries_text = $history?->surgeries_text ?? '';
            $this->family_history_text = $history?->family_history_text ?? '';
            $this->personal_history_text = $history?->personal_history_text ?? '';
            $this->gynecological_history_text = $history?->gynecological_history_text ?? '';
            $this->habits_text = $history?->habits_text ?? '';
            $this->other_notes = $history?->other_notes ?? '';

            $this->resetValidation();

            $this->showMedicalHistoryModal = true;
        }

        public function closeMedicalHistoryModal(): void
        {
            $this->showMedicalHistoryModal = false;

            $this->resetMedicalHistoryForm();
            $this->resetValidation();
        }

        private function resetMedicalHistoryForm(): void
        {
            $this->reset([
                'allergies_text',
                'current_medications_text',
                'chronic_conditions_text',
                'surgeries_text',
                'family_history_text',
                'personal_history_text',
                'gynecological_history_text',
                'habits_text',
                'other_notes',
            ]);
        }

        public function saveMedicalHistory(): void
        {
            $validated = $this->validate([
                'allergies_text' => ['nullable', 'string', 'max:5000'],
                'current_medications_text' => ['nullable', 'string', 'max:5000'],
                'chronic_conditions_text' => ['nullable', 'string', 'max:5000'],
                'surgeries_text' => ['nullable', 'string', 'max:5000'],
                'family_history_text' => ['nullable', 'string', 'max:5000'],
                'personal_history_text' => ['nullable', 'string', 'max:5000'],
                'gynecological_history_text' => ['nullable', 'string', 'max:5000'],
                'habits_text' => ['nullable', 'string', 'max:5000'],
                'other_notes' => ['nullable', 'string', 'max:5000'],
            ]);

            PatientMedicalHistory::updateOrCreate(
                [
                    'patient_id' => $this->patient->id,
                ],
                [
                    'allergies_text' => $validated['allergies_text'] ?: null,
                    'current_medications_text' => $validated['current_medications_text'] ?: null,
                    'chronic_conditions_text' => $validated['chronic_conditions_text'] ?: null,
                    'surgeries_text' => $validated['surgeries_text'] ?: null,
                    'family_history_text' => $validated['family_history_text'] ?: null,
                    'personal_history_text' => $validated['personal_history_text'] ?: null,
                    'gynecological_history_text' => $validated['gynecological_history_text'] ?: null,
                    'habits_text' => $validated['habits_text'] ?: null,
                    'other_notes' => $validated['other_notes'] ?: null,
                ]
            );

            $this->patient->unsetRelation('medicalHistory');

            $this->showMedicalHistoryModal = false;

            $this->resetMedicalHistoryForm();

            session()->flash(
                'success',
                'Antecedentes médicos actualizados correctamente.'
            );

            $this->redirectRoute(
                'patients.show',
                ['uuid' => $this->patient->uuid]
            );
        }
    };
?>

<div class="mx-auto max-w-7xl">

    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <a
                href="{{ route('patients.index') }}"
                class="text-sm font-medium text-slate-500 hover:text-slate-900">
                ← Volver a pacientes
            </a>

            <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-900">
                {{ $patient->first_name }}
                {{ $patient->last_name }}
                {{ $patient->second_last_name }}
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Expediente del paciente
            </p>
        </div>

        <div class="flex gap-3">

            <a
                href="{{ route('patients.edit', ['uuid' => $patient->uuid]) }}"
                class="rounded-lg border border-slate-300 px-4 py-2.5
                    text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Editar
            </a>

            <a
                href="{{ route('consultations.create', ['uuid' => $patient->uuid]) }}"
                class="rounded-lg bg-slate-900 px-4 py-2.5
                    text-sm font-semibold text-white hover:bg-slate-800">
                Nueva consulta
            </a>

        </div>

    </div>

    <div class="grid gap-6 lg:grid-cols-3">

        {{-- Columna principal --}}
        <div class="space-y-6 lg:col-span-2">

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-6 py-4">
                    <h2 class="font-semibold text-slate-900">
                        Datos generales
                    </h2>
                </div>

                <div class="grid gap-5 p-6 sm:grid-cols-2">

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                            Fecha de nacimiento
                        </p>

                        <p class="mt-1 text-sm font-medium text-slate-900">
                            {{ $patient->birth_date?->format('d/m/Y') ?? 'No registrada' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                            Sexo
                        </p>

                        <p class="mt-1 text-sm font-medium text-slate-900">
                            @php
                            $sexLabels = [
                            'male' => 'Masculino',
                            'female' => 'Femenino',
                            'other' => 'Otro',
                            'unspecified' => 'No especificado',
                            ];
                            @endphp

                            {{ $sexLabels[$patient->sex] ?? 'No registrado' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                            Tipo de sangre
                        </p>

                        <p class="mt-1 text-sm font-medium text-slate-900">
                            {{ $patient->blood_type ?: 'Desconocido' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                            Edad
                        </p>

                        <p class="mt-1 text-sm font-medium text-slate-900">
                            {{ $patient->birth_date?->age
                                ? $patient->birth_date->age . ' años'
                                : 'No disponible' }}
                        </p>
                    </div>

                </div>

            </div>

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">

                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">

                    <h2 class="font-semibold text-slate-900">
                        Antecedentes médicos
                    </h2>

                    <button
                        type="button"
                        wire:click="openMedicalHistoryModal"
                        class="text-sm font-semibold text-slate-700 hover:text-slate-900">
                        Editar antecedentes
                    </button>

                </div>

                <div class="p-6">

                    @if ($patient->medicalHistory)

                    <div class="grid gap-5 sm:grid-cols-2">

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                Alergias
                            </p>

                            <p class="mt-1 whitespace-pre-line text-sm text-slate-700">
                                {{ $patient->medicalHistory->allergies_text ?: 'Sin registro' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                Medicamentos actuales
                            </p>

                            <p class="mt-1 whitespace-pre-line text-sm text-slate-700">
                                {{ $patient->medicalHistory->current_medications_text ?: 'Sin registro' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                Enfermedades crónicas
                            </p>

                            <p class="mt-1 whitespace-pre-line text-sm text-slate-700">
                                {{ $patient->medicalHistory->chronic_conditions_text ?: 'Sin registro' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                Cirugías
                            </p>

                            <p class="mt-1 whitespace-pre-line text-sm text-slate-700">
                                {{ $patient->medicalHistory->surgeries_text ?: 'Sin registro' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                Antecedentes familiares
                            </p>

                            <p class="mt-1 whitespace-pre-line text-sm text-slate-700">
                                {{ $patient->medicalHistory->family_history_text ?: 'Sin registro' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                Antecedentes personales
                            </p>

                            <p class="mt-1 whitespace-pre-line text-sm text-slate-700">
                                {{ $patient->medicalHistory->personal_history_text ?: 'Sin registro' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                Hábitos
                            </p>

                            <p class="mt-1 whitespace-pre-line text-sm text-slate-700">
                                {{ $patient->medicalHistory->habits_text ?: 'Sin registro' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                Notas adicionales
                            </p>

                            <p class="mt-1 whitespace-pre-line text-sm text-slate-700">
                                {{ $patient->medicalHistory->other_notes ?: 'Sin registro' }}
                            </p>
                        </div>

                    </div>

                    @else

                    <div class="py-8 text-center">
                        <p class="font-medium text-slate-700">
                            Sin antecedentes registrados
                        </p>

                        <p class="mt-1 text-sm text-slate-500">
                            Más adelante podrás completar el historial médico del paciente.
                        </p>
                    </div>

                    @endif

                </div>

            </div>

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">

                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">

                    <div>
                        <h2 class="font-semibold text-slate-900">
                            Historial de consultas
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Consultas registradas para este paciente.
                        </p>
                    </div>

                    <a
                        href="{{ route('consultations.create', ['uuid' => $patient->uuid]) }}"
                        class="text-sm font-semibold text-slate-700 hover:text-slate-900">
                        + Nueva consulta
                    </a>

                </div>

                <div>

                    @forelse (
                    $patient->consultations()
                    ->latest('consultation_at')
                    ->get()
                    as $consultation
                    )

                    <div
                        class="flex flex-col gap-3 border-b border-slate-100
                                px-6 py-5 last:border-0 sm:flex-row
                                sm:items-center sm:justify-between">

                        <div>

                            <div class="flex flex-wrap items-center gap-2">

                                <p class="font-medium text-slate-900">
                                    {{ $consultation->consultation_at->format('d/m/Y H:i') }}
                                </p>

                                <span
                                    class="rounded-full bg-slate-100 px-2 py-0.5
                                            text-xs font-medium text-slate-600">
                                    {{ $consultation->status }}
                                </span>

                            </div>

                            <p class="mt-1 text-sm text-slate-600">
                                {{ $consultation->reason ?: 'Sin motivo registrado' }}
                            </p>

                            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">

                                @if ($consultation->systolic_bp && $consultation->diastolic_bp)
                                <span>
                                    PA:
                                    {{ $consultation->systolic_bp }}/{{ $consultation->diastolic_bp }}
                                </span>
                                @endif

                                @if ($consultation->heart_rate)
                                <span>
                                    FC:
                                    {{ $consultation->heart_rate }} lpm
                                </span>
                                @endif

                                @if ($consultation->temperature_c)
                                <span>
                                    Temp:
                                    {{ $consultation->temperature_c }} °C
                                </span>
                                @endif

                                @if ($consultation->oxygen_saturation)
                                <span>
                                    SatO₂:
                                    {{ $consultation->oxygen_saturation }}%
                                </span>
                                @endif

                            </div>

                        </div>

                        <div>

                            <a
                                href="{{ route('consultations.show', [
                                        'uuid' => $consultation->uuid
                                    ]) }}"
                                class="text-sm font-semibold text-slate-700
                                        hover:text-slate-900">
                                Ver consulta
                            </a>

                        </div>

                    </div>

                    @empty

                    <div class="px-6 py-10 text-center">

                        <p class="font-medium text-slate-700">
                            Sin consultas registradas
                        </p>

                        <p class="mt-1 text-sm text-slate-500">
                            Las consultas del paciente aparecerán aquí.
                        </p>

                    </div>

                    @endforelse

                </div>

            </div>

        </div>

        {{-- Columna lateral --}}
        <div class="space-y-6">

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-6 py-4">
                    <h2 class="font-semibold text-slate-900">
                        Contacto
                    </h2>
                </div>

                <div class="space-y-4 p-6">

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                            Teléfono
                        </p>

                        <p class="mt-1 text-sm font-medium text-slate-900">
                            {{ $patient->phone ?: 'No registrado' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                            WhatsApp
                        </p>

                        <p class="mt-1 text-sm font-medium text-slate-900">
                            {{ $patient->whatsapp ?: 'No registrado' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                            Correo
                        </p>

                        <p class="mt-1 break-all text-sm font-medium text-slate-900">
                            {{ $patient->email ?: 'No registrado' }}
                        </p>
                    </div>

                </div>

            </div>

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">

                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">

                    <h2 class="font-semibold text-slate-900">
                        Contactos de emergencia
                    </h2>

                    <button
                        type="button"
                        wire:click="openEmergencyContactModal"
                        class="text-sm font-semibold text-slate-700 hover:text-slate-900">
                        + Agregar
                    </button>

                </div>

                <div class="p-6">

                    @forelse ($patient->emergencyContacts as $contact)

                    <div
                        class="border-b border-slate-100 py-4 first:pt-0
                                last:border-0 last:pb-0">

                        <div class="flex items-start justify-between gap-3">

                            <div>

                                <div class="flex items-center gap-2">

                                    <p class="font-medium text-slate-900">
                                        {{ $contact->name }}
                                    </p>

                                    @if ($contact->is_primary)
                                    <span
                                        class="rounded-full bg-slate-100 px-2 py-0.5
                                                text-xs font-medium text-slate-600">
                                        Principal
                                    </span>
                                    @endif

                                </div>

                                <p class="mt-1 text-sm text-slate-500">
                                    {{ $contact->relationship ?: 'Contacto' }}
                                </p>

                                <p class="mt-2 text-sm text-slate-700">
                                    {{ $contact->phone }}
                                </p>

                                @if ($contact->email)
                                <p class="mt-1 text-sm text-slate-500">
                                    {{ $contact->email }}
                                </p>
                                @endif

                            </div>

                            <div class="flex items-center gap-2">

                                <button
                                    type="button"
                                    wire:click="editEmergencyContact({{ $contact->id }})"
                                    class="text-xs font-semibold text-slate-600
                                        hover:text-slate-900">
                                    Editar
                                </button>

                                <button
                                    type="button"
                                    x-data
                                    x-on:click="
                                        Swal.fire({
                                            title: '¿Eliminar contacto?',
                                            text: 'Esta acción no se puede deshacer.',
                                            icon: 'warning',
                                            showCancelButton: true,
                                            confirmButtonText: 'Sí, eliminar',
                                            cancelButtonText: 'Cancelar'
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                $wire.deleteEmergencyContact({{ $contact->id }})
                                            }
                                        })
                                    "
                                    class="text-xs font-semibold text-red-600
                                        hover:text-red-700">
                                    Eliminar
                                </button>

                            </div>

                        </div>

                    </div>

                    @empty

                    <div class="py-4 text-center">

                        <p class="text-sm font-medium text-slate-700">
                            Sin contactos de emergencia
                        </p>

                        <p class="mt-1 text-sm text-slate-500">
                            Agrega una persona a quien contactar en caso necesario.
                        </p>

                    </div>

                    @endforelse

                </div>

            </div>

        </div>

    </div>

    @if ($showEmergencyContactModal)

    <div
        class="fixed inset-0 z-50 flex items-center justify-center
                bg-slate-950/50 p-4">

        <div
            class="w-full max-w-lg rounded-2xl bg-white shadow-xl">

            <div
                class="flex items-center justify-between
                        border-b border-slate-200 px-6 py-4">

                <div>
                    <h2 class="text-lg font-semibold text-slate-900">
                        {{ $editingEmergencyContactId
                            ? 'Editar contacto de emergencia'
                            : 'Nuevo contacto de emergencia' }}
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        {{ $editingEmergencyContactId
                            ? 'Actualiza los datos del contacto.'
                            : 'Agrega una persona de contacto para este paciente.' }}
                    </p>
                </div>

                <button
                    type="button"
                    wire:click="closeEmergencyContactModal"
                    class="text-2xl leading-none text-slate-400
                            hover:text-slate-700">
                    ×
                </button>

            </div>

            <form wire:submit="saveEmergencyContact">

                <div class="space-y-5 p-6">

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Nombre *
                        </label>

                        <input
                            wire:model="emergency_contact_name"
                            type="text"
                            class="w-full rounded-lg border
                                    border-slate-300 px-3 py-2">

                        @error('emergency_contact_name')
                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Parentesco / relación
                        </label>

                        <input
                            wire:model="emergency_contact_relationship"
                            type="text"
                            placeholder="Ej. Esposa, hermano, hijo"
                            class="w-full rounded-lg border
                                    border-slate-300 px-3 py-2">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Teléfono *
                        </label>

                        <input
                            wire:model="emergency_contact_phone"
                            type="text"
                            class="w-full rounded-lg border
                                    border-slate-300 px-3 py-2">

                        @error('emergency_contact_phone')
                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Correo electrónico
                        </label>

                        <input
                            wire:model="emergency_contact_email"
                            type="email"
                            class="w-full rounded-lg border
                                    border-slate-300 px-3 py-2">

                        @error('emergency_contact_email')
                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>
                        @enderror
                    </div>

                    <label class="flex items-center gap-3">

                        <input
                            wire:model="emergency_contact_is_primary"
                            type="checkbox">

                        <span class="text-sm font-medium text-slate-700">
                            Marcar como contacto principal
                        </span>

                    </label>

                </div>

                <div
                    class="flex justify-end gap-3 border-t
                            border-slate-200 px-6 py-4">

                    <button
                        type="button"
                        wire:click="closeEmergencyContactModal"
                        class="rounded-lg border border-slate-300
                                px-4 py-2 text-sm font-medium">
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="rounded-lg bg-slate-900 px-4 py-2
                                text-sm font-semibold text-white
                                disabled:opacity-50">
                        <span
                            wire:loading.remove
                            wire:target="saveEmergencyContact">
                            {{ $editingEmergencyContactId
                                ? 'Guardar cambios'
                                : 'Guardar contacto' }}
                        </span>

                        <span
                            wire:loading
                            wire:target="saveEmergencyContact">
                            Guardando...
                        </span>
                    </button>

                </div>

            </form>

        </div>

    </div>

    @endif

    @if ($showMedicalHistoryModal)

    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">

        <div class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white shadow-xl">

            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">

                <div>
                    <h2 class="text-lg font-semibold text-slate-900">
                        Antecedentes médicos
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Registra o actualiza la información clínica básica del paciente.
                    </p>
                </div>

                <button
                    type="button"
                    wire:click="closeMedicalHistoryModal"
                    class="text-2xl leading-none text-slate-400 hover:text-slate-700">
                    ×
                </button>

            </div>

            <form wire:submit="saveMedicalHistory">

                <div class="grid gap-5 p-6 sm:grid-cols-2">

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Alergias
                        </label>

                        <textarea
                            wire:model="allergies_text"
                            rows="4"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Medicamentos actuales
                        </label>

                        <textarea
                            wire:model="current_medications_text"
                            rows="4"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Enfermedades crónicas
                        </label>

                        <textarea
                            wire:model="chronic_conditions_text"
                            rows="4"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Cirugías
                        </label>

                        <textarea
                            wire:model="surgeries_text"
                            rows="4"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Antecedentes familiares
                        </label>

                        <textarea
                            wire:model="family_history_text"
                            rows="4"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Antecedentes personales
                        </label>

                        <textarea
                            wire:model="personal_history_text"
                            rows="4"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Antecedentes ginecológicos
                        </label>

                        <textarea
                            wire:model="gynecological_history_text"
                            rows="4"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Hábitos
                        </label>

                        <textarea
                            wire:model="habits_text"
                            rows="4"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium">
                            Notas adicionales
                        </label>

                        <textarea
                            wire:model="other_notes"
                            rows="4"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                    </div>

                </div>

                <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">

                    <button
                        type="button"
                        wire:click="closeMedicalHistoryModal"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium">
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">
                        <span wire:loading.remove wire:target="saveMedicalHistory">
                            Guardar antecedentes
                        </span>

                        <span wire:loading wire:target="saveMedicalHistory">
                            Guardando...
                        </span>
                    </button>

                </div>

            </form>

        </div>

    </div>

    @endif

</div>