<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientProblem extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_RESOLVED = 'resolved';

    protected $fillable = [
        'tenant_id',
        'patient_id',
        'code',
        'description',
        'status',
        'started_at',
        'resolved_at',
        'notes',
    ];

    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'resolved_at' => 'date',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    public function resolve(?string $date = null): void
    {
        $this->update([
            'status' => self::STATUS_RESOLVED,
            'resolved_at' => $date ?? now()->toDateString(),
        ]);
    }

    public function reopen(): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'resolved_at' => null,
        ]);
    }
}
