<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SalesPartner extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'email',
        'phone',
        'active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SalesPartner $partner): void {
            if (! $partner->uuid) {
                $partner->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function promoCodes(): HasMany
    {
        return $this->hasMany(PromoCode::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(SalesCommission::class);
    }
}
