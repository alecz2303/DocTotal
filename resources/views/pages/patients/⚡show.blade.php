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

        public int $historicalDiagnosesPage = 1;
        public int $historicalTreatmentsPage = 1;
        public int $clinicalDocumentsPage = 1;
        public int $clinicalTimelinePage = 1;

        public bool $showClinicalDocumentModal = false;

        public $clinical_document_file = null;

        public string $clinical_document_title = '';

        public string $clinical_document_category =
        ClinicalDocument::CATEGORY_GENERAL;

        public string $clinical_document_date = '';

        public string $clinical_document_notes = '';

        public ?int $clinical_document_consultation_id = null;

        public ?int $editingEmergencyContactId = null;

        private function collectionPageCount(
            Collection $collection,
            int $perPage
        ): int {
            return max(
                1,
                (int) ceil($collection->count() / $perPage)
            );
        }

        public function previousHistoricalDiagnosesPage(): void
        {
            $this->historicalDiagnosesPage = max(
                1,
                $this->historicalDiagnosesPage - 1
            );
        }

        public function nextHistoricalDiagnosesPage(): void
        {
            $this->historicalDiagnosesPage = min(
                $this->collectionPageCount(
                    $this->historicalDiagnoses,
                    4
                ),
                $this->historicalDiagnosesPage + 1
            );
        }

        public function previousHistoricalTreatmentsPage(): void
        {
            $this->historicalTreatmentsPage = max(
                1,
                $this->historicalTreatmentsPage - 1
            );
        }

        public function nextHistoricalTreatmentsPage(): void
        {
            $this->historicalTreatmentsPage = min(
                $this->collectionPageCount(
                    $this->historicalTreatments,
                    4
                ),
                $this->historicalTreatmentsPage + 1
            );
        }

        public function previousClinicalDocumentsPage(): void
        {
            $this->clinicalDocumentsPage = max(
                1,
                $this->clinicalDocumentsPage - 1
            );
        }

        public function nextClinicalDocumentsPage(): void
        {
            $this->clinicalDocumentsPage = min(
                $this->collectionPageCount(
                    $this->clinicalDocuments,
                    4
                ),
                $this->clinicalDocumentsPage + 1
            );
        }

        public function previousClinicalTimelinePage(): void
        {
            $this->clinicalTimelinePage = max(
                1,
                $this->clinicalTimelinePage - 1
            );
        }

        public function nextClinicalTimelinePage(): void
        {
            $this->clinicalTimelinePage = min(
                $this->collectionPageCount(
                    $this->clinicalTimeline,
                    5
                ),
                $this->clinicalTimelinePage + 1
            );
        }

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

    $sexLabels = [
    'male' => 'Masculino',
    'female' => 'Femenino',
    'other' => 'Otro',
    'unspecified' => 'No especificado',
    ];

    $categoryLabels = [
    'general' => 'General',
    'laboratory' => 'Laboratorio',
    'imaging' => 'Imagen',
    'other' => 'Otro',
    ];

    $history = $patient->medicalHistory;
    @endphp

    {{-- VOLVER --}}
    <a
        href="{{ route('patients.index') }}"
        class="mb-5 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-blue-600">

        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
            class="h-4 w-4">
            <path
                d="M19 12H5M12 19l-7-7 7-7"
                stroke-linecap="round"
                stroke-linejoin="round" />
        </svg>

        Volver a pacientes
    </a>

    {{-- HERO DEL PACIENTE --}}
    <section
        class="relative overflow-hidden rounded-[1.75rem]
               border border-slate-200/90
               bg-gradient-to-br from-white via-white to-blue-50/70
               shadow-doctotal-lg">

        {{-- Decorative waves --}}
        <div
            class="pointer-events-none absolute inset-y-0 right-0 w-[42%]
                   bg-[radial-gradient(circle_at_top_right,rgba(124,58,237,0.10),transparent_55%)]">
        </div>

        <div
            class="pointer-events-none absolute right-[-4rem] top-[-4rem]
                   h-56 w-56 rounded-full border border-white/70">
        </div>

        <div
            class="pointer-events-none absolute right-[-2rem] top-[-1rem]
                   h-44 w-44 rounded-full border border-white/50">
        </div>

        <div class="relative p-5 sm:p-6 lg:p-7">

            {{-- Identity row --}}
            <div
                class="flex flex-col gap-5
                       lg:flex-row lg:items-center lg:justify-between">

                <div class="flex min-w-0 items-center gap-4 sm:gap-5">

                    <div
                        class="flex h-20 w-20 shrink-0 items-center justify-center
                               rounded-[1.5rem]
                               bg-gradient-to-br from-blue-600 via-indigo-600 to-violet-600
                               text-3xl font-bold text-white
                               shadow-[0_14px_30px_rgba(79,70,229,0.28)]">

                        {{ strtoupper(substr($patient->first_name ?? 'P', 0, 1)) }}

                    </div>

                    <div class="min-w-0">

                        <span
                            class="inline-flex items-center gap-2 rounded-full
                                   border border-violet-200/80 bg-violet-50/80
                                   px-3 py-1.5 text-xs font-bold text-violet-700">

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                class="h-4 w-4">
                                <path
                                    d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"
                                    stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>

                            Expediente del paciente

                        </span>

                        <h1
                            class="mt-3 text-2xl font-bold tracking-tight
                                   text-slate-950 sm:text-3xl">

                            {{ $patient->first_name }}
                            {{ $patient->last_name }}
                            {{ $patient->second_last_name }}

                        </h1>

                    </div>

                </div>

                <a
                    href="{{ route('patients.edit', ['uuid' => $patient->uuid]) }}"
                    class="dt-btn dt-btn-secondary self-start
                           rounded-2xl px-5 py-3
                           shadow-doctotal-md lg:self-auto">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        class="h-4 w-4">
                        <path d="M12 20h9" />
                        <path
                            d="M16.5 3.5
                               a2.12 2.12 0 0 1 3 3
                               L7 19l-4 1 1-4Z" />
                    </svg>

                    Editar
                </a>

            </div>

            {{-- Patient metadata --}}
            <div
                class="mt-6 grid gap-px overflow-hidden
                       rounded-2xl border border-slate-200/80
                       bg-slate-200/80 shadow-doctotal-md
                       sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">

                @if ($patient->birth_date)
                <div class="bg-white px-4 py-4">
                    <div class="flex items-start gap-3">

                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center
                                       rounded-xl bg-blue-50 text-blue-600">
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                class="h-4.5 w-4.5">
                                <rect x="4" y="5" width="16" height="15" rx="2" />
                                <path d="M8 3v4M16 3v4M4 10h16" />
                            </svg>
                        </div>

                        <div>
                            <p class="text-sm font-bold text-slate-900">
                                {{ $patient->birth_date->age }} años
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                Edad
                            </p>
                        </div>

                    </div>
                </div>
                @endif

                <div class="bg-white px-4 py-4">
                    <div class="flex items-start gap-3">

                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center
                                   rounded-xl bg-violet-50 text-violet-600">
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                class="h-4.5 w-4.5">
                                <circle cx="12" cy="8" r="4" />
                                <path d="M5 21a7 7 0 0 1 14 0" />
                            </svg>
                        </div>

                        <div>
                            <p class="text-sm font-bold text-slate-900">
                                {{ $sexLabels[$patient->sex] ?? 'No registrado' }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                Sexo
                            </p>
                        </div>

                    </div>
                </div>

                <div class="bg-white px-4 py-4">
                    <div class="flex items-start gap-3">

                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center
                                   rounded-xl bg-cyan-50 text-cyan-600">
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                class="h-4.5 w-4.5">
                                <rect x="4" y="5" width="16" height="15" rx="2" />
                                <path d="M8 3v4M16 3v4M4 10h16" />
                            </svg>
                        </div>

                        <div>
                            <p class="text-sm font-bold text-slate-900">
                                {{ $patient->birth_date?->format('d/m/Y') ?? 'No registrada' }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                Fecha de nacimiento
                            </p>
                        </div>

                    </div>
                </div>

                <div class="bg-white px-4 py-4">
                    <div class="flex items-start gap-3">

                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center
                                   rounded-xl bg-rose-50 text-rose-600">
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                class="h-4.5 w-4.5">
                                <path
                                    d="M12 2s5 5.2 5 10a5 5 0 0 1-10 0c0-4.8 5-10 5-10Z"
                                    stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </div>

                        <div>
                            <p class="text-sm font-bold text-slate-900">
                                {{ $patient->blood_type ?: 'Desconocido' }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                Tipo de sangre
                            </p>
                        </div>

                    </div>
                </div>

                <div class="bg-white px-4 py-4">
                    <div class="flex items-start gap-3">

                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center
                                   rounded-xl bg-blue-50 text-blue-600">
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                class="h-4.5 w-4.5">
                                <path
                                    d="M22 16.92v3a2 2 0 0 1-2.18 2
                                       19.79 19.79 0 0 1-8.63-3.07
                                       19.5 19.5 0 0 1-6-6
                                       19.79 19.79 0 0 1-3.07-8.67
                                       A2 2 0 0 1 4.11 2h3
                                       a2 2 0 0 1 2 1.72
                                       12.84 12.84 0 0 0 .7 2.81
                                       2 2 0 0 1-.45 2.11L8.09 9.91
                                       a16 16 0 0 0 6 6l1.27-1.27
                                       a2 2 0 0 1 2.11-.45
                                       12.84 12.84 0 0 0 2.81.7
                                       A2 2 0 0 1 22 16.92Z" />
                            </svg>
                        </div>

                        <div>
                            <p class="text-sm font-bold text-slate-900">
                                {{ $patient->phone ?: 'No registrado' }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                Teléfono
                            </p>
                        </div>

                    </div>
                </div>

                <div class="bg-white px-4 py-4">
                    <div class="flex items-start gap-3">

                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center
                                   rounded-xl bg-emerald-50 text-emerald-600">
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                class="h-4.5 w-4.5">
                                <path
                                    d="M20 11.5a8.5 8.5 0 1 1-15.9 4.2L3 21l5.45-1.02A8.5 8.5 0 0 1 20 11.5Z"
                                    stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </div>

                        <div>
                            <p class="text-sm font-bold text-emerald-600">
                                {{ $patient->whatsapp ?: 'No registrado' }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                WhatsApp
                            </p>
                        </div>

                    </div>
                </div>

                <div class="bg-white px-4 py-4 sm:col-span-2 lg:col-span-2 xl:col-span-1">
                    <div class="flex items-start gap-3">

                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center
                                   rounded-xl bg-indigo-50 text-indigo-600">
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                class="h-4.5 w-4.5">
                                <rect x="3" y="5" width="18" height="14" rx="2" />
                                <path d="m3 7 9 6 9-6" />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-slate-900">
                                {{ $patient->email ?: 'No registrado' }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                Correo electrónico
                            </p>
                        </div>

                    </div>
                </div>

            </div>

        </div>

    </section>

    {{-- CONSULTA DIRECTA EN PROGRESO --}}
    @if ($directDraft)

    <section
        class="relative mt-6 overflow-hidden rounded-[1.75rem]
                   border border-orange-200/90
                   bg-gradient-to-r from-orange-50 via-amber-50/80 to-orange-50
                   shadow-doctotal-lg">

        <div
            class="pointer-events-none absolute inset-y-0 left-0 w-40
                       bg-[radial-gradient(circle_at_center,rgba(249,115,22,0.16),transparent_65%)]">
        </div>

        <div
            class="relative flex flex-col gap-5 p-5
                       sm:p-6 lg:flex-row lg:items-center lg:justify-between">

            <div class="flex min-w-0 items-start gap-4 sm:gap-5">

                <div
                    class="flex h-16 w-16 shrink-0 items-center justify-center
                               rounded-full border border-orange-200
                               bg-orange-100/90 text-orange-600
                               shadow-[0_10px_24px_rgba(249,115,22,0.16)]">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.9"
                        class="h-8 w-8">
                        <circle cx="12" cy="12" r="8" />
                        <path
                            d="M12 7v5l3 2"
                            stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>

                </div>

                <div class="min-w-0">

                    <div class="flex flex-wrap items-center gap-3">

                        <p class="text-lg font-bold text-slate-950">
                            Consulta en progreso
                        </p>

                        <span
                            class="inline-flex items-center rounded-full
                                       border border-orange-200
                                       bg-orange-100 px-3 py-1
                                       text-xs font-bold text-orange-700">
                            Borrador
                        </span>

                    </div>

                    <div
                        class="mt-3 inline-flex items-center gap-2
                                   text-base font-bold text-orange-600">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            class="h-5 w-5">
                            <rect x="4" y="5" width="16" height="15" rx="2" />
                            <path d="M8 3v4M16 3v4M4 10h16" />
                        </svg>

                        {{ $directDraft->consultation_at->format('d/m/Y') }}
                        ·
                        {{ $directDraft->consultation_at->format('H:i') }}

                    </div>

                    @if ($directDraft->reason)
                    <p class="mt-2 text-sm font-medium text-orange-800">
                        {{ $directDraft->reason }}
                    </p>
                    @endif

                    <p class="mt-2 text-sm text-orange-600">
                        Esta consulta todavía no forma parte del historial clínico.
                    </p>

                </div>

            </div>

            <a
                href="{{ route('consultations.create', ['uuid' => $patient->uuid]) }}"
                class="inline-flex shrink-0 items-center justify-center gap-2
                           rounded-2xl bg-gradient-to-r from-orange-600 to-orange-500
                           px-5 py-3 text-sm font-bold text-white
                           shadow-[0_10px_24px_rgba(234,88,12,0.22)]
                           hover:-translate-y-0.5 hover:from-orange-700 hover:to-orange-600">

                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    class="h-5 w-5">
                    <path
                        d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" />
                    <path d="M14 2v6h6M9 13h6M9 17h4" />
                </svg>

                Continuar consulta

            </a>

        </div>

    </section>

    @endif

    {{-- RESUMEN SUPERIOR --}}
    <div class="mt-5 grid gap-5 lg:grid-cols-3">

        {{-- DATOS GENERALES --}}
        <section class="dt-card">

            <div class="dt-card-header flex items-center gap-3">

                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4.5 w-4.5">
                        <rect x="4" y="3" width="16" height="18" rx="2" />
                        <path d="M8 7h8M8 11h8M8 15h5" />
                    </svg>
                </div>

                <h2 class="font-semibold text-slate-900">
                    Datos generales
                </h2>

            </div>

            <div class="grid gap-x-5 gap-y-5 p-5 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Fecha de nacimiento
                    </p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">
                        {{ $patient->birth_date?->format('d/m/Y') ?? 'No registrada' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Edad
                    </p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">
                        {{ $patient->birth_date ? $patient->birth_date->age . ' años' : 'No disponible' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Sexo
                    </p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">
                        {{ $sexLabels[$patient->sex] ?? 'No registrado' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Tipo de sangre
                    </p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">
                        {{ $patient->blood_type ?: 'Desconocido' }}
                    </p>
                </div>

            </div>

        </section>

        {{-- CONTACTO --}}
        <section class="dt-card">

            <div class="dt-card-header flex items-center gap-3">

                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-50 text-cyan-600">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4.5 w-4.5">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92Z" />
                    </svg>
                </div>

                <h2 class="font-semibold text-slate-900">
                    Contacto
                </h2>

            </div>

            <div class="space-y-5 p-5">

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Teléfono</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">
                        {{ $patient->phone ?: 'No registrado' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">WhatsApp</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">
                        {{ $patient->whatsapp ?: 'No registrado' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Correo</p>
                    <p class="mt-1 break-all text-sm font-semibold text-slate-900">
                        {{ $patient->email ?: 'No registrado' }}
                    </p>
                </div>

            </div>

        </section>

        {{-- CONTACTOS DE EMERGENCIA --}}
        <section class="dt-card">

            <div class="dt-card-header flex items-center justify-between gap-3">

                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4.5 w-4.5">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M19 8v6M16 11h6" />
                        </svg>
                    </div>

                    <h2 class="font-semibold text-slate-900">
                        Contactos de emergencia
                    </h2>
                </div>

                <button
                    type="button"
                    wire:click="openEmergencyContactModal"
                    class="text-sm font-semibold text-blue-600 hover:text-blue-700">
                    + Agregar
                </button>

            </div>

            <div class="p-5">

                @forelse ($patient->emergencyContacts as $contact)

                <div class="border-b border-slate-100 py-4 first:pt-0 last:border-0 last:pb-0">

                    <div class="flex items-start gap-3">

                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-blue-600 text-xs font-semibold text-white">
                            {{ strtoupper(substr($contact->name ?? 'C', 0, 1)) }}
                        </div>

                        <div class="min-w-0 flex-1">

                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold text-slate-900">
                                    {{ $contact->name }}
                                </p>

                                @if ($contact->is_primary)
                                <span class="dt-badge dt-badge-success">
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
                            <p class="mt-1 break-all text-sm text-slate-500">
                                {{ $contact->email }}
                            </p>
                            @endif

                            <div class="mt-3 flex items-center gap-3">
                                <button
                                    type="button"
                                    wire:click="editEmergencyContact({{ $contact->id }})"
                                    class="text-xs font-semibold text-slate-600 hover:text-blue-600">
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
                                    class="text-xs font-semibold text-red-600 hover:text-red-700">
                                    Eliminar
                                </button>
                            </div>

                        </div>

                    </div>

                </div>

                @empty

                <div class="dt-empty-state py-8">
                    <p class="font-medium text-slate-700">
                        Sin contactos de emergencia
                    </p>
                    <p class="mt-1 text-sm text-slate-500">
                        Agrega una persona a quien contactar en caso necesario.
                    </p>
                </div>

                @endforelse

            </div>

        </section>

    </div>

    {{-- RESUMEN CLÍNICO --}}
    <section class="dt-card mt-5 shadow-doctotal-md">

        <div class="dt-card-header flex flex-col gap-3 bg-white sm:flex-row sm:items-center sm:justify-between">

            <div class="flex items-start gap-3">

                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4.5 w-4.5">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" />
                        <path d="M14 2v6h6M8 13h8M8 17h5" />
                    </svg>
                </div>

                <div>
                    <h2 class="font-semibold text-slate-900">
                        Resumen clínico
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Antecedentes médicos relevantes del paciente.
                    </p>
                </div>

            </div>

            <button
                type="button"
                wire:click="openMedicalHistoryModal"
                class="dt-btn dt-btn-secondary">

                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                    <path d="M12 20h9" />
                    <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" />
                </svg>

                Editar antecedentes
            </button>

        </div>

        @if ($history)

        @php
        $historyItems = [
        [
        'label' => 'Alergias',
        'value' => $history->allergies_text,
        'tone' => 'rose',
        ],
        [
        'label' => 'Medicamentos actuales',
        'value' => $history->current_medications_text,
        'tone' => 'blue',
        ],
        [
        'label' => 'Enfermedades crónicas',
        'value' => $history->chronic_conditions_text,
        'tone' => 'amber',
        ],
        [
        'label' => 'Cirugías',
        'value' => $history->surgeries_text,
        'tone' => 'violet',
        ],
        [
        'label' => 'Antecedentes familiares',
        'value' => $history->family_history_text,
        'tone' => 'cyan',
        ],
        [
        'label' => 'Antecedentes personales',
        'value' => $history->personal_history_text,
        'tone' => 'emerald',
        ],
        [
        'label' => 'Hábitos',
        'value' => $history->habits_text,
        'tone' => 'slate',
        ],
        [
        'label' => 'Notas adicionales',
        'value' => $history->other_notes,
        'tone' => 'slate',
        ],
        ];
        @endphp

        <div class="grid gap-3 border-b border-slate-200 bg-slate-50/70 p-4 sm:grid-cols-2 sm:p-5 xl:grid-cols-4">

            @foreach ($historyItems as $item)

            @php
            $toneClasses = match ($item['tone']) {
            'rose' => 'bg-rose-50 text-rose-600',
            'blue' => 'bg-blue-50 text-blue-600',
            'amber' => 'bg-amber-50 text-amber-600',
            'violet' => 'bg-violet-50 text-violet-600',
            'cyan' => 'bg-cyan-50 text-cyan-600',
            'emerald' => 'bg-emerald-50 text-emerald-600',
            default => 'bg-slate-100 text-slate-600',
            };
            @endphp

            <div class="group rounded-2xl border border-slate-200/90 bg-white p-4 shadow-doctotal-sm hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-doctotal-md">

                <div class="flex items-start gap-3">

                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $toneClasses }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                            <circle cx="12" cy="12" r="8" />
                            <path d="M12 8v4M12 16h.01" stroke-linecap="round" />
                        </svg>
                    </div>

                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-800">
                            {{ $item['label'] }}
                        </p>
                        <div class="mt-3 rounded-xl bg-slate-50 px-3 py-2.5 ring-1 ring-inset ring-slate-100">
                            <p class="whitespace-pre-line text-sm font-medium leading-6 text-slate-700">
                                {{ $item['value'] ?: 'Sin registro' }}
                            </p>
                        </div>
                    </div>

                </div>

            </div>

            @endforeach

        </div>

        @else

        <div class="p-6">
            <div class="dt-empty-state">
                <p class="font-medium text-slate-700">
                    Sin antecedentes registrados
                </p>
                <p class="mt-1 text-sm text-slate-500">
                    Más adelante podrás completar el historial médico del paciente.
                </p>
            </div>
        </div>

        @endif

        {{-- HISTÓRICOS + DOCUMENTOS --}}
        @php
        $summaryPerPage = 4;

        $diagnosesPageCount = max(
        1,
        (int) ceil(
        $historicalDiagnoses->count() / $summaryPerPage
        )
        );

        $treatmentsPageCount = max(
        1,
        (int) ceil(
        $historicalTreatments->count() / $summaryPerPage
        )
        );

        $documentsPageCount = max(
        1,
        (int) ceil(
        $clinicalDocuments->count() / $summaryPerPage
        )
        );

        $visibleHistoricalDiagnoses = $historicalDiagnoses
        ->forPage(
        $historicalDiagnosesPage,
        $summaryPerPage
        );

        $visibleHistoricalTreatments = $historicalTreatments
        ->forPage(
        $historicalTreatmentsPage,
        $summaryPerPage
        );

        $visibleClinicalDocuments = $clinicalDocuments
        ->forPage(
        $clinicalDocumentsPage,
        $summaryPerPage
        );
        @endphp

        <div class="grid gap-5 border-t border-slate-200 bg-slate-100/60 p-4 sm:p-5 md:grid-cols-2 xl:grid-cols-3">

            {{-- DIAGNÓSTICOS HISTÓRICOS --}}
            @if ($historicalDiagnoses->isNotEmpty())
            <section class="flex h-full min-w-0 flex-col overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-doctotal-md">

                <div class="flex min-h-[76px] items-start justify-between gap-3 border-b border-slate-100 px-4 py-4 sm:px-5">

                    <div class="flex min-w-0 items-start gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                                <path d="M8 3v3M16 3v3M5 8h14M6 5h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" />
                                <path d="M9 13h6M12 10v6" stroke-linecap="round" />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-slate-900">
                                Diagnósticos históricos
                            </h3>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $historicalDiagnoses->count() }}
                                {{ $historicalDiagnoses->count() === 1 ? 'diagnóstico agrupado' : 'diagnósticos agrupados' }}
                            </p>
                        </div>
                    </div>

                </div>

                <div class="flex-1 px-4 sm:px-5">

                    @forelse ($visibleHistoricalDiagnoses as $entry)

                    <div class="border-b border-slate-100 py-3.5 last:border-0">

                        <div class="flex items-start justify-between gap-3">

                            <div class="min-w-0">
                                <p class="text-sm leading-5 text-slate-700">
                                    @if ($entry['code'])
                                    <span class="font-semibold text-slate-900">
                                        {{ $entry['code'] }}
                                    </span>
                                    ·
                                    @endif

                                    {{ $entry['description'] }}
                                </p>

                                <p class="mt-1 text-xs leading-5 text-slate-400">
                                    {{ $entry['count'] }}
                                    {{ $entry['count'] === 1 ? 'registro' : 'registros' }}
                                    · Último {{ $entry['last_occurred_at']->format('d/m/Y') }}
                                </p>
                            </div>

                            <a
                                href="{{ route('consultations.show', ['uuid' => $entry['latest_consultation']->uuid]) }}"
                                class="shrink-0 text-xs font-semibold text-blue-600 hover:text-blue-700">
                                Ver
                            </a>

                        </div>

                    </div>

                    @empty

                    <div class="flex min-h-44 items-center justify-center py-6 text-center">
                        <div>
                            <p class="text-sm font-medium text-slate-700">
                                Sin diagnósticos históricos
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                Aparecerán conforme se finalicen consultas.
                            </p>
                        </div>
                    </div>

                    @endforelse

                </div>

                <div class="mt-auto flex items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/70 px-4 py-3 sm:px-5">
                    <p class="text-xs text-slate-500">
                        Página {{ $historicalDiagnosesPage }} de {{ $diagnosesPageCount }}
                    </p>

                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            wire:click="previousHistoricalDiagnosesPage"
                            @disabled($historicalDiagnosesPage <=1)
                            aria-label="Página anterior de diagnósticos"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm hover:border-slate-300 hover:text-slate-900 disabled:pointer-events-none disabled:opacity-40">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                                <path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>

                        <button
                            type="button"
                            wire:click="nextHistoricalDiagnosesPage"
                            @disabled($historicalDiagnosesPage>= $diagnosesPageCount)
                            aria-label="Página siguiente de diagnósticos"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm hover:border-slate-300 hover:text-slate-900 disabled:pointer-events-none disabled:opacity-40">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                                <path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                    </div>
                </div>

            </section>
            @endif

            {{-- TRATAMIENTOS HISTÓRICOS --}}
            <section class="flex h-full min-w-0 flex-col overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-doctotal-md">

                <div class="flex min-h-[76px] items-start justify-between gap-3 border-b border-slate-100 px-4 py-4 sm:px-5">

                    <div class="flex min-w-0 items-start gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                                <path d="m10.5 6.5 7 7a4 4 0 0 1-5.66 5.66l-7-7A4 4 0 0 1 10.5 6.5Z" />
                                <path d="m8 15 7-7" />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-slate-900">
                                Tratamientos históricos
                            </h3>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $historicalTreatments->count() }}
                                {{ $historicalTreatments->count() === 1 ? 'tratamiento agrupado' : 'tratamientos agrupados' }}
                            </p>
                        </div>
                    </div>

                </div>

                <div class="flex-1 px-4 sm:px-5">

                    @forelse ($visibleHistoricalTreatments as $entry)

                    <div class="border-b border-slate-100 py-3.5 last:border-0">

                        <div class="flex items-start justify-between gap-3">

                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-900">
                                    {{ $entry['medication_name'] }}
                                </p>

                                <p class="mt-1 text-xs leading-5 text-slate-500">
                                    @if ($entry['dose'])
                                    {{ $entry['dose'] }}
                                    @endif

                                    @if ($entry['frequency'])
                                    · {{ $entry['frequency'] }}
                                    @endif

                                    @if ($entry['duration'])
                                    · {{ $entry['duration'] }}
                                    @endif
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    {{ $entry['count'] }}
                                    {{ $entry['count'] === 1 ? 'registro' : 'registros' }}
                                    · Último {{ $entry['last_prescribed_at']->format('d/m/Y') }}
                                </p>
                            </div>

                            <a
                                href="{{ route('prescriptions.show', ['uuid' => $entry['latest_prescription']->uuid]) }}"
                                class="shrink-0 text-xs font-semibold text-blue-600 hover:text-blue-700">
                                Ver
                            </a>

                        </div>

                    </div>

                    @empty

                    <div class="flex min-h-44 items-center justify-center py-6 text-center">
                        <div>
                            <p class="text-sm font-medium text-slate-700">
                                Sin tratamientos históricos
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                Los tratamientos agrupados aparecerán aquí.
                            </p>
                        </div>
                    </div>

                    @endforelse

                </div>

                <div class="mt-auto flex items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/70 px-4 py-3 sm:px-5">
                    <p class="text-xs text-slate-500">
                        Página {{ $historicalTreatmentsPage }} de {{ $treatmentsPageCount }}
                    </p>

                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            wire:click="previousHistoricalTreatmentsPage"
                            @disabled($historicalTreatmentsPage <=1)
                            aria-label="Página anterior de tratamientos"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm hover:border-slate-300 hover:text-slate-900 disabled:pointer-events-none disabled:opacity-40">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                                <path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>

                        <button
                            type="button"
                            wire:click="nextHistoricalTreatmentsPage"
                            @disabled($historicalTreatmentsPage>= $treatmentsPageCount)
                            aria-label="Página siguiente de tratamientos"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm hover:border-slate-300 hover:text-slate-900 disabled:pointer-events-none disabled:opacity-40">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                                <path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                    </div>
                </div>

            </section>

            {{-- DOCUMENTOS CLÍNICOS --}}
            <section class="flex h-full min-w-0 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm md:col-span-2 xl:col-span-1">

                <div class="flex min-h-[76px] items-start justify-between gap-3 border-b border-slate-100 px-4 py-4 sm:px-5">

                    <div class="flex min-w-0 items-start gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" />
                                <path d="M14 2v6h6" />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-slate-900">
                                Documentos clínicos
                            </h3>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $clinicalDocuments->count() }}
                                {{ $clinicalDocuments->count() === 1 ? 'documento' : 'documentos' }}
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        wire:click="openClinicalDocumentModal"
                        class="shrink-0 text-xs font-semibold text-blue-600 hover:text-blue-700 sm:text-sm">
                        + Agregar
                    </button>

                </div>

                <div class="flex-1 px-4 sm:px-5">

                    @forelse ($visibleClinicalDocuments as $document)

                    @php
                    $isImage = str_starts_with(
                    $document->mime_type ?? '',
                    'image/'
                    );

                    $isPdf =
                    $document->mime_type === 'application/pdf';
                    @endphp

                    <div class="border-b border-slate-100 py-3.5 last:border-0">

                        <div class="flex gap-3">

                            <div class="shrink-0">

                                @if ($isImage)

                                <a
                                    href="{{ route('clinical-documents.view', $document) }}"
                                    target="_blank"
                                    rel="noopener">

                                    <img
                                        src="{{ route('clinical-documents.view', $document) }}"
                                        alt="{{ $document->title }}"
                                        class="h-12 w-12 rounded-xl border border-slate-200 object-cover shadow-sm">

                                </a>

                                @elseif ($isPdf)

                                <a
                                    href="{{ route('clinical-documents.view', $document) }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="flex h-12 w-12 flex-col items-center justify-center rounded-xl border border-red-100 bg-red-50 text-red-600 hover:bg-red-100">

                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-5 w-5">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                        <path d="M14 2v6h6" />
                                    </svg>

                                    <span class="mt-0.5 text-[9px] font-bold">
                                        PDF
                                    </span>

                                </a>

                                @else

                                <div class="flex h-12 w-12 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-500">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-5 w-5">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                        <path d="M14 2v6h6" />
                                    </svg>
                                </div>

                                @endif

                            </div>

                            <div class="min-w-0 flex-1">

                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="truncate text-sm font-semibold text-slate-900">
                                        {{ $document->title }}
                                    </p>

                                    <span class="dt-badge dt-badge-neutral">
                                        {{ $categoryLabels[$document->category] ?? 'General' }}
                                    </span>
                                </div>

                                <p class="mt-1 truncate text-xs text-slate-500" title="{{ $document->original_name }}">
                                    {{ $document->original_name }}
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    {{
                                            $document->document_date?->format('d/m/Y')
                                            ?? $document->created_at->format('d/m/Y')
                                        }}
                                    ·
                                    @if ($document->size_bytes >= 1024 * 1024)
                                    {{ number_format($document->size_bytes / 1024 / 1024, 1) }} MB
                                    @else
                                    {{ number_format($document->size_bytes / 1024, 0) }} KB
                                    @endif
                                </p>

                                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-2">

                                    <a
                                        href="{{ route('clinical-documents.view', $document) }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="text-xs font-semibold text-slate-600 hover:text-blue-600">
                                        Ver
                                    </a>

                                    <a
                                        href="{{ route('clinical-documents.download', $document) }}"
                                        class="text-xs font-semibold text-slate-600 hover:text-blue-600">
                                        Descargar
                                    </a>

                                    @if ($document->consultation)
                                    <a
                                        href="{{ route('consultations.show', ['uuid' => $document->consultation->uuid]) }}"
                                        class="text-xs font-semibold text-slate-600 hover:text-blue-600">
                                        Consulta
                                    </a>
                                    @endif

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
                                                        $wire.deleteClinicalDocument('{{ $document->uuid }}')
                                                    }
                                                })
                                            "
                                        class="text-xs font-semibold text-red-600 hover:text-red-700">
                                        Eliminar
                                    </button>

                                </div>

                            </div>

                        </div>

                    </div>

                    @empty

                    <div class="flex min-h-44 items-center justify-center py-6 text-center">
                        <div>
                            <p class="text-sm font-medium text-slate-700">
                                Sin documentos clínicos
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                Los estudios y archivos aparecerán aquí.
                            </p>
                        </div>
                    </div>

                    @endforelse

                </div>

                <div class="mt-auto flex items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/70 px-4 py-3 sm:px-5">
                    <p class="text-xs text-slate-500">
                        Página {{ $clinicalDocumentsPage }} de {{ $documentsPageCount }}
                    </p>

                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            wire:click="previousClinicalDocumentsPage"
                            @disabled($clinicalDocumentsPage <=1)
                            aria-label="Página anterior de documentos"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm hover:border-slate-300 hover:text-slate-900 disabled:pointer-events-none disabled:opacity-40">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                                <path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>

                        <button
                            type="button"
                            wire:click="nextClinicalDocumentsPage"
                            @disabled($clinicalDocumentsPage>= $documentsPageCount)
                            aria-label="Página siguiente de documentos"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm hover:border-slate-300 hover:text-slate-900 disabled:pointer-events-none disabled:opacity-40">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                                <path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                    </div>
                </div>

            </section>

        </div>

    </section>

    {{-- HISTORIA CLÍNICA --}}
    @php
    $timelinePerPage = 5;

    $timelinePageCount = max(
    1,
    (int) ceil(
    $clinicalTimeline->count() / $timelinePerPage
    )
    );

    $visibleClinicalTimeline = $clinicalTimeline
    ->forPage(
    $clinicalTimelinePage,
    $timelinePerPage
    );
    @endphp

    <section class="dt-card mt-5 overflow-hidden shadow-doctotal-md">

        <div class="dt-card-header flex flex-col gap-3 bg-white sm:flex-row sm:items-center sm:justify-between">

            <div class="flex items-start gap-3">

                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4.5 w-4.5">
                        <rect x="4" y="3" width="16" height="18" rx="2" />
                        <path d="M9 8h6M9 12h6M9 16h4" />
                    </svg>
                </div>

                <div>
                    <h2 class="font-semibold text-slate-900">
                        Historia clínica
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Línea de tiempo clínica del paciente.
                    </p>
                </div>

            </div>

            <div class="flex flex-wrap items-center gap-2">

                @if ($clinicalTimeline->isNotEmpty())
                <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-2 py-1.5">
                    <button
                        type="button"
                        wire:click="previousClinicalTimelinePage"
                        @disabled($clinicalTimelinePage <=1)
                        aria-label="Página anterior de historia clínica"
                        class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-white text-slate-500 shadow-sm ring-1 ring-slate-200 hover:text-slate-900 disabled:pointer-events-none disabled:opacity-40">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                            <path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>

                    <span class="min-w-14 text-center text-xs font-medium text-slate-500">
                        {{ $clinicalTimelinePage }} / {{ $timelinePageCount }}
                    </span>

                    <button
                        type="button"
                        wire:click="nextClinicalTimelinePage"
                        @disabled($clinicalTimelinePage>= $timelinePageCount)
                        aria-label="Página siguiente de historia clínica"
                        class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-white text-slate-500 shadow-sm ring-1 ring-slate-200 hover:text-slate-900 disabled:pointer-events-none disabled:opacity-40">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                            <path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                </div>
                @endif

                @if (! $directDraft)
                <a
                    href="{{ route('consultations.create', ['uuid' => $patient->uuid]) }}"
                    class="dt-btn dt-btn-secondary">
                    + Nueva consulta
                </a>
                @endif

            </div>

        </div>

        <div class="relative bg-slate-50/40 px-3 py-3 sm:px-4">
            @if ($visibleClinicalTimeline->isNotEmpty())
            <div
                aria-hidden="true"
                class="pointer-events-none absolute bottom-8 left-[31px] top-8 w-px bg-gradient-to-b from-blue-200 via-slate-200 to-slate-200 sm:left-[39px]">
            </div>
            @endif

            <div class="space-y-3">

                @forelse ($visibleClinicalTimeline as $event)

                @if ($event['type'] === 'consultation')

                @php
                $consultation = $event['consultation'];
                @endphp

                <article class="relative grid gap-4 rounded-2xl border border-slate-200/80 bg-white py-5 pl-14 pr-5 shadow-doctotal-sm transition hover:border-slate-300 hover:shadow-doctotal-md sm:pl-16 sm:pr-6 lg:grid-cols-[120px_minmax(0,1fr)_auto]">

                    <div class="relative pl-7 lg:pl-0">

                        <div class="absolute left-[-37px] top-0 flex h-8 w-8 items-center justify-center rounded-full border border-blue-100 bg-blue-50 text-blue-600 shadow-sm sm:left-[-45px]">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                                <rect x="4" y="5" width="16" height="15" rx="2" />
                                <path d="M8 3v4M16 3v4M4 10h16" stroke-linecap="round" />
                            </svg>
                        </div>

                        <p class="text-sm font-semibold text-slate-900">
                            {{ $event['occurred_at']->format('d/m/Y') }}
                        </p>

                        <p class="mt-1 text-xs font-medium text-slate-500">
                            {{ $event['occurred_at']->format('H:i') }}
                        </p>

                        <span class="dt-badge dt-badge-success mt-2">
                            Consulta
                        </span>

                    </div>

                    <div class="min-w-0">

                        <p class="text-sm font-semibold text-slate-900">
                            {{ $consultation->reason ?: 'Sin motivo registrado' }}
                        </p>

                        @if ($consultation->diagnoses->isNotEmpty())

                        <div class="mt-3">

                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                Diagnósticos
                            </p>

                            <div class="mt-1.5 space-y-1">

                                @foreach ($consultation->diagnoses as $diagnosis)

                                <p class="text-sm text-slate-700">
                                    @if ($diagnosis->code)
                                    <span class="font-semibold">
                                        {{ $diagnosis->code }}
                                    </span>
                                    ·
                                    @endif

                                    {{ $diagnosis->description }}

                                    @if ($diagnosis->is_primary)
                                    <span class="text-xs font-medium text-slate-400">
                                        (Principal)
                                    </span>
                                    @endif
                                </p>

                                @endforeach

                            </div>

                        </div>

                        @endif

                        <div class="mt-3 flex flex-wrap gap-2">

                            @if ($consultation->systolic_bp && $consultation->diastolic_bp)
                            <span class="dt-badge dt-badge-neutral">
                                PA {{ $consultation->systolic_bp }}/{{ $consultation->diastolic_bp }}
                            </span>
                            @endif

                            @if ($consultation->heart_rate)
                            <span class="dt-badge dt-badge-neutral">
                                FC {{ $consultation->heart_rate }} lpm
                            </span>
                            @endif

                            @if ($consultation->temperature_c)
                            <span class="dt-badge dt-badge-neutral">
                                Temp {{ $consultation->temperature_c }} °C
                            </span>
                            @endif

                            @if ($consultation->oxygen_saturation)
                            <span class="dt-badge dt-badge-neutral">
                                SatO₂ {{ $consultation->oxygen_saturation }}%
                            </span>
                            @endif

                        </div>

                        @if ($consultation->prescriptions->isNotEmpty())

                        <div class="mt-4">

                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                Tratamiento
                            </p>

                            <div class="mt-2 flex flex-wrap gap-2">

                                @foreach ($consultation->prescriptions as $prescription)

                                @forelse ($prescription->items as $item)

                                <span class="inline-flex max-w-full items-center rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs text-slate-700">

                                    <span class="font-semibold">
                                        {{ $item->medication_name }}
                                    </span>

                                    @if ($item->dose)
                                    <span class="ml-1">· {{ $item->dose }}</span>
                                    @endif

                                    @if ($item->frequency)
                                    <span class="ml-1">· {{ $item->frequency }}</span>
                                    @endif

                                    @if ($item->duration)
                                    <span class="ml-1">· {{ $item->duration }}</span>
                                    @endif

                                </span>

                                @empty

                                <span class="text-sm text-slate-500">
                                    Receta sin medicamentos registrados.
                                </span>

                                @endforelse

                                <a
                                    href="{{ route('prescriptions.show', ['uuid' => $prescription->uuid]) }}"
                                    class="inline-flex items-center text-xs font-semibold text-blue-600 hover:text-blue-700">
                                    Ver receta
                                </a>

                                @endforeach

                            </div>

                        </div>

                        @endif

                    </div>

                    <div class="lg:pt-0.5">
                        <a
                            href="{{ route('consultations.show', ['uuid' => $consultation->uuid]) }}"
                            class="text-sm font-semibold text-slate-600 hover:text-blue-600">
                            Ver consulta
                        </a>
                    </div>

                </article>

                @elseif ($event['type'] === 'prescription')

                @php
                $prescription = $event['prescription'];
                @endphp

                <article class="relative grid gap-4 rounded-2xl border border-slate-200/80 bg-white py-5 pl-14 pr-5 shadow-doctotal-sm transition hover:border-slate-300 hover:shadow-doctotal-md sm:pl-16 sm:pr-6 lg:grid-cols-[120px_minmax(0,1fr)_auto]">

                    <div class="relative pl-7 lg:pl-0">

                        <div class="absolute left-[-37px] top-0 flex h-8 w-8 items-center justify-center rounded-full border border-violet-100 bg-violet-50 text-violet-600 shadow-sm sm:left-[-45px]">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" />
                                <path d="M14 2v6h6M9 13h6M9 17h4" />
                            </svg>
                        </div>

                        <p class="text-sm font-semibold text-slate-900">
                            {{ $event['occurred_at']->format('d/m/Y') }}
                        </p>

                        <p class="mt-1 text-xs font-medium text-slate-500">
                            {{ $event['occurred_at']->format('H:i') }}
                        </p>

                        <span class="dt-badge bg-violet-50 text-violet-700 ring-1 ring-inset ring-violet-600/10 mt-2">
                            Receta
                        </span>

                    </div>

                    <div>

                        <p class="text-sm font-semibold text-slate-900">
                            Receta emitida fuera de una consulta
                        </p>

                        <div class="mt-3 flex flex-wrap gap-2">

                            @forelse ($prescription->items as $item)

                            <span class="inline-flex max-w-full items-center rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs text-slate-700">

                                <span class="font-semibold">
                                    {{ $item->medication_name }}
                                </span>

                                @if ($item->dose)
                                <span class="ml-1">· {{ $item->dose }}</span>
                                @endif

                                @if ($item->frequency)
                                <span class="ml-1">· {{ $item->frequency }}</span>
                                @endif

                                @if ($item->duration)
                                <span class="ml-1">· {{ $item->duration }}</span>
                                @endif

                            </span>

                            @empty

                            <span class="text-sm text-slate-500">
                                Sin medicamentos registrados.
                            </span>

                            @endforelse

                        </div>

                    </div>

                    <div class="lg:pt-0.5">
                        <a
                            href="{{ route('prescriptions.show', ['uuid' => $prescription->uuid]) }}"
                            class="text-sm font-semibold text-slate-600 hover:text-blue-600">
                            Ver receta
                        </a>
                    </div>

                </article>

                @endif

                @empty

                <div class="p-6">
                    <div class="dt-empty-state">
                        <p class="font-medium text-slate-700">
                            Sin actividad clínica registrada
                        </p>
                        <p class="mt-1 text-sm text-slate-500">
                            Las consultas finalizadas y las recetas independientes aparecerán aquí.
                        </p>
                    </div>
                </div>

                @endforelse

            </div>

        </div>

    </section>

    {{-- MODAL CONTACTO DE EMERGENCIA --}}
    @if ($showEmergencyContactModal)

    <div
        class="fixed inset-0 z-50 flex items-center justify-center
                   bg-slate-950/55 p-4 backdrop-blur-[2px]">

        <div
            class="max-h-[92vh] w-full max-w-xl overflow-y-auto
                       rounded-[1.75rem] border border-white/70 bg-white
                       shadow-[0_28px_80px_rgba(15,23,42,0.28)]">

            {{-- Header --}}
            <div
                class="flex items-start justify-between gap-4
                           border-b border-slate-200/80
                           bg-gradient-to-r from-violet-50/90 via-white to-blue-50/80
                           px-5 py-5 sm:px-6">

                <div class="flex items-start gap-4">

                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center
                                   rounded-2xl bg-violet-100 text-violet-600
                                   shadow-sm">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            class="h-6 w-6">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M19 8v6M16 11h6" />
                        </svg>

                    </div>

                    <div>

                        <h2 class="text-xl font-bold tracking-tight text-slate-950">
                            {{ $editingEmergencyContactId
                                    ? 'Editar contacto de emergencia'
                                    : 'Nuevo contacto de emergencia'
                                }}
                        </h2>

                        <p class="mt-1 text-sm leading-6 text-slate-500">
                            {{ $editingEmergencyContactId
                                    ? 'Actualiza los datos del contacto seleccionado.'
                                    : 'Agrega una persona de contacto para este paciente.'
                                }}
                        </p>

                    </div>

                </div>

                <button
                    type="button"
                    wire:click="closeEmergencyContactModal"
                    class="flex h-9 w-9 shrink-0 items-center justify-center
                               rounded-xl text-slate-400
                               hover:bg-white hover:text-slate-700">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        class="h-5 w-5">
                        <path d="M6 6l12 12M18 6 6 18" stroke-linecap="round" />
                    </svg>
                </button>

            </div>

            <form wire:submit="saveEmergencyContact">

                <div class="space-y-5 p-5 sm:p-6">

                    <div>
                        <label class="dt-label">
                            Nombre *
                        </label>

                        <div class="relative">
                            <div
                                class="pointer-events-none absolute inset-y-0 left-0
                                           flex items-center pl-3.5 text-violet-500">
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    class="h-4.5 w-4.5">
                                    <circle cx="12" cy="8" r="4" />
                                    <path d="M5 21a7 7 0 0 1 14 0" />
                                </svg>
                            </div>

                            <input
                                wire:model="emergency_contact_name"
                                type="text"
                                placeholder="Nombre completo del contacto"
                                class="dt-input pl-10">
                        </div>

                        @error('emergency_contact_name')
                        <p class="mt-1.5 text-sm font-medium text-red-600">
                            {{ $message }}
                        </p>
                        @enderror
                    </div>

                    <div>
                        <label class="dt-label">
                            Parentesco / relación
                        </label>

                        <div class="relative">
                            <div
                                class="pointer-events-none absolute inset-y-0 left-0
                                           flex items-center pl-3.5 text-violet-500">
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    class="h-4.5 w-4.5">
                                    <path d="M12 21s-7-4.35-7-10a4 4 0 0 1 7-2.65A4 4 0 0 1 19 11c0 5.65-7 10-7 10Z" />
                                </svg>
                            </div>

                            <input
                                wire:model="emergency_contact_relationship"
                                type="text"
                                placeholder="Ej. Esposa, hermano, hijo, madre, padre..."
                                class="dt-input pl-10">
                        </div>
                    </div>

                    <div>
                        <label class="dt-label">
                            Teléfono *
                        </label>

                        <div class="relative">
                            <div
                                class="pointer-events-none absolute inset-y-0 left-0
                                           flex items-center pl-3.5 text-violet-500">
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    class="h-4.5 w-4.5">
                                    <path
                                        d="M22 16.92v3a2 2 0 0 1-2.18 2
                                               19.79 19.79 0 0 1-8.63-3.07
                                               19.5 19.5 0 0 1-6-6
                                               19.79 19.79 0 0 1-3.07-8.67
                                               A2 2 0 0 1 4.11 2h3
                                               a2 2 0 0 1 2 1.72" />
                                </svg>
                            </div>

                            <input
                                wire:model="emergency_contact_phone"
                                type="text"
                                placeholder="Número de teléfono de contacto"
                                class="dt-input pl-10">
                        </div>

                        @error('emergency_contact_phone')
                        <p class="mt-1.5 text-sm font-medium text-red-600">
                            {{ $message }}
                        </p>
                        @enderror
                    </div>

                    <div>
                        <label class="dt-label">
                            Correo electrónico
                        </label>

                        <div class="relative">
                            <div
                                class="pointer-events-none absolute inset-y-0 left-0
                                           flex items-center pl-3.5 text-violet-500">
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    class="h-4.5 w-4.5">
                                    <rect x="3" y="5" width="18" height="14" rx="2" />
                                    <path d="m3 7 9 6 9-6" />
                                </svg>
                            </div>

                            <input
                                wire:model="emergency_contact_email"
                                type="email"
                                placeholder="correo@ejemplo.com"
                                class="dt-input pl-10">
                        </div>

                        @error('emergency_contact_email')
                        <p class="mt-1.5 text-sm font-medium text-red-600">
                            {{ $message }}
                        </p>
                        @enderror
                    </div>

                    <label
                        class="flex cursor-pointer items-center justify-between gap-4
                                   rounded-2xl border border-violet-100
                                   bg-gradient-to-r from-violet-50/80 to-blue-50/50
                                   p-4 shadow-sm">

                        <div class="flex min-w-0 items-center gap-3">

                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center
                                           rounded-xl bg-violet-100 text-violet-600">
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    class="h-5 w-5">
                                    <path d="m12 3 2.2 4.46 4.93.72-3.56 3.47.84 4.9L12 14.23 7.59 16.55l.84-4.9-3.56-3.47 4.93-.72Z" />
                                </svg>
                            </div>

                            <div>
                                <p class="text-sm font-semibold text-slate-800">
                                    Marcar como contacto principal
                                </p>
                                <p class="mt-0.5 text-xs leading-5 text-slate-500">
                                    Se mostrará primero en la lista de contactos de emergencia.
                                </p>
                            </div>

                        </div>

                        <input
                            wire:model="emergency_contact_is_primary"
                            type="checkbox"
                            class="h-5 w-5 rounded border-slate-300 text-violet-600 focus:ring-violet-500">

                    </label>

                </div>

                <div
                    class="flex flex-col-reverse gap-3
                               border-t border-slate-200/80
                               bg-slate-50/70 px-5 py-4
                               sm:flex-row sm:justify-end sm:px-6">

                    <button
                        type="button"
                        wire:click="closeEmergencyContactModal"
                        class="dt-btn dt-btn-secondary">
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center justify-center gap-2
                                   rounded-xl bg-gradient-to-r from-blue-600 to-violet-600
                                   px-5 py-2.5 text-sm font-bold text-white
                                   shadow-[0_8px_20px_rgba(79,70,229,0.24)]
                                   hover:-translate-y-0.5 hover:from-blue-700 hover:to-violet-700
                                   disabled:pointer-events-none disabled:opacity-50">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            class="h-4.5 w-4.5">
                            <path d="M5 4h12l2 2v14H5Z" />
                            <path d="M8 4v6h8V4M8 20v-6h8v6" />
                        </svg>

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
        class="fixed inset-0 z-50 flex items-center justify-center
                   bg-slate-950/55 p-3 backdrop-blur-[2px] sm:p-4">

        <div
            class="flex max-h-[94vh] w-full max-w-4xl flex-col
                       overflow-hidden rounded-[1.75rem]
                       border border-white/70 bg-white
                       shadow-[0_28px_80px_rgba(15,23,42,0.28)]">

            {{-- Header --}}
            <div
                class="flex shrink-0 items-start justify-between gap-4
                           border-b border-slate-200/80
                           bg-gradient-to-r from-emerald-50/90 via-white to-cyan-50/70
                           px-5 py-5 sm:px-6">

                <div class="flex items-start gap-4">

                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center
                                   rounded-2xl bg-emerald-100 text-emerald-600
                                   shadow-sm">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            class="h-6 w-6">
                            <path d="M12 21s-8-4.8-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 10c0 6.2-8 11-8 11Z" />
                            <path d="M8.5 12h2l1-2 1.5 4 1-2h1.5" />
                        </svg>

                    </div>

                    <div>

                        <h2 class="text-xl font-bold tracking-tight text-slate-950">
                            Antecedentes médicos
                        </h2>

                        <p class="mt-1 text-sm leading-6 text-slate-500">
                            Registra o actualiza la información clínica básica del paciente.
                        </p>

                    </div>

                </div>

                <button
                    type="button"
                    wire:click="closeMedicalHistoryModal"
                    class="flex h-9 w-9 shrink-0 items-center justify-center
                               rounded-xl text-slate-400
                               hover:bg-white hover:text-slate-700">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        class="h-5 w-5">
                        <path d="M6 6l12 12M18 6 6 18" stroke-linecap="round" />
                    </svg>
                </button>

            </div>

            <form wire:submit="saveMedicalHistory" class="flex min-h-0 flex-1 flex-col">

                <div class="min-h-0 flex-1 overflow-y-auto p-5 sm:p-6">

                    @php
                    $medicalFields = [
                    'allergies_text' => [
                    'label' => 'Alergias',
                    'placeholder' => 'Ej. Penicilina, mariscos, polen...',
                    ],
                    'current_medications_text' => [
                    'label' => 'Medicamentos actuales',
                    'placeholder' => 'Ej. Losartán 50mg, metformina...',
                    ],
                    'chronic_conditions_text' => [
                    'label' => 'Enfermedades crónicas',
                    'placeholder' => 'Ej. Hipertensión arterial, diabetes...',
                    ],
                    'surgeries_text' => [
                    'label' => 'Cirugías',
                    'placeholder' => 'Ej. Apendicectomía, cesárea...',
                    ],
                    'family_history_text' => [
                    'label' => 'Antecedentes familiares',
                    'placeholder' => 'Ej. Diabetes en padres, cáncer...',
                    ],
                    'personal_history_text' => [
                    'label' => 'Antecedentes personales',
                    'placeholder' => 'Ej. Asma, migraña, convulsiones...',
                    ],
                    'gynecological_history_text' => [
                    'label' => 'Antecedentes ginecológicos',
                    'placeholder' => 'Ej. Menarquia, ciclos irregulares...',
                    ],
                    'habits_text' => [
                    'label' => 'Hábitos',
                    'placeholder' => 'Ej. No fuma, ejercicio regular...',
                    ],
                    ];
                    @endphp

                    <div class="grid gap-5 md:grid-cols-2">

                        @foreach ($medicalFields as $field => $meta)

                        <div>

                            <label class="dt-label">
                                {{ $meta['label'] }}
                            </label>

                            <div class="relative">

                                <div
                                    class="pointer-events-none absolute left-3.5 top-3.5
                                                   flex h-7 w-7 items-center justify-center
                                                   rounded-lg bg-emerald-50 text-emerald-600">

                                    @switch($field)
                                    @case('allergies_text')
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        class="h-4 w-4">
                                        <path d="M12 3c-2.2 2.6-4 5-4 7.4a4 4 0 1 0 8 0C16 8 14.2 5.6 12 3Z" />
                                        <path d="m9.5 15.5 5-5M9.5 10.5l5 5" stroke-linecap="round" />
                                    </svg>
                                    @break

                                    @case('current_medications_text')
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        class="h-4 w-4">
                                        <path d="M8.5 4.5a4.95 4.95 0 0 1 7 7l-4 4a4.95 4.95 0 1 1-7-7Z" />
                                        <path d="m7 13 4 4" stroke-linecap="round" />
                                    </svg>
                                    @break

                                    @case('chronic_conditions_text')
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        class="h-4 w-4">
                                        <path d="M3 12h4l2-5 4 10 2-5h6" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    @break

                                    @case('surgeries_text')
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        class="h-4 w-4">
                                        <path d="m14.5 4.5 5 5M5 19l5.5-5.5M8 21l-5-5 9.5-9.5a3.54 3.54 0 0 1 5 5Z" />
                                    </svg>
                                    @break

                                    @case('family_history_text')
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        class="h-4 w-4">
                                        <circle cx="8" cy="8" r="3" />
                                        <circle cx="17" cy="9" r="2.5" />
                                        <path d="M3 20a5 5 0 0 1 10 0M13 20a4 4 0 0 1 8 0" />
                                    </svg>
                                    @break

                                    @case('personal_history_text')
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        class="h-4 w-4">
                                        <circle cx="12" cy="8" r="4" />
                                        <path d="M5 21a7 7 0 0 1 14 0" />
                                    </svg>
                                    @break

                                    @case('gynecological_history_text')
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        class="h-4 w-4">
                                        <circle cx="12" cy="9" r="5" />
                                        <path d="M12 14v7M9 18h6" stroke-linecap="round" />
                                    </svg>
                                    @break

                                    @case('habits_text')
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        class="h-4 w-4">
                                        <path d="M4 15h12a3 3 0 0 1 0 6H4Z" />
                                        <path d="M4 15V9M8 15V7M12 15V5" stroke-linecap="round" />
                                    </svg>
                                    @break

                                    @default
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        class="h-4 w-4">
                                        <circle cx="12" cy="12" r="8" />
                                        <path d="M12 8v4M12 16h.01" stroke-linecap="round" />
                                    </svg>
                                    @endswitch

                                </div>

                                <textarea
                                    wire:model="{{ $field }}"
                                    rows="4"
                                    placeholder="{{ $meta['placeholder'] }}"
                                    class="dt-textarea min-h-28 resize-y pl-12"></textarea>

                            </div>

                            @error($field)
                            <p class="mt-1.5 text-sm font-medium text-red-600">
                                {{ $message }}
                            </p>
                            @enderror

                        </div>

                        @endforeach

                        <div class="md:col-span-2">

                            <label class="dt-label">
                                Notas adicionales
                            </label>

                            <textarea
                                wire:model="other_notes"
                                rows="4"
                                placeholder="Información adicional relevante para el expediente..."
                                class="dt-textarea min-h-28 resize-y"></textarea>

                            @error('other_notes')
                            <p class="mt-1.5 text-sm font-medium text-red-600">
                                {{ $message }}
                            </p>
                            @enderror

                        </div>

                    </div>

                </div>

                <div
                    class="flex shrink-0 flex-col-reverse gap-3
                               border-t border-slate-200/80
                               bg-slate-50/70 px-5 py-4
                               sm:flex-row sm:justify-end sm:px-6">

                    <button
                        type="button"
                        wire:click="closeMedicalHistoryModal"
                        class="dt-btn dt-btn-secondary">
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center justify-center gap-2
                                   rounded-xl bg-gradient-to-r from-emerald-600 to-green-500
                                   px-5 py-2.5 text-sm font-bold text-white
                                   shadow-[0_8px_20px_rgba(16,185,129,0.22)]
                                   hover:-translate-y-0.5 hover:from-emerald-700 hover:to-green-600
                                   disabled:pointer-events-none disabled:opacity-50">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            class="h-4.5 w-4.5">
                            <path d="m5 12 4 4L19 6" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>

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
        class="fixed inset-0 z-50 flex items-center justify-center
                   bg-slate-950/55 p-3 backdrop-blur-[2px] sm:p-4">

        <div
            class="flex max-h-[94vh] w-full max-w-2xl flex-col
                       overflow-hidden rounded-[1.75rem]
                       border border-white/70 bg-white
                       shadow-[0_28px_80px_rgba(15,23,42,0.28)]">

            {{-- Header --}}
            <div
                class="flex shrink-0 items-start justify-between gap-4
                           border-b border-slate-200/80
                           bg-gradient-to-r from-blue-50/90 via-white to-violet-50/70
                           px-5 py-5 sm:px-6">

                <div class="flex items-start gap-4">

                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center
                                   rounded-2xl bg-blue-100 text-blue-600
                                   shadow-sm">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            class="h-6 w-6">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" />
                            <path d="M14 2v6h6M9 13h6M9 17h4" />
                        </svg>

                    </div>

                    <div>

                        <h2 class="text-xl font-bold tracking-tight text-slate-950">
                            Agregar documento clínico
                        </h2>

                        <p class="mt-1 text-sm leading-6 text-slate-500">
                            Adjunta un estudio, resultado, imagen u otro documento relacionado con el paciente.
                        </p>

                    </div>

                </div>

                <button
                    type="button"
                    wire:click="closeClinicalDocumentModal"
                    class="flex h-9 w-9 shrink-0 items-center justify-center
                               rounded-xl text-slate-400
                               hover:bg-white hover:text-slate-700">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        class="h-5 w-5">
                        <path d="M6 6l12 12M18 6 6 18" stroke-linecap="round" />
                    </svg>
                </button>

            </div>

            <form wire:submit="saveClinicalDocument" class="flex min-h-0 flex-1 flex-col">

                <div class="min-h-0 flex-1 space-y-5 overflow-y-auto p-5 sm:p-6">

                    {{-- Archivo --}}
                    <div>

                        <label class="dt-label">
                            Archivo *
                        </label>

                        <label
                            class="group flex cursor-pointer flex-col items-center justify-center
                                       rounded-2xl border-2 border-dashed border-blue-200
                                       bg-gradient-to-br from-blue-50/60 to-violet-50/40
                                       px-5 py-7 text-center
                                       hover:border-blue-400 hover:bg-blue-50/80">

                            <div
                                class="flex h-12 w-12 items-center justify-center
                                           rounded-2xl bg-white text-blue-600
                                           shadow-sm ring-1 ring-slate-200">

                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    class="h-6 w-6">
                                    <path d="M12 16V4M8 8l4-4 4 4" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M5 12H4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-5a2 2 0 0 0-2-2h-1" />
                                </svg>

                            </div>

                            <p class="mt-3 text-sm font-semibold text-slate-800">
                                Selecciona el archivo clínico
                            </p>

                            <p class="mt-1 text-xs text-slate-500">
                                PDF, JPG, JPEG, PNG o WebP · Máximo 10 MB
                            </p>

                            <span
                                class="mt-3 inline-flex items-center rounded-xl
                                           border border-slate-200 bg-white
                                           px-3 py-2 text-xs font-semibold text-slate-700
                                           shadow-sm">
                                Seleccionar archivo
                            </span>

                            <input
                                wire:model="clinical_document_file"
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png,.webp"
                                class="sr-only">

                        </label>

                        @if ($clinical_document_file)
                        <div
                            class="mt-3 rounded-xl border border-blue-100
                                           bg-blue-50/70 px-3 py-2 text-sm text-blue-800">
                            Archivo seleccionado:
                            <span class="font-semibold">
                                {{ $clinical_document_file->getClientOriginalName() }}
                            </span>
                        </div>
                        @endif

                        @error('clinical_document_file')
                        <p class="mt-1.5 text-sm font-medium text-red-600">
                            {{ $message }}
                        </p>
                        @enderror

                        <div
                            wire:loading
                            wire:target="clinical_document_file"
                            class="mt-2 text-sm font-medium text-blue-600">
                            Procesando archivo...
                        </div>

                    </div>

                    {{-- Título --}}
                    <div>

                        <label class="dt-label">
                            Título *
                        </label>

                        <div class="relative">
                            <div
                                class="pointer-events-none absolute inset-y-0 left-0
                                           flex items-center pl-3.5 text-blue-500">
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    class="h-4.5 w-4.5">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" />
                                    <path d="M14 2v6h6" />
                                </svg>
                            </div>

                            <input
                                wire:model="clinical_document_title"
                                type="text"
                                maxlength="190"
                                placeholder="Ej. Biometría hemática"
                                class="dt-input pl-10">
                        </div>

                        @error('clinical_document_title')
                        <p class="mt-1.5 text-sm font-medium text-red-600">
                            {{ $message }}
                        </p>
                        @enderror

                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">

                        <div>

                            <label class="dt-label">
                                Categoría *
                            </label>

                            <select
                                wire:model="clinical_document_category"
                                class="dt-select">

                                <option value="general">General</option>
                                <option value="laboratory">Laboratorio</option>
                                <option value="imaging">Imagen</option>
                                <option value="other">Otro</option>

                            </select>

                            @error('clinical_document_category')
                            <p class="mt-1.5 text-sm font-medium text-red-600">
                                {{ $message }}
                            </p>
                            @enderror

                        </div>

                        <div>

                            <label class="dt-label">
                                Fecha del documento
                            </label>

                            <input
                                wire:model="clinical_document_date"
                                type="date"
                                class="dt-input">

                            @error('clinical_document_date')
                            <p class="mt-1.5 text-sm font-medium text-red-600">
                                {{ $message }}
                            </p>
                            @enderror

                        </div>

                    </div>

                    <div>

                        <label class="dt-label">
                            Consulta relacionada
                        </label>

                        <select
                            wire:model="clinical_document_consultation_id"
                            class="dt-select">

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
                                {{ $consultation->consultation_at->format('d/m/Y H:i') }}
                                @if ($consultation->reason)
                                — {{ $consultation->reason }}
                                @endif
                            </option>

                            @endforeach

                        </select>

                        <p class="mt-1.5 text-xs leading-5 text-slate-500">
                            Opcional. Permite relacionar el archivo con una consulta específica del paciente.
                        </p>

                        @error('clinical_document_consultation_id')
                        <p class="mt-1.5 text-sm font-medium text-red-600">
                            {{ $message }}
                        </p>
                        @enderror

                    </div>

                    <div>

                        <label class="dt-label">
                            Notas
                        </label>

                        <textarea
                            wire:model="clinical_document_notes"
                            rows="4"
                            maxlength="5000"
                            placeholder="Información adicional sobre el documento..."
                            class="dt-textarea min-h-28 resize-y"></textarea>

                        @error('clinical_document_notes')
                        <p class="mt-1.5 text-sm font-medium text-red-600">
                            {{ $message }}
                        </p>
                        @enderror

                    </div>

                </div>

                <div
                    class="flex shrink-0 flex-col-reverse gap-3
                               border-t border-slate-200/80
                               bg-slate-50/70 px-5 py-4
                               sm:flex-row sm:justify-end sm:px-6">

                    <button
                        type="button"
                        wire:click="closeClinicalDocumentModal"
                        wire:loading.attr="disabled"
                        class="dt-btn dt-btn-secondary">
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="saveClinicalDocument,clinical_document_file"
                        class="inline-flex items-center justify-center gap-2
                                   rounded-xl bg-gradient-to-r from-blue-600 to-violet-600
                                   px-5 py-2.5 text-sm font-bold text-white
                                   shadow-[0_8px_20px_rgba(79,70,229,0.24)]
                                   hover:-translate-y-0.5 hover:from-blue-700 hover:to-violet-700
                                   disabled:pointer-events-none disabled:opacity-50">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            class="h-4.5 w-4.5">
                            <path d="M12 16V4M8 8l4-4 4 4" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M5 12H4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-5a2 2 0 0 0-2-2h-1" />
                        </svg>

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