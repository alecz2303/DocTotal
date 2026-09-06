<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class TenantPromoAttribution extends Model
{
    protected $fillable = [
        'tenant_id',
        'promo_code_id',
        'sales_partner_id',
        'code_snapshot',
        'doctor_discount_percent_snapshot',
        'commission_percent_snapshot',
        'attributed_at',
    ];

    protected function casts(): array
    {
        return [
            'doctor_discount_percent_snapshot' => 'decimal:2',
            'commission_percent_snapshot' => 'decimal:2',
            'attributed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException(
                'La atribución comercial es inmutable y no puede reasignarse.'
            );
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function salesPartner(): BelongsTo
    {
        return $this->belongsTo(SalesPartner::class);
    }
}
