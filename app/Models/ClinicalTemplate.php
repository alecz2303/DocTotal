<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClinicalTemplate extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'content',
        'active',
        'usage_count',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'active' => 'boolean',
            'usage_count' => 'integer',
        ];
    }

    public function canDelete(): bool
    {
        return $this->usage_count === 0;
    }
}
