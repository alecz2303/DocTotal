<?php

use App\Actions\Patients\BuildPatientClinicalTimeline;
use App\Actions\Patients\DeleteClinicalDocument;
use App\Actions\Patients\StoreClinicalDocument;
use Illuminate\Support\Collection;
use App\Models\ClinicalDocument;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\PatientEmergencyContact;
use App\Models\PatientMedicalHistory;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new
    #[Layout('layouts::app')]
    #[Title('Expediente del paciente | DocTotal')]
    class extends Component
    {
        use WithFileUploads;

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

        public Collection $clinicalTimeline;
        public Collection $historicalDiagnoses;
        public Collection $historicalTreatments;
        public Collection $clinicalDocuments;

        public bool $showClinicalDocumentModal = false;

        public $clinical_document_file = null;

        public string $clinical_document_title = '';

        public string $clinical_document_category =
        ClinicalDocument::CATEGORY_GENERAL;

        public string $clinical_document_date = '';

        public string $clinical_document_notes = '';

        public ?int $clinical_document_consultation_id = null;

        public ?int $editingEmergencyContactId = null;

        public function mount(
            string $uuid,
            BuildPatientClinicalTimeline $buildPatientClinicalTimeline
        ): void {
            $this->patient = Patient::query()
                ->where('uuid', $uuid)
                ->firstOrFail();

            $this->clinicalTimeline =
                $buildPatientClinicalTimeline->handle(
                    $this->patient
                );

            $this->historicalDiagnoses =
                $buildPatientClinicalTimeline->diagnoses(
                    $this->patient
                );

            $this->historicalTreatments =
                $buildPatientClinicalTimeline->treatments(
                    $this->patient
                );

            $this->clinicalDocuments = $this->patient
                ->clinicalDocuments()
                ->with('consultation')
                ->orderByDesc('document_date')
                ->orderByDesc('created_at')
                ->get();
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
                    ->where(
                        'patient_id',
                        $this->patient->id
                    )
                    ->update([
                        'is_primary' => false,
                    ]);
            }

            PatientEmergencyContact::create([
                'patient_id' => $this->patient->id,
                'name' =>
                $validated['emergency_contact_name'],
                'relationship' =>
                $validated['emergency_contact_relationship'] ?: null,
                'phone' =>
                $validated['emergency_contact_phone'],
                'email' =>
                $validated['emergency_contact_email'] ?: null,
                'is_primary' =>
                $validated['emergency_contact_is_primary'],
            ]);

            $this->patient->unsetRelation(
                'emergencyContacts'
            );

            $this->showEmergencyContactModal = false;

            $this->resetEmergencyContactForm();

            session()->flash(
                'success',
                'Contacto de emergencia registrado correctamente.'
            );

            $this->redirectRoute(
                'patients.show',
                [
                    'uuid' => $this->patient->uuid,
                ]
            );
        }

        public function openEmergencyContactModal(): void
        {
            $this->editingEmergencyContactId = null;

            $this->resetEmergencyContactForm();
            $this->resetValidation();

            $this->showEmergencyContactModal = true;
        }

        public function editEmergencyContact(
            int $contactId
        ): void {
            $contact =
                PatientEmergencyContact::query()
                ->where(
                    'patient_id',
                    $this->patient->id
                )
                ->findOrFail($contactId);

            $this->editingEmergencyContactId =
                $contact->id;

            $this->emergency_contact_name =
                $contact->name;

            $this->emergency_contact_relationship =
                $contact->relationship ?? '';

            $this->emergency_contact_phone =
                $contact->phone;

            $this->emergency_contact_email =
                $contact->email ?? '';

            $this->emergency_contact_is_primary =
                $contact->is_primary;

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
            $validated =
                $this->validateEmergencyContact();

            if (
                $validated['emergency_contact_is_primary']
            ) {
                PatientEmergencyContact::query()
                    ->where(
                        'patient_id',
                        $this->patient->id
                    )
                    ->when(
                        $this->editingEmergencyContactId,
                        fn($query) =>
                        $query->where(
                            'id',
                            '!=',
                            $this
                                ->editingEmergencyContactId
                        )
                    )
                    ->update([
                        'is_primary' => false,
                    ]);
            }

            if ($this->editingEmergencyContactId) {
                $contact =
                    PatientEmergencyContact::query()
                    ->where(
                        'patient_id',
                        $this->patient->id
                    )
                    ->findOrFail(
                        $this
                            ->editingEmergencyContactId
                    );

                $contact->update([
                    'name' =>
                    $validated['emergency_contact_name'],

                    'relationship' =>
                    $validated['emergency_contact_relationship'] ?: null,

                    'phone' =>
                    $validated['emergency_contact_phone'],

                    'email' =>
                    $validated['emergency_contact_email'] ?: null,

                    'is_primary' =>
                    $validated['emergency_contact_is_primary'],
                ]);

                $message =
                    'Contacto de emergencia actualizado correctamente.';
            } else {
                PatientEmergencyContact::create([
                    'patient_id' =>
                    $this->patient->id,

                    'name' =>
                    $validated['emergency_contact_name'],

                    'relationship' =>
                    $validated['emergency_contact_relationship'] ?: null,

                    'phone' =>
                    $validated['emergency_contact_phone'],

                    'email' =>
                    $validated['emergency_contact_email'] ?: null,

                    'is_primary' =>
                    $validated['emergency_contact_is_primary'],
                ]);

                $message =
                    'Contacto de emergencia registrado correctamente.';
            }

            $this->patient->unsetRelation(
                'emergencyContacts'
            );

            $this->showEmergencyContactModal = false;

            $this->editingEmergencyContactId = null;

            $this->resetEmergencyContactForm();

            session()->flash(
                'success',
                $message
            );

            $this->redirectRoute(
                'patients.show',
                [
                    'uuid' => $this->patient->uuid,
                ]
            );
        }

        public function deleteEmergencyContact(
            int $contactId
        ): void {
            $contact =
                PatientEmergencyContact::query()
                ->where(
                    'patient_id',
                    $this->patient->id
                )
                ->findOrFail($contactId);

            $contact->delete();

            $this->patient->unsetRelation(
                'emergencyContacts'
            );

            session()->flash(
                'success',
                'Contacto de emergencia eliminado correctamente.'
            );

            $this->redirectRoute(
                'patients.show',
                [
                    'uuid' => $this->patient->uuid,
                ]
            );
        }

        public function openMedicalHistoryModal(): void
        {
            $history =
                PatientMedicalHistory::query()
                ->where(
                    'patient_id',
                    $this->patient->id
                )
                ->first();

            $this->allergies_text =
                $history?->allergies_text ?? '';

            $this->current_medications_text =
                $history?->current_medications_text ?? '';

            $this->chronic_conditions_text =
                $history?->chronic_conditions_text ?? '';

            $this->surgeries_text =
                $history?->surgeries_text ?? '';

            $this->family_history_text =
                $history?->family_history_text ?? '';

            $this->personal_history_text =
                $history?->personal_history_text ?? '';

            $this->gynecological_history_text =
                $history?->gynecological_history_text ?? '';

            $this->habits_text =
                $history?->habits_text ?? '';

            $this->other_notes =
                $history?->other_notes ?? '';

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
                'allergies_text' => [
                    'nullable',
                    'string',
                    'max:5000',
                ],
                'current_medications_text' => [
                    'nullable',
                    'string',
                    'max:5000',
                ],
                'chronic_conditions_text' => [
                    'nullable',
                    'string',
                    'max:5000',
                ],
                'surgeries_text' => [
                    'nullable',
                    'string',
                    'max:5000',
                ],
                'family_history_text' => [
                    'nullable',
                    'string',
                    'max:5000',
                ],
                'personal_history_text' => [
                    'nullable',
                    'string',
                    'max:5000',
                ],
                'gynecological_history_text' => [
                    'nullable',
                    'string',
                    'max:5000',
                ],
                'habits_text' => [
                    'nullable',
                    'string',
                    'max:5000',
                ],
                'other_notes' => [
                    'nullable',
                    'string',
                    'max:5000',
                ],
            ]);

            PatientMedicalHistory::updateOrCreate(
                [
                    'patient_id' =>
                    $this->patient->id,
                ],
                [
                    'allergies_text' =>
                    $validated['allergies_text'] ?: null,

                    'current_medications_text' =>
                    $validated['current_medications_text'] ?: null,

                    'chronic_conditions_text' =>
                    $validated['chronic_conditions_text'] ?: null,

                    'surgeries_text' =>
                    $validated['surgeries_text'] ?: null,

                    'family_history_text' =>
                    $validated['family_history_text'] ?: null,

                    'personal_history_text' =>
                    $validated['personal_history_text'] ?: null,

                    'gynecological_history_text' =>
                    $validated['gynecological_history_text'] ?: null,

                    'habits_text' =>
                    $validated['habits_text'] ?: null,

                    'other_notes' =>
                    $validated['other_notes'] ?: null,
                ]
            );

            $this->patient->unsetRelation(
                'medicalHistory'
            );

            $this->showMedicalHistoryModal = false;

            $this->resetMedicalHistoryForm();

            session()->flash(
                'success',
                'Antecedentes médicos actualizados correctamente.'
            );

            $this->redirectRoute(
                'patients.show',
                [
                    'uuid' => $this->patient->uuid,
                ]
            );
        }


        public function openClinicalDocumentModal(): void
        {
            $this->resetClinicalDocumentForm();
            $this->resetValidation();

            $this->showClinicalDocumentModal = true;
        }

        public function closeClinicalDocumentModal(): void
        {
            $this->showClinicalDocumentModal = false;

            $this->resetClinicalDocumentForm();
            $this->resetValidation();
        }

        private function resetClinicalDocumentForm(): void
        {
            $this->reset([
                'clinical_document_file',
                'clinical_document_title',
                'clinical_document_date',
                'clinical_document_notes',
                'clinical_document_consultation_id',
            ]);

            $this->clinical_document_category =
                ClinicalDocument::CATEGORY_GENERAL;
        }

        public function saveClinicalDocument(
            StoreClinicalDocument $storeClinicalDocument
        ): void {
            $validated = $this->validate([
                'clinical_document_file' => [
                    'required',
                    'file',
                    'mimes:pdf,jpg,jpeg,png,webp',
                    'max:10240',
                ],

                'clinical_document_title' => [
                    'required',
                    'string',
                    'max:190',
                ],

                'clinical_document_category' => [
                    'required',
                    'string',
                    'in:' . implode(
                        ',',
                        ClinicalDocument::categories()
                    ),
                ],

                'clinical_document_date' => [
                    'nullable',
                    'date',
                ],

                'clinical_document_notes' => [
                    'nullable',
                    'string',
                    'max:5000',
                ],

                'clinical_document_consultation_id' => [
                    'nullable',
                    'integer',
                ],
            ]);

            $consultation = null;

            if (
                $validated['clinical_document_consultation_id']
            ) {
                $consultation = $this->patient
                    ->consultations()
                    ->findOrFail(
                        $validated['clinical_document_consultation_id']
                    );
            }

            $storeClinicalDocument->handle(
                patient: $this->patient,
                file: $validated['clinical_document_file'],
                title: $validated['clinical_document_title'],
                category: $validated['clinical_document_category'],
                documentDate: $validated['clinical_document_date'] ?: null,
                notes: $validated['clinical_document_notes'] ?: null,
                consultation: $consultation,
                uploadedBy: auth()->user(),
            );

            $this->showClinicalDocumentModal = false;

            $this->resetClinicalDocumentForm();

            session()->flash(
                'success',
                'Documento clínico agregado correctamente.'
            );

            $this->redirectRoute(
                'patients.show',
                [
                    'uuid' => $this->patient->uuid,
                ]
            );
        }

        public function deleteClinicalDocument(
            string $uuid,
            DeleteClinicalDocument $deleteClinicalDocument
        ): void {
            $document = $this->patient
                ->clinicalDocuments()
                ->where('uuid', $uuid)
                ->firstOrFail();

            $deleteClinicalDocument->handle(
                $document
            );

            session()->flash(
                'success',
                'Documento clínico eliminado correctamente.'
            );

            $this->redirectRoute(
                'patients.show',
                [
                    'uuid' => $this->patient->uuid,
                ]
            );
        }
    };
