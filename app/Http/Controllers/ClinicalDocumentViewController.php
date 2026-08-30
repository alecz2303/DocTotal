<?php

namespace App\Http\Controllers;

use App\Models\ClinicalDocument;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClinicalDocumentViewController extends Controller
{
    public function __invoke(
        ClinicalDocument $clinicalDocument
    ): StreamedResponse {
        $disk = Storage::disk(
            $clinicalDocument->disk
        );

        if (
            ! $disk->exists(
                $clinicalDocument->path
            )
        ) {
            abort(404);
        }

        return $disk->response(
            $clinicalDocument->path,
            $clinicalDocument->original_name,
            [
                'Content-Type' =>
                $clinicalDocument->mime_type
                    ?: 'application/octet-stream',

                'Content-Disposition' =>
                'inline; filename="' .
                    addslashes(
                        $clinicalDocument->original_name
                    ) .
                    '"',
            ]
        );
    }
}
