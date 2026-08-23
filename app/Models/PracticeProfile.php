<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PracticeProfile extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'public_name',
        'legal_name',
        'tax_id',
        'description',
        'logo_path',
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
        'print_footer',
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
}
