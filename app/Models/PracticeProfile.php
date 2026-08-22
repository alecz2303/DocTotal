<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PracticeProfile extends Model
{
    protected $fillable = [
        'tenant_id',
        'public_name',
        'description',
        'phone',
        'whatsapp',
        'email',
        'address_line_1',
        'address_line_2',
        'neighborhood',
        'city',
        'state',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'website_enabled',
        'booking_enabled',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'website_enabled' => 'boolean',
            'booking_enabled' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}