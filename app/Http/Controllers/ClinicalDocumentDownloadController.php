<?php

namespace App\Http\Controllers;

use App\Models\ClinicalDocument;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClinicalDocumentDownloadController extends Controller
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

        return $disk->download(
            $clinicalDocument->path,
            $clinicalDocument->original_name
        );
    }
}
