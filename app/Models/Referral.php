<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use LogicException;

class Referral extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_QUALIFIED = 'qualified';
    public const REWARD_GRANTED = 'granted';

    public const REWARD_MONTHLY_CAP_REACHED =
    'monthly_cap_reached';

    public const MONTHLY_REWARD_LIMIT = 5;

    protected $fillable = [
        'uuid',
        'referrer_tenant_id',
        'referred_tenant_id',
        'referral_code',
        'status',
        'qualifying_payment_id',
        'qualified_at',
        'reward_status',
        'reward_month',
    ];

    protected function casts(): array
    {
        return [
            'qualified_at' => 'datetime',
            'reward_month' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Referral $referral): void {
            if (! $referral->uuid) {
                $referral->uuid = (string) Str::uuid();
            }

            if (! $referral->status) {
                $referral->status = self::STATUS_PENDING;
            }

            if (
                $referral->referrer_tenant_id
                && $referral->referred_tenant_id
                && (int) $referral->referrer_tenant_id
                === (int) $referral->referred_tenant_id
            ) {
                throw new LogicException(
                    'Un tenant no puede referirse a sí mismo.'
                );
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function referrerTenant(): BelongsTo
    {
        return $this->belongsTo(
            Tenant::class,
            'referrer_tenant_id'
        );
    }

    public function referredTenant(): BelongsTo
    {
        return $this->belongsTo(
            Tenant::class,
            'referred_tenant_id'
        );
    }

    public function qualifyingPayment(): BelongsTo
    {
        return $this->belongsTo(
            Payment::class,
            'qualifying_payment_id'
        );
    }

    public function promotionalCredits(): HasMany
    {
        return $this->hasMany(
            PromotionalCredit::class
        );
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isQualified(): bool
    {
        return $this->status === self::STATUS_QUALIFIED;
    }
}
