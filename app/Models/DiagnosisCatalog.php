<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiagnosisCatalog extends Model
{
    protected $fillable = [
        'code',
        'description',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }
}
