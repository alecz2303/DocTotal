<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaboratoryResult extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'laboratory_study_id',
        'parameter_name',
        'value',
        'unit',
        'reference_range',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    public function study(): BelongsTo
    {
        return $this->belongsTo(LaboratoryStudy::class, 'laboratory_study_id');
    }
}
