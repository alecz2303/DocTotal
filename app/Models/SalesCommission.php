<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use LogicException;

class SalesCommission extends Model
{
    public const STATUS_ACCRUED = 'accrued';
    public const STATUS_PAID = 'paid';
    public const STATUS_REVERTED = 'reverted';

    protected $fillable = [
        'uuid',
        'sales_partner_id',
        'promo_code_id',
        'tenant_id',
        'payment_id',
        'status',
        'base_amount',
        'commission_percent',
        'commission_amount',
        'currency',
        'accrued_at',
        'paid_at',
        'reverted_at',
    ];

    protected function casts(): array
    {
        return [
            'base_amount' => 'integer',
            'commission_percent' => 'decimal:2',
            'commission_amount' => 'integer',
            'accrued_at' => 'datetime',
            'paid_at' => 'datetime',
            'reverted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SalesCommission $commission): void {
            if (! $commission->uuid) {
                $commission->uuid = (string) Str::uuid();
            }
        });

        static::updating(function (SalesCommission $commission): void {
            $immutable = [
                'sales_partner_id',
                'promo_code_id',
                'tenant_id',
                'payment_id',
                'base_amount',
                'commission_percent',
                'commission_amount',
                'currency',
                'accrued_at',
            ];

            foreach ($immutable as $field) {
                if ($commission->isDirty($field)) {
                    throw new LogicException(
                        'Los datos económicos de una comisión son inmutables.'
                    );
                }
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

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class)
            ->withoutGlobalScope(TenantScope::class);
    }
}
