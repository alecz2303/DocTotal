<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use LogicException;

class Consultation extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'tenant_id',
        'uuid',
        'patient_id',
        'doctor_profile_id',
        'appointment_id',
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
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'consultation_at' => 'datetime',
            'completed_at' => 'datetime',

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

            if (! $consultation->status) {
                $consultation->status = self::STATUS_DRAFT;
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

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function diagnoses(): HasMany
    {
        return $this->hasMany(
            ConsultationDiagnosis::class
        );
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(
            Prescription::class
        );
    }

    public function clinicalDocuments(): HasMany
    {
        return $this->hasMany(ClinicalDocument::class);
    }

    public function laboratoryStudies(): HasMany
    {
        return $this->hasMany(LaboratoryStudy::class);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function canEdit(): bool
    {
        return $this->isDraft();
    }

    public function canComplete(): bool
    {
        return $this->isDraft();
    }

    public function complete(): void
    {
        if (! $this->canComplete()) {
            throw new LogicException(
                sprintf(
                    'La consulta no puede finalizarse desde el estado "%s".',
                    $this->status
                )
            );
        }

        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }
}
