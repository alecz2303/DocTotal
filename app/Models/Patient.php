<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use BelongsToTenant, HasUuid, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'first_name',
        'last_name',
        'second_last_name',
        'birth_date',
        'sex',
        'email',
        'phone',
        'whatsapp',
        'blood_type',
        'address_line_1',
        'address_line_2',
        'neighborhood',
        'city',
        'state',
        'postal_code',
        'country',
        'notes',
        'allow_email_communications',
        'allow_whatsapp_communications',
        'allow_sms_communications',
    ];

    protected $attributes = [
        'country' => 'MX',
        'allow_email_communications' => true,
        'allow_whatsapp_communications' => true,
        'allow_sms_communications' => true,
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'allow_email_communications' => 'boolean',
            'allow_whatsapp_communications' => 'boolean',
            'allow_sms_communications' => 'boolean',
        ];
    }

    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(PatientEmergencyContact::class);
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
    }

    public function clinicalDocuments(): HasMany
    {
        return $this->hasMany(ClinicalDocument::class);
    }

    public function laboratoryStudies(): HasMany
    {
        return $this->hasMany(LaboratoryStudy::class);
    }

    public function medicalHistory(): HasOne
    {
        return $this->hasOne(PatientMedicalHistory::class);
    }

    public function problems(): HasMany
    {
        return $this->hasMany(PatientProblem::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function communications(): HasMany
    {
        return $this->hasMany(Communication::class);
    }
}
