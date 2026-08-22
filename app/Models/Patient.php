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
    ];

    protected $attributes = [
        'country' => 'MX',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(PatientEmergencyContact::class);
    }

    public function medicalHistory(): HasOne
    {
        return $this->hasOne(PatientMedicalHistory::class);
    }
}