<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionItem extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'prescription_id',
        'medication_name',
        'presentation',
        'dose',
        'frequency',
        'duration',
        'instructions',
        'sort_order',
    ];

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }
}