?>

<div class="mx-auto max-w-7xl">

    @php
    /*
    * Draft directo:
    *
    * Solo buscamos consultas en progreso que NO
    * provengan de una cita.
    *
    * Las consultas vinculadas a Appointment se
    * continúan desde la pantalla de la cita.
    */
    $directDraft = $patient->consultations()
    ->where(
    'status',
    Consultation::STATUS_DRAFT
    )
    ->whereNull('appointment_id')
    ->latest('updated_at')
    ->first();
    @endphp

    <div
        class="mb-8 flex flex-col gap-4
               sm:flex-row sm:items-center
               sm:justify-between">

        <div>

            <a
                href="{{ route('patients.index') }}"
                class="text-sm font-medium
                       text-slate-500
                       hover:text-slate-900">
                ← Volver a pacientes
            </a>

            <h1
                class="mt-3 text-2xl
                       font-bold tracking-tight
                       text-slate-900">
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
                href="{{ route(
                    'patients.edit',
                    [
                        'uuid' => $patient->uuid,
                    ]
                ) }}"
                class="rounded-lg
                       border border-slate-300
                       px-4 py-2.5
                       text-sm font-semibold
                       text-slate-700
                       hover:bg-slate-50">
                Editar
            </a>

            @if (! $directDraft)

            <a
                href="{{ route(
                        'consultations.create',
                        [
                            'uuid' => $patient->uuid,
                        ]
                    ) }}"
                class="rounded-lg
                           bg-slate-900
                           px-4 py-2.5
                           text-sm font-semibold
                           text-white
                           hover:bg-slate-800">
                Nueva consulta
            </a>

            @endif

        </div>

    </div>


    {{-- CONSULTA DIRECTA EN PROGRESO --}}
    @if ($directDraft)

    <div
        class="mb-6 rounded-xl
                   border border-orange-200
                   bg-orange-50
                   px-5 py-4">

        <div
            class="flex flex-col gap-4
                       sm:flex-row
                       sm:items-center
                       sm:justify-between">

            <div>

                <div
                    class="flex flex-wrap
                               items-center gap-2">

                    <p
                        class="text-sm font-semibold
                                   text-orange-900">
                        Consulta en progreso
                    </p>

                    <span
                        class="rounded-full
                                   bg-orange-100
                                   px-2.5 py-0.5
                                   text-xs font-semibold
                                   text-orange-700">
                        Borrador
                    </span>

                </div>

                <p
                    class="mt-1 text-sm
                               text-orange-700">
                    {{ $directDraft
                            ->consultation_at
                            ->format('d/m/Y H:i') }}

                    @if ($directDraft->reason)
                    · {{ $directDraft->reason }}
                    @endif
                </p>

                <p
                    class="mt-1 text-xs
                               text-orange-600">
                    Esta consulta todavía no forma
                    parte del historial clínico.
                </p>

            </div>

            <a
                href="{{ route(
                        'consultations.create',
                        [
                            'uuid' => $patient->uuid,
                        ]
                    ) }}"
                class="inline-flex
                           justify-center
                           rounded-lg
                           bg-orange-600
                           px-4 py-2.5
                           text-sm font-semibold
                           text-white
                           hover:bg-orange-700">
                Continuar consulta
            </a>

        </div>

    </div>

    @endif


    <div class="grid gap-6 lg:grid-cols-3">

        {{-- Columna principal --}}
        <div class="space-y-6 lg:col-span-2">

            <div
                class="rounded-xl
                       border border-slate-200
                       bg-white shadow-sm">

                <div
                    class="border-b
                           border-slate-200
                           px-6 py-4">
                    <h2 class="font-semibold text-slate-900">
                        Datos generales
                    </h2>
                </div>

                <div
                    class="grid gap-5 p-6
                           sm:grid-cols-2">

                    <div>
                        <p
                            class="text-xs font-medium
                                   uppercase tracking-wide
                                   text-slate-400">
                            Fecha de nacimiento
                        </p>

                        <p
                            class="mt-1 text-sm
                                   font-medium
                                   text-slate-900">
                            {{ $patient->birth_date?->format('d/m/Y')
                                ?? 'No registrada'
                            }}
                        </p>
                    </div>

                    <div>
                        <p
                            class="text-xs font-medium
                                   uppercase tracking-wide
                                   text-slate-400">
                            Sexo
                        </p>

                        <p
                            class="mt-1 text-sm
                                   font-medium
                                   text-slate-900">
                            @php
                            $sexLabels = [
                            'male' => 'Masculino',
                            'female' => 'Femenino',
                            'other' => 'Otro',
                            'unspecified' =>
                            'No especificado',
                            ];
                            @endphp

                            {{ $sexLabels[$patient->sex]
                                ?? 'No registrado'
                            }}
                        </p>
                    </div>

                    <div>
                        <p
                            class="text-xs font-medium
                                   uppercase tracking-wide
                                   text-slate-400">
                            Tipo de sangre
                        </p>

                        <p
                            class="mt-1 text-sm
                                   font-medium
                                   text-slate-900">
                            {{ $patient->blood_type
                                ?: 'Desconocido'
                            }}
                        </p>
                    </div>

                    <div>
                        <p
                            class="text-xs font-medium
                                   uppercase tracking-wide
                                   text-slate-400">
                            Edad
                        </p>

                        <p
                            class="mt-1 text-sm
                                   font-medium
                                   text-slate-900">
                            {{ $patient->birth_date?->age
                                ? $patient->birth_date->age
                                    . ' años'
                                : 'No disponible'
                            }}
                        </p>
                    </div>

                </div>

            </div>


            {{-- ANTECEDENTES MÉDICOS --}}
            <div
                class="rounded-xl
                       border border-slate-200
                       bg-white shadow-sm">

                <div
                    class="flex items-center
                           justify-between
                           border-b
                           border-slate-200
                           px-6 py-4">

                    <div>
                        <h2 class="font-semibold text-slate-900">
                            Resumen clínico
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Antecedentes médicos relevantes del paciente.
                        </p>
                    </div>

                    <button
                        type="button"
                        wire:click="openMedicalHistoryModal"
                        class="text-sm font-semibold
                               text-slate-700
                               hover:text-slate-900">
                        Editar antecedentes
                    </button>

                </div>

                <div class="p-6">

                    @if ($patient->medicalHistory)

                    <div
                        class="grid gap-5
                                   sm:grid-cols-2">

                        @php
                        $historyItems = [
                        'Alergias' =>
                        $patient
                        ->medicalHistory
                        ->allergies_text,

                        'Medicamentos actuales' =>
                        $patient
                        ->medicalHistory
                        ->current_medications_text,

                        'Enfermedades crónicas' =>
                        $patient
                        ->medicalHistory
                        ->chronic_conditions_text,

                        'Cirugías' =>
                        $patient
                        ->medicalHistory
                        ->surgeries_text,

                        'Antecedentes familiares' =>
                        $patient
                        ->medicalHistory
                        ->family_history_text,

                        'Antecedentes personales' =>
                        $patient
                        ->medicalHistory
                        ->personal_history_text,

                        'Hábitos' =>
                        $patient
                        ->medicalHistory
                        ->habits_text,

                        'Notas adicionales' =>
                        $patient
                        ->medicalHistory
                        ->other_notes,
                        ];
                        @endphp

                        @foreach (
                        $historyItems
                        as $label => $value
                        )

                        <div>

                            <p
                                class="text-xs
                                               font-medium
                                               uppercase
                                               tracking-wide
                                               text-slate-400">
                                {{ $label }}
                            </p>

                            <p
                                class="mt-1
                                               whitespace-pre-line
                                               text-sm
                                               text-slate-700">
                                {{ $value
                                            ?: 'Sin registro'
                                        }}
                            </p>

                        </div>

                        @endforeach

                    </div>

                    @else

                    <div class="py-8 text-center">

                        <p
                            class="font-medium
                                       text-slate-700">
                            Sin antecedentes registrados
                        </p>

                        <p
                            class="mt-1 text-sm
                                       text-slate-500">
                            Más adelante podrás completar
                            el historial médico del paciente.
                        </p>

                    </div>

                    @endif

                </div>

                @if ($historicalDiagnoses->isNotEmpty())

                <div
                    class="border-t border-slate-200
                           px-6 py-5">

                    <p
                        class="text-xs font-semibold
                               uppercase tracking-wide
                               text-slate-400">
                        Diagnósticos históricos
                    </p>

                    <div class="mt-3 space-y-3">

                        @foreach ($historicalDiagnoses as $entry)

                        <div
                            class="flex flex-col gap-1
                                   sm:flex-row
                                   sm:items-center
                                   sm:justify-between">

                            <div>

                                <p class="text-sm text-slate-700">

                                    @if ($entry['code'])
                                    <span class="font-semibold">
                                        {{ $entry['code'] }}
                                    </span>
                                    ·
                                    @endif

                                    {{ $entry['description'] }}

                                </p>

                                <p
                                    class="mt-0.5 text-xs
                                           text-slate-400">

                                    {{ $entry['count'] }}
                                    {{ $entry['count'] === 1
                                        ? 'registro'
                                        : 'registros'
                                    }}

                                    · Último:

                                    {{ $entry['last_occurred_at']
                                        ->format('d/m/Y') }}

                                </p>

                            </div>

                            <a
                                href="{{ route(
                                    'consultations.show',
                                    [
                                        'uuid' =>
                                            $entry[
                                                'latest_consultation'
                                            ]->uuid,
                                    ]
                                ) }}"
                                class="text-xs font-semibold
                                       text-slate-600
                                       hover:text-slate-900">
                                Ver última consulta
                            </a>

                        </div>

                        @endforeach

                    </div>

                </div>

                @endif


                @if ($historicalTreatments->isNotEmpty())

                <div
                    class="border-t border-slate-200
                           px-6 py-5">

                    <p
                        class="text-xs font-semibold
                               uppercase tracking-wide
                               text-slate-400">
                        Tratamientos históricos
                    </p>

                    <div class="mt-3 space-y-3">

                        @foreach ($historicalTreatments as $entry)

                        <div
                            class="flex flex-col gap-1
                                    sm:flex-row
                                    sm:items-center
                                    sm:justify-between">

                            <div>

                                <p class="text-sm text-slate-700">

                                    <span class="font-semibold">
                                        {{ $entry['medication_name'] }}
                                    </span>

                                    @if ($entry['dose'])
                                    · {{ $entry['dose'] }}
                                    @endif

                                    @if ($entry['frequency'])
                                    · {{ $entry['frequency'] }}
                                    @endif

                                    @if ($entry['duration'])
                                    · {{ $entry['duration'] }}
                                    @endif

                                </p>

                                <p
                                    class="mt-0.5 text-xs
                                            text-slate-400">

                                    {{ $entry['count'] }}

                                    {{ $entry['count'] === 1
                                            ? 'registro'
                                            : 'registros'
                                        }}

                                    · Último:

                                    {{ $entry['last_prescribed_at']
                                            ->format('d/m/Y') }}

                                </p>

                            </div>

                            <a
                                href="{{ route(
                                        'prescriptions.show',
                                        [
                                            'uuid' =>
                                                $entry[
                                                    'latest_prescription'
                                                ]->uuid,
                                        ]
                                    ) }}"
                                class="text-xs font-semibold
                                        text-slate-600
                                        hover:text-slate-900">
                                Ver última receta
                            </a>

                        </div>

                        @endforeach

                    </div>

                </div>

                @endif

            </div>



            {{-- DOCUMENTOS CLÍNICOS --}}
            <div
                class="rounded-xl
                       border border-slate-200
                       bg-white shadow-sm">

                <div
                    class="flex items-center
                           justify-between
                           border-b
                           border-slate-200
                           px-6 py-4">

                    <div>
                        <h2 class="font-semibold text-slate-900">
                            Documentos clínicos
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Estudios, resultados, imágenes y otros
                            archivos relacionados con el paciente.
                        </p>
                    </div>

                    <button
                        type="button"
                        wire:click="openClinicalDocumentModal"
                        class="text-sm font-semibold
                               text-slate-700
                               hover:text-slate-900">
                        + Agregar documento
                    </button>

                </div>

                <div>

                    @forelse ($clinicalDocuments as $document)

                    @php
                    $categoryLabels = [
                    'general' => 'General',
                    'laboratory' => 'Laboratorio',
                    'imaging' => 'Imagen',
                    'other' => 'Otro',
                    ];

                    $isImage = str_starts_with(
                    $document->mime_type ?? '',
                    'image/'
                    );

                    $isPdf =
                    $document->mime_type === 'application/pdf';
                    @endphp

                    <div
                        class="border-b
                            border-slate-100
                            px-6 py-5
                            last:border-0">

                        <div
                            class="flex flex-col gap-4
                                sm:flex-row
                                sm:items-start">

                            {{-- MINIATURA --}}
                            <div class="shrink-0">

                                @if ($isImage)

                                <a
                                    href="{{ route(
                                            'clinical-documents.view',
                                            $document
                                        ) }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="block">

                                    <img
                                        src="{{ route(
                                                'clinical-documents.view',
                                                $document
                                            ) }}"
                                        alt="{{ $document->title }}"
                                        class="h-24 w-24
                                                rounded-xl
                                                border border-slate-200
                                                object-cover
                                                shadow-sm">

                                </a>

                                @elseif ($isPdf)

                                <a
                                    href="{{ route(
                                            'clinical-documents.view',
                                            $document
                                        ) }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="flex h-24 w-24
                                            flex-col
                                            items-center
                                            justify-center
                                            rounded-xl
                                            border border-red-100
                                            bg-red-50
                                            text-red-600
                                            transition
                                            hover:bg-red-100">

                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                        class="h-8 w-8">

                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M14 2H6a2 2 0 0 0-2 2v16
                                                a2 2 0 0 0 2 2h12
                                                a2 2 0 0 0 2-2V8z" />

                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M14 2v6h6" />

                                    </svg>

                                    <span
                                        class="mt-1 text-xs
                                                font-bold">
                                        PDF
                                    </span>

                                </a>

                                @else

                                <div
                                    class="flex h-24 w-24
                                            flex-col
                                            items-center
                                            justify-center
                                            rounded-xl
                                            border border-slate-200
                                            bg-slate-50
                                            text-slate-500">

                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                        class="h-8 w-8">

                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M14 2H6a2 2 0 0 0-2 2v16
                                                a2 2 0 0 0 2 2h12
                                                a2 2 0 0 0 2-2V8z" />

                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M14 2v6h6" />

                                    </svg>

                                    <span
                                        class="mt-1 text-xs
                                                font-semibold">
                                        Archivo
                                    </span>

                                </div>

                                @endif

                            </div>


                            {{-- INFORMACIÓN --}}
                            <div class="min-w-0 flex-1">

                                <div
                                    class="flex flex-col gap-3
                                        sm:flex-row
                                        sm:items-start
                                        sm:justify-between">

                                    <div class="min-w-0">

                                        <div
                                            class="flex flex-wrap
                                                items-center gap-2">

                                            <p
                                                class="font-medium
                                                    text-slate-900">
                                                {{ $document->title }}
                                            </p>

                                            <span
                                                class="rounded-full
                                                    bg-slate-100
                                                    px-2 py-0.5
                                                    text-xs font-medium
                                                    text-slate-600">
                                                {{
                                                    $categoryLabels[
                                                        $document->category
                                                    ] ?? 'General'
                                                }}
                                            </span>

                                        </div>


                                        <p
                                            class="mt-1 truncate
                                                text-sm
                                                text-slate-500"
                                            title="{{ $document->original_name }}">
                                            {{ $document->original_name }}
                                        </p>


                                        <div
                                            class="mt-2 flex flex-wrap
                                                gap-x-4 gap-y-1
                                                text-xs
                                                text-slate-400">

                                            <span>
                                                Fecha:
                                                {{
                                                    $document
                                                        ->document_date
                                                        ?->format('d/m/Y')
                                                    ?? $document
                                                        ->created_at
                                                        ->format('d/m/Y')
                                                }}
                                            </span>

                                            <span>
                                                @if (
                                                $document->size_bytes
                                                >= 1024 * 1024
                                                )

                                                {{
                                                        number_format(
                                                            $document->size_bytes
                                                                / 1024
                                                                / 1024,
                                                            1
                                                        )
                                                    }}
                                                MB

                                                @else

                                                {{
                                                        number_format(
                                                            $document->size_bytes
                                                                / 1024,
                                                            0
                                                        )
                                                    }}
                                                KB

                                                @endif
                                            </span>

                                        </div>


                                        @if ($document->consultation)

                                        <div class="mt-2">

                                            <a
                                                href="{{ route(
                                                        'consultations.show',
                                                        [
                                                            'uuid' =>
                                                                $document
                                                                    ->consultation
                                                                    ->uuid,
                                                        ]
                                                    ) }}"
                                                class="text-xs
                                                        font-semibold
                                                        text-slate-500
                                                        hover:text-slate-900">
                                                Ver consulta relacionada
                                            </a>

                                        </div>

                                        @endif


                                        @if ($document->notes)

                                        <p
                                            class="mt-3
                                                    whitespace-pre-line
                                                    text-sm
                                                    text-slate-600">
                                            {{ $document->notes }}
                                        </p>

                                        @endif

                                    </div>


                                    {{-- ACCIONES --}}
                                    <div
                                        class="flex shrink-0
                                            flex-wrap
                                            items-center
                                            gap-3">

                                        <a
                                            href="{{ route(
                                                'clinical-documents.view',
                                                $document
                                            ) }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="text-sm
                                                font-semibold
                                                text-slate-700
                                                hover:text-slate-900">
                                            Ver
                                        </a>

                                        <a
                                            href="{{ route(
                                                'clinical-documents.download',
                                                $document
                                            ) }}"
                                            class="text-sm
                                                font-semibold
                                                text-slate-700
                                                hover:text-slate-900">
                                            Descargar
                                        </a>

                                        <button
                                            type="button"
                                            x-data
                                            x-on:click="
                                                Swal.fire({
                                                    title: '¿Eliminar documento?',
                                                    text: 'El archivo será eliminado permanentemente del expediente.',
                                                    icon: 'warning',
                                                    showCancelButton: true,
                                                    confirmButtonText: 'Sí, eliminar',
                                                    cancelButtonText: 'Cancelar'
                                                }).then((result) => {
                                                    if (result.isConfirmed) {
                                                        $wire.deleteClinicalDocument(
                                                            '{{ $document->uuid }}'
                                                        )
                                                    }
                                                })
                                            "
                                            class="text-sm
                                                font-semibold
                                                text-red-600
                                                hover:text-red-700">
                                            Eliminar
                                        </button>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                    @empty

                    <div class="px-6 py-10 text-center">

                        <p
                            class="font-medium
                                       text-slate-700">
                            Sin documentos clínicos
                        </p>

                        <p
                            class="mt-1 text-sm
                                       text-slate-500">
                            Los estudios, resultados y archivos
                            del paciente aparecerán aquí.
                        </p>

                    </div>

                    @endforelse

                </div>

            </div>


            {{-- HISTORIA CLÍNICA --}}
            <div
                class="rounded-xl
                    border border-slate-200
                    bg-white shadow-sm">

                <div
                    class="flex items-center
                        justify-between
                        border-b
                        border-slate-200
                        px-6 py-4">

                    <div>

                        <h2
                            class="font-semibold
                                text-slate-900">
                            Historia clínica
                        </h2>

                        <p
                            class="mt-1 text-sm
                                text-slate-500">
                            Línea de tiempo clínica del paciente.
                        </p>

                    </div>

                    @if (! $directDraft)

                    <a
                        href="{{ route(
                                'consultations.create',
                                [
                                    'uuid' =>
                                        $patient->uuid,
                                ]
                            ) }}"
                        class="text-sm font-semibold
                            text-slate-700
                            hover:text-slate-900">
                        + Nueva consulta
                    </a>

                    @endif

                </div>

                <div>

                    @forelse ($clinicalTimeline as $event)

                    @if ($event['type'] === 'consultation')

                    @php
                    $consultation =
                    $event['consultation'];
                    @endphp

                    <div
                        class="border-b
                                    border-slate-100
                                    px-6 py-5
                                    last:border-0">

                        <div
                            class="flex flex-col gap-4
                                        sm:flex-row
                                        sm:items-start
                                        sm:justify-between">

                            <div class="min-w-0">

                                <div
                                    class="flex flex-wrap
                                                items-center
                                                gap-2">

                                    <p
                                        class="font-medium
                                                    text-slate-900">
                                        {{ $event['occurred_at']
                                                    ->format('d/m/Y H:i') }}
                                    </p>

                                    <span
                                        class="rounded-full
                                                    bg-emerald-50
                                                    px-2 py-0.5
                                                    text-xs font-medium
                                                    text-emerald-700">
                                        Consulta
                                    </span>

                                </div>

                                <p
                                    class="mt-1 text-sm
                                                text-slate-600">
                                    {{ $consultation->reason
                                                ?: 'Sin motivo registrado'
                                            }}
                                </p>


                                {{-- DIAGNÓSTICOS --}}
                                @if ($consultation->diagnoses->isNotEmpty())

                                <div class="mt-3">

                                    <p
                                        class="text-xs font-semibold
                                                        uppercase tracking-wide
                                                        text-slate-400">
                                        Diagnósticos
                                    </p>

                                    <div
                                        class="mt-1
                                                        space-y-1">

                                        @foreach (
                                        $consultation->diagnoses
                                        as $diagnosis
                                        )

                                        <p
                                            class="text-sm
                                                                text-slate-700">

                                            @if ($diagnosis->code)
                                            <span
                                                class="font-medium">
                                                {{ $diagnosis->code }}
                                            </span>
                                            ·
                                            @endif

                                            {{ $diagnosis->description }}

                                            @if ($diagnosis->is_primary)
                                            <span
                                                class="text-xs
                                                                        font-medium
                                                                        text-slate-500">
                                                (Principal)
                                            </span>
                                            @endif

                                        </p>

                                        @endforeach

                                    </div>

                                </div>

                                @endif


                                {{-- SIGNOS VITALES --}}
                                <div
                                    class="mt-3 flex
                                                flex-wrap
                                                gap-x-4 gap-y-1
                                                text-xs
                                                text-slate-500">

                                    @if (
                                    $consultation->systolic_bp
                                    && $consultation->diastolic_bp
                                    )
                                    <span>
                                        PA:
                                        {{ $consultation->systolic_bp }}/{{ $consultation->diastolic_bp }}
                                    </span>
                                    @endif

                                    @if ($consultation->heart_rate)
                                    <span>
                                        FC:
                                        {{ $consultation->heart_rate }}
                                        lpm
                                    </span>
                                    @endif

                                    @if ($consultation->temperature_c)
                                    <span>
                                        Temp:
                                        {{ $consultation->temperature_c }}
                                        °C
                                    </span>
                                    @endif

                                    @if ($consultation->oxygen_saturation)
                                    <span>
                                        SatO₂:
                                        {{ $consultation->oxygen_saturation }}%
                                    </span>
                                    @endif

                                </div>


                                {{-- RECETAS DE LA CONSULTA --}}
                                @if ($consultation->prescriptions->isNotEmpty())

                                <div class="mt-4">

                                    <p
                                        class="text-xs font-semibold
                                                        uppercase tracking-wide
                                                        text-slate-400">
                                        Tratamiento
                                    </p>

                                    <div
                                        class="mt-2 space-y-2">

                                        @foreach (
                                        $consultation->prescriptions
                                        as $prescription
                                        )

                                        <div
                                            class="rounded-lg
                                                                bg-slate-50
                                                                px-3 py-2">

                                            @forelse (
                                            $prescription->items
                                            as $item
                                            )

                                            <p
                                                class="text-sm
                                                                        text-slate-700">
                                                <span
                                                    class="font-medium">
                                                    {{ $item->medication_name }}
                                                </span>

                                                @if ($item->dose)
                                                · {{ $item->dose }}
                                                @endif

                                                @if ($item->frequency)
                                                · {{ $item->frequency }}
                                                @endif

                                                @if ($item->duration)
                                                · {{ $item->duration }}
                                                @endif
                                            </p>

                                            @empty

                                            <p
                                                class="text-sm
                                                                        text-slate-500">
                                                Receta sin medicamentos registrados.
                                            </p>

                                            @endforelse

                                            <a
                                                href="{{ route(
                                                                    'prescriptions.show',
                                                                    [
                                                                        'uuid' =>
                                                                            $prescription->uuid,
                                                                    ]
                                                                ) }}"
                                                class="mt-1
                                                                    inline-block
                                                                    text-xs
                                                                    font-semibold
                                                                    text-slate-600
                                                                    hover:text-slate-900">
                                                Ver receta
                                            </a>

                                        </div>

                                        @endforeach

                                    </div>

                                </div>

                                @endif

                            </div>

                            <div class="shrink-0">

                                <a
                                    href="{{ route(
                                                'consultations.show',
                                                [
                                                    'uuid' =>
                                                        $consultation->uuid,
                                                ]
                                            ) }}"
                                    class="text-sm
                                                font-semibold
                                                text-slate-700
                                                hover:text-slate-900">
                                    Ver consulta
                                </a>

                            </div>

                        </div>

                    </div>


                    @elseif ($event['type'] === 'prescription')

                    @php
                    $prescription =
                    $event['prescription'];
                    @endphp

                    <div
                        class="border-b
                                    border-slate-100
                                    px-6 py-5
                                    last:border-0">

                        <div
                            class="flex flex-col gap-4
                                        sm:flex-row
                                        sm:items-start
                                        sm:justify-between">

                            <div>

                                <div
                                    class="flex flex-wrap
                                                items-center
                                                gap-2">

                                    <p
                                        class="font-medium
                                                    text-slate-900">
                                        {{ $event['occurred_at']
                                                    ->format('d/m/Y H:i') }}
                                    </p>

                                    <span
                                        class="rounded-full
                                                    bg-blue-50
                                                    px-2 py-0.5
                                                    text-xs font-medium
                                                    text-blue-700">
                                        Receta
                                    </span>

                                </div>

                                <p
                                    class="mt-1 text-sm
                                                text-slate-600">
                                    Receta emitida fuera de una consulta.
                                </p>

                                <div
                                    class="mt-3 space-y-1">

                                    @forelse (
                                    $prescription->items
                                    as $item
                                    )

                                    <p
                                        class="text-sm
                                                        text-slate-700">
                                        <span
                                            class="font-medium">
                                            {{ $item->medication_name }}
                                        </span>

                                        @if ($item->dose)
                                        · {{ $item->dose }}
                                        @endif

                                        @if ($item->frequency)
                                        · {{ $item->frequency }}
                                        @endif

                                        @if ($item->duration)
                                        · {{ $item->duration }}
                                        @endif
                                    </p>

                                    @empty

                                    <p
                                        class="text-sm
                                                        text-slate-500">
                                        Sin medicamentos registrados.
                                    </p>

                                    @endforelse

                                </div>

                            </div>

                            <div class="shrink-0">

                                <a
                                    href="{{ route(
                                                'prescriptions.show',
                                                [
                                                    'uuid' =>
                                                        $prescription->uuid,
                                                ]
                                            ) }}"
                                    class="text-sm
                                                font-semibold
                                                text-slate-700
                                                hover:text-slate-900">
                                    Ver receta
                                </a>

                            </div>

                        </div>

                    </div>

                    @endif

                    @empty

                    <div
                        class="px-6 py-10
                                text-center">

                        <p
                            class="font-medium
                                    text-slate-700">
                            Sin actividad clínica registrada
                        </p>

                        <p
                            class="mt-1 text-sm
                                    text-slate-500">
                            Las consultas finalizadas y las recetas
                            independientes aparecerán aquí.
                        </p>

                    </div>

                    @endforelse

                </div>

            </div>

        </div>


        {{-- Columna lateral --}}
        <div class="space-y-6">

            <div
                class="rounded-xl
                       border border-slate-200
                       bg-white shadow-sm">

                <div
                    class="border-b
                           border-slate-200
                           px-6 py-4">
                    <h2 class="font-semibold text-slate-900">
                        Contacto
                    </h2>
                </div>

                <div class="space-y-4 p-6">

                    <div>
                        <p
                            class="text-xs font-medium
                                   uppercase tracking-wide
                                   text-slate-400">
                            Teléfono
                        </p>

                        <p
                            class="mt-1 text-sm
                                   font-medium
                                   text-slate-900">
                            {{ $patient->phone
                                ?: 'No registrado'
                            }}
                        </p>
                    </div>

                    <div>
                        <p
                            class="text-xs font-medium
                                   uppercase tracking-wide
                                   text-slate-400">
                            WhatsApp
                        </p>

                        <p
                            class="mt-1 text-sm
                                   font-medium
                                   text-slate-900">
                            {{ $patient->whatsapp
                                ?: 'No registrado'
                            }}
                        </p>
                    </div>

                    <div>
                        <p
                            class="text-xs font-medium
                                   uppercase tracking-wide
                                   text-slate-400">
                            Correo
                        </p>

                        <p
                            class="mt-1 break-all
                                   text-sm font-medium
                                   text-slate-900">
                            {{ $patient->email
                                ?: 'No registrado'
                            }}
                        </p>
                    </div>

                </div>

            </div>


            {{-- CONTACTOS DE EMERGENCIA --}}
            <div
                class="rounded-xl
                       border border-slate-200
                       bg-white shadow-sm">

                <div
                    class="flex items-center
                           justify-between
                           border-b
                           border-slate-200
                           px-6 py-4">

                    <h2 class="font-semibold text-slate-900">
                        Contactos de emergencia
                    </h2>

                    <button
                        type="button"
                        wire:click="openEmergencyContactModal"
                        class="text-sm font-semibold
                               text-slate-700
                               hover:text-slate-900">
                        + Agregar
                    </button>

                </div>

                <div class="p-6">

                    @forelse (
                    $patient->emergencyContacts
                    as $contact
                    )

                    <div
                        class="border-b
                                   border-slate-100
                                   py-4 first:pt-0
                                   last:border-0
                                   last:pb-0">

                        <div
                            class="flex items-start
                                       justify-between
                                       gap-3">

                            <div>

                                <div
                                    class="flex
                                               items-center
                                               gap-2">

                                    <p
                                        class="font-medium
                                                   text-slate-900">
                                        {{ $contact->name }}
                                    </p>

                                    @if ($contact->is_primary)

                                    <span
                                        class="rounded-full
                                                       bg-slate-100
                                                       px-2 py-0.5
                                                       text-xs
                                                       font-medium
                                                       text-slate-600">
                                        Principal
                                    </span>

                                    @endif

                                </div>

                                <p
                                    class="mt-1 text-sm
                                               text-slate-500">
                                    {{ $contact->relationship
                                            ?: 'Contacto'
                                        }}
                                </p>

                                <p
                                    class="mt-2 text-sm
                                               text-slate-700">
                                    {{ $contact->phone }}
                                </p>

                                @if ($contact->email)

                                <p
                                    class="mt-1 text-sm
                                                   text-slate-500">
                                    {{ $contact->email }}
                                </p>

                                @endif

                            </div>

                            <div
                                class="flex items-center
                                           gap-2">

                                <button
                                    type="button"
                                    wire:click="editEmergencyContact({{ $contact->id }})"
                                    class="text-xs
                                               font-semibold
                                               text-slate-600
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
                                    class="text-xs
                                               font-semibold
                                               text-red-600
                                               hover:text-red-700">
                                    Eliminar
                                </button>

                            </div>

                        </div>

                    </div>

                    @empty

                    <div class="py-4 text-center">

                        <p
                            class="text-sm
                                       font-medium
                                       text-slate-700">
                            Sin contactos de emergencia
                        </p>

                        <p
                            class="mt-1 text-sm
                                       text-slate-500">
                            Agrega una persona a quien
                            contactar en caso necesario.
                        </p>

                    </div>

                    @endforelse

                </div>

            </div>

        </div>

    </div>


    {{-- MODAL CONTACTO DE EMERGENCIA --}}
    @if ($showEmergencyContactModal)

    <div
        class="fixed inset-0 z-50
                   flex items-center
                   justify-center
                   bg-slate-950/50 p-4">

        <div
            class="w-full max-w-lg
                       rounded-2xl bg-white
                       shadow-xl">

            <div
                class="flex items-center
                           justify-between
                           border-b
                           border-slate-200
                           px-6 py-4">

                <div>

                    <h2
                        class="text-lg font-semibold
                                   text-slate-900">
                        {{ $editingEmergencyContactId
                                ? 'Editar contacto de emergencia'
                                : 'Nuevo contacto de emergencia'
                            }}
                    </h2>

                    <p
                        class="mt-1 text-sm
                                   text-slate-500">
                        {{ $editingEmergencyContactId
                                ? 'Actualiza los datos del contacto.'
                                : 'Agrega una persona de contacto para este paciente.'
                            }}
                    </p>

                </div>

                <button
                    type="button"
                    wire:click="closeEmergencyContactModal"
                    class="text-2xl
                               leading-none
                               text-slate-400
                               hover:text-slate-700">
                    ×
                </button>

            </div>

            <form wire:submit="saveEmergencyContact">

                <div class="space-y-5 p-6">

                    <div>

                        <label
                            class="mb-1 block
                                       text-sm font-medium">
                            Nombre *
                        </label>

                        <input
                            wire:model="emergency_contact_name"
                            type="text"
                            class="w-full rounded-lg
                                       border border-slate-300
                                       px-3 py-2">

                        @error('emergency_contact_name')
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
                                       text-sm font-medium">
                            Parentesco / relación
                        </label>

                        <input
                            wire:model="emergency_contact_relationship"
                            type="text"
                            placeholder="Ej. Esposa, hermano, hijo"
                            class="w-full rounded-lg
                                       border border-slate-300
                                       px-3 py-2">

                    </div>

                    <div>

                        <label
                            class="mb-1 block
                                       text-sm font-medium">
                            Teléfono *
                        </label>

                        <input
                            wire:model="emergency_contact_phone"
                            type="text"
                            class="w-full rounded-lg
                                       border border-slate-300
                                       px-3 py-2">

                        @error('emergency_contact_phone')
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
                                       text-sm font-medium">
                            Correo electrónico
                        </label>

                        <input
                            wire:model="emergency_contact_email"
                            type="email"
                            class="w-full rounded-lg
                                       border border-slate-300
                                       px-3 py-2">

                        @error('emergency_contact_email')
                        <p
                            class="mt-1 text-sm
                                           text-red-600">
                            {{ $message }}
                        </p>
                        @enderror

                    </div>

                    <label
                        class="flex items-center
                                   gap-3">

                        <input
                            wire:model="emergency_contact_is_primary"
                            type="checkbox">

                        <span
                            class="text-sm
                                       font-medium
                                       text-slate-700">
                            Marcar como contacto principal
                        </span>

                    </label>

                </div>

                <div
                    class="flex justify-end
                               gap-3 border-t
                               border-slate-200
                               px-6 py-4">

                    <button
                        type="button"
                        wire:click="closeEmergencyContactModal"
                        class="rounded-lg
                                   border border-slate-300
                                   px-4 py-2
                                   text-sm font-medium">
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="rounded-lg
                                   bg-slate-900
                                   px-4 py-2
                                   text-sm font-semibold
                                   text-white
                                   disabled:opacity-50">

                        <span
                            wire:loading.remove
                            wire:target="saveEmergencyContact">
                            {{ $editingEmergencyContactId
                                    ? 'Guardar cambios'
                                    : 'Guardar contacto'
                                }}
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


    {{-- MODAL ANTECEDENTES MÉDICOS --}}
    @if ($showMedicalHistoryModal)

    <div
        class="fixed inset-0 z-50
                   flex items-center
                   justify-center
                   bg-slate-950/50 p-4">

        <div
            class="max-h-[90vh]
                       w-full max-w-3xl
                       overflow-y-auto
                       rounded-2xl bg-white
                       shadow-xl">

            <div
                class="flex items-center
                           justify-between
                           border-b
                           border-slate-200
                           px-6 py-4">

                <div>

                    <h2
                        class="text-lg font-semibold
                                   text-slate-900">
                        Antecedentes médicos
                    </h2>

                    <p
                        class="mt-1 text-sm
                                   text-slate-500">
                        Registra o actualiza la
                        información clínica básica
                        del paciente.
                    </p>

                </div>

                <button
                    type="button"
                    wire:click="closeMedicalHistoryModal"
                    class="text-2xl
                               leading-none
                               text-slate-400
                               hover:text-slate-700">
                    ×
                </button>

            </div>

            <form wire:submit="saveMedicalHistory">

                <div
                    class="grid gap-5 p-6
                               sm:grid-cols-2">

                    @php
                    $medicalFields = [
                    'allergies_text' =>
                    'Alergias',

                    'current_medications_text' =>
                    'Medicamentos actuales',

                    'chronic_conditions_text' =>
                    'Enfermedades crónicas',

                    'surgeries_text' =>
                    'Cirugías',

                    'family_history_text' =>
                    'Antecedentes familiares',

                    'personal_history_text' =>
                    'Antecedentes personales',

                    'gynecological_history_text' =>
                    'Antecedentes ginecológicos',

                    'habits_text' =>
                    'Hábitos',
                    ];
                    @endphp

                    @foreach (
                    $medicalFields
                    as $field => $label
                    )

                    <div>

                        <label
                            class="mb-1 block
                                           text-sm font-medium">
                            {{ $label }}
                        </label>

                        <textarea
                            wire:model="{{ $field }}"
                            rows="4"
                            class="w-full
                                           rounded-lg
                                           border
                                           border-slate-300
                                           px-3 py-2"></textarea>

                    </div>

                    @endforeach

                    <div class="sm:col-span-2">

                        <label
                            class="mb-1 block
                                       text-sm font-medium">
                            Notas adicionales
                        </label>

                        <textarea
                            wire:model="other_notes"
                            rows="4"
                            class="w-full
                                       rounded-lg
                                       border
                                       border-slate-300
                                       px-3 py-2"></textarea>

                    </div>

                </div>

                <div
                    class="flex justify-end
                               gap-3 border-t
                               border-slate-200
                               px-6 py-4">

                    <button
                        type="button"
                        wire:click="closeMedicalHistoryModal"
                        class="rounded-lg
                                   border border-slate-300
                                   px-4 py-2
                                   text-sm font-medium">
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="rounded-lg
                                   bg-slate-900
                                   px-4 py-2
                                   text-sm font-semibold
                                   text-white
                                   disabled:opacity-50">

                        <span
                            wire:loading.remove
                            wire:target="saveMedicalHistory">
                            Guardar antecedentes
                        </span>

                        <span
                            wire:loading
                            wire:target="saveMedicalHistory">
                            Guardando...
                        </span>

                    </button>

                </div>

            </form>

        </div>

    </div>

    @endif


    {{-- MODAL DOCUMENTO CLÍNICO --}}
    @if ($showClinicalDocumentModal)

    <div
        class="fixed inset-0 z-50
               flex items-center
               justify-center
               bg-slate-950/50 p-4">

        <div
            class="max-h-[90vh]
                   w-full max-w-2xl
                   overflow-y-auto
                   rounded-2xl bg-white
                   shadow-xl">

            <div
                class="flex items-center
                       justify-between
                       border-b
                       border-slate-200
                       px-6 py-4">

                <div>

                    <h2
                        class="text-lg font-semibold
                               text-slate-900">
                        Agregar documento clínico
                    </h2>

                    <p
                        class="mt-1 text-sm
                               text-slate-500">
                        Adjunta un estudio, resultado,
                        imagen u otro documento relacionado
                        con el paciente.
                    </p>

                </div>

                <button
                    type="button"
                    wire:click="closeClinicalDocumentModal"
                    class="text-2xl
                           leading-none
                           text-slate-400
                           hover:text-slate-700">
                    ×
                </button>

            </div>

            <form wire:submit="saveClinicalDocument">

                <div class="space-y-5 p-6">

                    <div>

                        <label
                            class="mb-1 block
                                   text-sm font-medium
                                   text-slate-700">
                            Archivo *
                        </label>

                        <input
                            wire:model="clinical_document_file"
                            type="file"
                            accept=".pdf,.jpg,.jpeg,.png,.webp"
                            class="block w-full
                                   rounded-lg
                                   border border-slate-300
                                   bg-white
                                   px-3 py-2
                                   text-sm
                                   text-slate-700">

                        <p
                            class="mt-1 text-xs
                                   text-slate-400">
                            PDF, JPG, JPEG, PNG o WebP.
                            Máximo 10 MB.
                        </p>

                        @error('clinical_document_file')
                        <p
                            class="mt-1 text-sm
                                   text-red-600">
                            {{ $message }}
                        </p>
                        @enderror

                        <div
                            wire:loading
                            wire:target="clinical_document_file"
                            class="mt-2 text-sm
                                   text-slate-500">
                            Procesando archivo...
                        </div>

                    </div>

                    <div>

                        <label
                            class="mb-1 block
                                   text-sm font-medium
                                   text-slate-700">
                            Título *
                        </label>

                        <input
                            wire:model="clinical_document_title"
                            type="text"
                            maxlength="190"
                            placeholder="Ej. Biometría hemática"
                            class="w-full rounded-lg
                                   border border-slate-300
                                   px-3 py-2">

                        @error('clinical_document_title')
                        <p
                            class="mt-1 text-sm
                                   text-red-600">
                            {{ $message }}
                        </p>
                        @enderror

                    </div>

                    <div
                        class="grid gap-5
                               sm:grid-cols-2">

                        <div>

                            <label
                                class="mb-1 block
                                       text-sm font-medium
                                       text-slate-700">
                                Categoría *
                            </label>

                            <select
                                wire:model="clinical_document_category"
                                class="w-full rounded-lg
                                       border border-slate-300
                                       bg-white
                                       px-3 py-2">

                                <option value="general">
                                    General
                                </option>

                                <option value="laboratory">
                                    Laboratorio
                                </option>

                                <option value="imaging">
                                    Imagen
                                </option>

                                <option value="other">
                                    Otro
                                </option>

                            </select>

                            @error('clinical_document_category')
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
                                Fecha del documento
                            </label>

                            <input
                                wire:model="clinical_document_date"
                                type="date"
                                class="w-full rounded-lg
                                       border border-slate-300
                                       px-3 py-2">

                            @error('clinical_document_date')
                            <p
                                class="mt-1 text-sm
                                       text-red-600">
                                {{ $message }}
                            </p>
                            @enderror

                        </div>

                    </div>

                    <div>

                        <label
                            class="mb-1 block
                                   text-sm font-medium
                                   text-slate-700">
                            Consulta relacionada
                        </label>

                        <select
                            wire:model="clinical_document_consultation_id"
                            class="w-full rounded-lg
                                   border border-slate-300
                                   bg-white
                                   px-3 py-2">

                            <option value="">
                                Sin consulta relacionada
                            </option>

                            @foreach (
                            $patient
                            ->consultations()
                            ->latest('consultation_at')
                            ->get()
                            as $consultation
                            )

                            <option value="{{ $consultation->id }}">
                                {{
                                    $consultation
                                        ->consultation_at
                                        ->format('d/m/Y H:i')
                                }}
                                @if ($consultation->reason)
                                — {{ $consultation->reason }}
                                @endif
                            </option>

                            @endforeach

                        </select>

                        <p
                            class="mt-1 text-xs
                                   text-slate-400">
                            Opcional. Permite relacionar el archivo
                            con una consulta específica del paciente.
                        </p>

                        @error('clinical_document_consultation_id')
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
                            Notas
                        </label>

                        <textarea
                            wire:model="clinical_document_notes"
                            rows="4"
                            maxlength="5000"
                            placeholder="Información adicional sobre el documento..."
                            class="w-full rounded-lg
                                   border border-slate-300
                                   px-3 py-2"></textarea>

                        @error('clinical_document_notes')
                        <p
                            class="mt-1 text-sm
                                   text-red-600">
                            {{ $message }}
                        </p>
                        @enderror

                    </div>

                </div>

                <div
                    class="flex justify-end
                           gap-3 border-t
                           border-slate-200
                           px-6 py-4">

                    <button
                        type="button"
                        wire:click="closeClinicalDocumentModal"
                        wire:loading.attr="disabled"
                        class="rounded-lg
                               border border-slate-300
                               px-4 py-2
                               text-sm font-medium
                               text-slate-700
                               hover:bg-slate-50
                               disabled:opacity-50">
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="saveClinicalDocument,clinical_document_file"
                        class="rounded-lg
                               bg-slate-900
                               px-4 py-2
                               text-sm font-semibold
                               text-white
                               hover:bg-slate-800
                               disabled:opacity-50">

                        <span
                            wire:loading.remove
                            wire:target="saveClinicalDocument">
                            Guardar documento
                        </span>

                        <span
                            wire:loading
                            wire:target="saveClinicalDocument">
                            Guardando...
                        </span>

                    </button>

                </div>

            </form>

        </div>

    </div>

    @endif

</div>