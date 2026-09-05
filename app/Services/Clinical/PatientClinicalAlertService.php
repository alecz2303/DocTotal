<?php

namespace App\Services\Clinical;

use App\Models\Patient;
use App\Models\PatientProblem;
use App\Support\TenantContext;
use Illuminate\Support\Collection;
use LogicException;

class PatientClinicalAlertService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {}

    public function forPatient(Patient $patient): Collection
    {
        $tenant = $this->tenantContext->requireTenant();

        if ((int) $patient->tenant_id !== (int) $tenant->id) {
            throw new LogicException('El paciente no pertenece al tenant clínico activo.');
        }

        $patient->loadMissing([
            'medicalHistory',
            'problems' => fn ($query) => $query
                ->where('status', PatientProblem::STATUS_ACTIVE)
                ->orderByDesc('started_at')
                ->orderByDesc('created_at'),
        ]);

        $alerts = collect();
        $history = $patient->medicalHistory;

        if ($this->hasText($history?->allergies_text)) {
            $alerts->push($this->historyAlert(
                key: 'allergies',
                title: 'Alergias registradas',
                message: (string) $history->allergies_text,
                sourceLabel: 'Antecedentes médicos · Alergias',
                severity: 'critical',
            ));
        }

        if ($this->hasText($history?->current_medications_text)) {
            $alerts->push($this->historyAlert(
                key: 'current_medications',
                title: 'Medicamentos actuales registrados',
                message: (string) $history->current_medications_text,
                sourceLabel: 'Antecedentes médicos · Medicamentos actuales',
                severity: 'warning',
            ));
        }

        if ($this->hasText($history?->chronic_conditions_text)) {
            $alerts->push($this->historyAlert(
                key: 'chronic_conditions',
                title: 'Condiciones crónicas registradas',
                message: (string) $history->chronic_conditions_text,
                sourceLabel: 'Antecedentes médicos · Condiciones crónicas',
                severity: 'warning',
            ));
        }

        foreach ($patient->problems as $problem) {
            $alerts->push([
                'key' => 'active_problem:' . $problem->id,
                'type' => 'active_problem',
                'severity' => 'warning',
                'title' => 'Problema clínico activo',
                'message' => $problem->description,
                'source_type' => PatientProblem::class,
                'source_id' => $problem->id,
                'source_label' => $problem->code
                    ? 'Problema clínico · ' . $problem->code
                    : 'Problema clínico activo',
            ]);
        }

        return $alerts->values();
    }

    private function historyAlert(
        string $key,
        string $title,
        string $message,
        string $sourceLabel,
        string $severity,
    ): array {
        return [
            'key' => $key,
            'type' => $key,
            'severity' => $severity,
            'title' => $title,
            'message' => trim($message),
            'source_type' => 'patient_medical_history',
            'source_id' => null,
            'source_label' => $sourceLabel,
        ];
    }

    private function hasText(?string $value): bool
    {
        return $value !== null && trim($value) !== '';
    }
}
