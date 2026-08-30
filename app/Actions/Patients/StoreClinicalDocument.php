<?php

namespace App\Actions\Patients;

use App\Models\ClinicalDocument;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class StoreClinicalDocument
{
    public function handle(
        Patient $patient,
        UploadedFile $file,
        string $title,
        string $category = ClinicalDocument::CATEGORY_GENERAL,
        ?string $documentDate = null,
        ?string $notes = null,
        ?Consultation $consultation = null,
        ?User $uploadedBy = null,
    ): ClinicalDocument {
        $tenant = app(TenantContext::class)
            ->requireTenant();

        $this->ensurePatientBelongsToTenant(
            $patient,
            $tenant->id
        );

        $this->ensureUploaderBelongsToTenant(
            $uploadedBy,
            $tenant->id
        );

        $this->validateInput(
            $file,
            $title,
            $documentDate,
            $notes
        );

        $this->ensureValidCategory(
            $category
        );

        $this->ensureConsultationBelongsToPatient(
            $patient,
            $consultation
        );

        $disk = config(
            'clinical.documents.disk',
            'local'
        );

        $directory = sprintf(
            'clinical-documents/%s',
            $patient->uuid
        );

        $extension = $file->extension();

        $storedName = (string) Str::uuid();

        if ($extension !== '') {
            $storedName .= '.' . $extension;
        }

        $path = Storage::disk($disk)->putFileAs(
            $directory,
            $file,
            $storedName
        );

        if ($path === false) {
            throw new RuntimeException(
                'The clinical document could not be stored.'
            );
        }

        try {
            return DB::transaction(
                function () use (
                    $patient,
                    $file,
                    $title,
                    $category,
                    $documentDate,
                    $notes,
                    $consultation,
                    $uploadedBy,
                    $disk,
                    $path
                ): ClinicalDocument {
                    return ClinicalDocument::create([
                        'patient_id' => $patient->id,
                        'consultation_id' =>
                        $consultation?->id,
                        'uploaded_by' =>
                        $uploadedBy?->id,
                        'category' => $category,
                        'title' => $title,
                        'document_date' =>
                        $documentDate,
                        'original_name' =>
                        $file->getClientOriginalName(),
                        'disk' => $disk,
                        'path' => $path,
                        'mime_type' =>
                        $file->getMimeType(),
                        'size_bytes' =>
                        $file->getSize(),
                        'notes' => $notes,
                    ]);
                }
            );
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete(
                $path
            );

            throw $exception;
        }
    }

    private function validateInput(
        UploadedFile $file,
        string $title,
        ?string $documentDate,
        ?string $notes
    ): void {
        Validator::make(
            [
                'file' => $file,
                'title' => $title,
                'document_date' => $documentDate,
                'notes' => $notes,
            ],
            [
                'file' => [
                    'required',
                    'file',
                    'mimes:pdf,jpg,jpeg,png,webp',
                    'max:10240',
                ],
                'title' => [
                    'required',
                    'string',
                    'max:190',
                ],
                'document_date' => [
                    'nullable',
                    'date',
                ],
                'notes' => [
                    'nullable',
                    'string',
                    'max:5000',
                ],
            ]
        )->validate();
    }

    private function ensurePatientBelongsToTenant(
        Patient $patient,
        int $tenantId
    ): void {
        if (
            (int) $patient->tenant_id
            !== $tenantId
        ) {
            throw new InvalidArgumentException(
                'The patient does not belong to the current tenant.'
            );
        }
    }

    private function ensureUploaderBelongsToTenant(
        ?User $uploadedBy,
        int $tenantId
    ): void {
        if ($uploadedBy === null) {
            return;
        }

        if (
            (int) $uploadedBy->tenant_id
            !== $tenantId
        ) {
            throw new InvalidArgumentException(
                'The uploader does not belong to the current tenant.'
            );
        }
    }

    private function ensureValidCategory(
        string $category
    ): void {
        if (
            ! in_array(
                $category,
                ClinicalDocument::categories(),
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid clinical document category.'
            );
        }
    }

    private function ensureConsultationBelongsToPatient(
        Patient $patient,
        ?Consultation $consultation
    ): void {
        if ($consultation === null) {
            return;
        }

        $belongsToPatient = $patient
            ->consultations()
            ->whereKey(
                $consultation->getKey()
            )
            ->exists();

        if (! $belongsToPatient) {
            throw new InvalidArgumentException(
                'The consultation does not belong to the patient.'
            );
        }
    }
}
