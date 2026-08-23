<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Consultation extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'uuid',
        'patient_id',
        'doctor_profile_id',
        'consultation_at',
        'reason',
        'subjective',
        'objective',
        'assessment',
        'plan',
        'weight_kg',
        'height_cm',
        'systolic_bp',
        'diastolic_bp',
        'heart_rate',
        'respiratory_rate',
        'temperature_c',
        'oxygen_saturation',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'consultation_at' => 'datetime',
            'weight_kg' => 'decimal:2',
            'height_cm' => 'decimal:2',
            'temperature_c' => 'decimal:1',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Consultation $consultation): void {
            if (! $consultation->uuid) {
                $consultation->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctorProfile(): BelongsTo
    {
        return $this->belongsTo(DoctorProfile::class);
    }

    public function diagnoses(): HasMany
    {
        return $this->hasMany(ConsultationDiagnosis::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }
}
