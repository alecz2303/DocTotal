<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'status',
        'timezone',
        'locale',
        'currency',
        'suspended_at',
        'deletion_due_at',
    ];

    protected $attributes = [
        'status' => 'trial',
        'timezone' => 'America/Mexico_City',
        'locale' => 'es_MX',
        'currency' => 'MXN',
    ];

    protected function casts(): array
    {
        return [
            'suspended_at' => 'datetime',
            'deletion_due_at' => 'datetime',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function doctorProfiles(): HasMany
    {
        return $this->hasMany(DoctorProfile::class);
    }

    public function practiceProfile(): HasOne
    {
        return $this->hasOne(PracticeProfile::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function scheduleExceptions(): HasMany
    {
        return $this->hasMany(ScheduleException::class);
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }
}