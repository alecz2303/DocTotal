<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalAcceptance extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'email_snapshot',
        'terms_version',
        'privacy_version',
        'ip_address',
        'user_agent',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
        ];
    }
}
