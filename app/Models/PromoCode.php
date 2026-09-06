<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PromoCode extends Model
{
    public const SCOPE_FIRST_SUCCESSFUL_PAYMENT = 'first_successful_payment';

    protected $fillable = [
        'uuid',
        'sales_partner_id',
        'code',
        'active',
        'doctor_discount_percent',
        'commission_percent',
        'commission_scope',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'doctor_discount_percent' => 'decimal:2',
            'commission_percent' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PromoCode $promoCode): void {
            if (! $promoCode->uuid) {
                $promoCode->uuid = (string) Str::uuid();
            }

            $promoCode->code = strtoupper(trim($promoCode->code));
        });

        static::updating(function (PromoCode $promoCode): void {
            if ($promoCode->isDirty('code')) {
                $promoCode->code = strtoupper(trim($promoCode->code));
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function salesPartner(): BelongsTo
    {
        return $this->belongsTo(SalesPartner::class);
    }

    public function attributions(): HasMany
    {
        return $this->hasMany(TenantPromoAttribution::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(SalesCommission::class);
    }

    public function isValidAt(?\DateTimeInterface $moment = null): bool
    {
        $moment ??= now();

        if (! $this->active || ! $this->salesPartner?->active) {
            return false;
        }

        if ($this->starts_at && $moment < $this->starts_at) {
            return false;
        }

        if ($this->ends_at && $moment > $this->ends_at) {
            return false;
        }

        return true;
    }
}
