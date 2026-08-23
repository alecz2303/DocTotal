<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicationCatalog extends Model
{
    protected $fillable = [
        'code',
        'source_hash',
        'name',
        'presentation',
        'therapeutic_group',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }
}
