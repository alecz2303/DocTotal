<?php

namespace App\Actions\Patients;

use App\Models\ClinicalDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DeleteClinicalDocument
{
    public function handle(
        ClinicalDocument $document
    ): void {
        $disk = Storage::disk(
            $document->disk
        );

        if (
            $disk->exists($document->path)
            && ! $disk->delete($document->path)
        ) {
            throw new RuntimeException(
                'The clinical document file could not be deleted.'
            );
        }

        DB::transaction(
            function () use ($document): void {
                $document->delete();
            }
        );
    }
}
