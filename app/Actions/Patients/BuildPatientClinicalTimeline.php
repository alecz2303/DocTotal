<?php

namespace App\Actions\Patients;

use App\Models\Consultation;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Support\Collection;

class BuildPatientClinicalTimeline
{
    public function handle(Patient $patient): Collection
    {
        $consultationEvents = $patient->consultations()
            ->with([
                'diagnoses',
                'prescriptions.items',
            ])
            ->where(
                'status',
                Consultation::STATUS_COMPLETED
            )
            ->get()
            ->map(function (Consultation $consultation): array {
                return [
                    'type' => 'consultation',
                    'occurred_at' => $consultation->consultation_at,
                    'consultation' => $consultation,
                ];
            });

        $prescriptionEvents = $patient->prescriptions()
            ->with([
                'items',
                'doctorProfile',
            ])
            ->whereNull('consultation_id')
            ->get()
            ->map(function (Prescription $prescription): array {
                return [
                    'type' => 'prescription',
                    'occurred_at' => $prescription->prescribed_at,
                    'prescription' => $prescription,
                ];
            });

        return $consultationEvents
            ->concat($prescriptionEvents)
            ->sortByDesc('occurred_at')
            ->values()
            ->toBase();
    }

    public function diagnoses(Patient $patient): Collection
    {
        return $patient->consultations()
            ->with('diagnoses')
            ->where(
                'status',
                Consultation::STATUS_COMPLETED
            )
            ->orderByDesc('consultation_at')
            ->get()
            ->flatMap(function (Consultation $consultation) {
                return $consultation->diagnoses
                    ->map(function ($diagnosis) use ($consultation): array {
                        return [
                            'diagnosis' => $diagnosis,
                            'consultation' => $consultation,
                            'occurred_at' => $consultation->consultation_at,
                        ];
                    });
            })
            ->groupBy(function (array $entry): string {
                $diagnosis = $entry['diagnosis'];

                if ($diagnosis->code) {
                    return 'code:'
                        . mb_strtoupper(
                            trim($diagnosis->code)
                        );
                }

                return 'description:'
                    . mb_strtolower(
                        trim($diagnosis->description)
                    );
            })
            ->map(function (Collection $entries): array {
                /*
                * Las consultas ya vienen ordenadas
                * de la más reciente a la más antigua.
                */
                $latest = $entries->first();

                return [
                    'code' =>
                    $latest['diagnosis']->code,

                    'description' =>
                    $latest['diagnosis']->description,

                    'count' =>
                    $entries->count(),

                    'last_occurred_at' =>
                    $latest['occurred_at'],

                    'latest_consultation' =>
                    $latest['consultation'],

                    'is_primary' =>
                    $latest['diagnosis']->is_primary,
                ];
            })
            ->sortByDesc('last_occurred_at')
            ->values()
            ->toBase();
    }

    public function treatments(Patient $patient): Collection
    {
        return $patient->prescriptions()
            ->with([
                'items',
                'consultation',
            ])
            ->orderByDesc('prescribed_at')
            ->get()
            ->flatMap(function (Prescription $prescription) {
                return $prescription->items
                    ->map(function ($item) use ($prescription): array {
                        return [
                            'medication_name' =>
                            $item->medication_name,

                            'dose' =>
                            $item->dose,

                            'frequency' =>
                            $item->frequency,

                            'duration' =>
                            $item->duration,

                            'prescribed_at' =>
                            $prescription->prescribed_at,

                            'prescription' =>
                            $prescription,

                            'consultation' =>
                            $prescription->consultation,
                        ];
                    });
            })
            ->groupBy(function (array $entry): string {
                return implode('|', [
                    mb_strtolower(
                        trim(
                            $entry['medication_name'] ?? ''
                        )
                    ),
                    mb_strtolower(
                        trim(
                            $entry['dose'] ?? ''
                        )
                    ),
                    mb_strtolower(
                        trim(
                            $entry['frequency'] ?? ''
                        )
                    ),
                    mb_strtolower(
                        trim(
                            $entry['duration'] ?? ''
                        )
                    ),
                ]);
            })
            ->map(function (Collection $entries): array {
                $latest = $entries
                    ->sortByDesc('prescribed_at')
                    ->first();

                return [
                    'medication_name' =>
                    $latest['medication_name'],

                    'dose' =>
                    $latest['dose'],

                    'frequency' =>
                    $latest['frequency'],

                    'duration' =>
                    $latest['duration'],

                    'count' =>
                    $entries->count(),

                    'last_prescribed_at' =>
                    $latest['prescribed_at'],

                    'latest_prescription' =>
                    $latest['prescription'],

                    'latest_consultation' =>
                    $latest['consultation'],
                ];
            })
            ->sortByDesc('last_prescribed_at')
            ->values()
            ->toBase();
    }
}
