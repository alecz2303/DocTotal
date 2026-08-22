<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientMedicalHistory extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'patient_id',
        'allergies_text',
        'current_medications_text',
        'chronic_conditions_text',
        'surgeries_text',
        'family_history_text',
        'personal_history_text',
        'gynecological_history_text',
        'habits_text',
        'other_notes',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}