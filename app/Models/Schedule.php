<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'doctor_profile_id',
        'day_of_week',
        'start_time',
        'end_time',
        'appointment_duration',
        'buffer_before',
        'buffer_after',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'appointment_duration' => 'integer',
            'buffer_before' => 'integer',
            'buffer_after' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function doctorProfile(): BelongsTo
    {
        return $this->belongsTo(DoctorProfile::class);
    }
}