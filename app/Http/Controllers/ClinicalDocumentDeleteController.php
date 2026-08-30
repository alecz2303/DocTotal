<?php

namespace App\Http\Controllers;

use App\Actions\Patients\DeleteClinicalDocument;
use App\Models\ClinicalDocument;
use Illuminate\Http\RedirectResponse;

class ClinicalDocumentDeleteController extends Controller
{
    public function __invoke(
        ClinicalDocument $clinicalDocument,
        DeleteClinicalDocument $deleteClinicalDocument
    ): RedirectResponse {
        $patient = $clinicalDocument->patient;

        $deleteClinicalDocument->handle(
            $clinicalDocument
        );

        return redirect()
            ->route(
                'patients.show',
                $patient
            )
            ->with(
                'status',
                'Documento clínico eliminado correctamente.'
            );
    }
}
