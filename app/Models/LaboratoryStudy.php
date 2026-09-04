<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LaboratoryStudy extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'patient_id',
        'consultation_id',
        'name',
        'study_date',
        'laboratory_name',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'study_date' => 'date',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(LaboratoryResult::class)
            ->orderBy('position')
            ->orderBy('id');
    }
}
