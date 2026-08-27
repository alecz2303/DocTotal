<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BillingCustomer extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const PROVIDER_STRIPE = 'stripe';

    protected $fillable = [
        'tenant_id',
        'uuid',
        'provider',
        'provider_customer_id',
    ];

    protected static function booted(): void
    {
        static::creating(
            function (
                BillingCustomer $billingCustomer
            ): void {
                if (! $billingCustomer->uuid) {
                    $billingCustomer->uuid =
                        (string) Str::uuid();
                }
            }
        );
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            Tenant::class
        );
    }

    public function isStripe(): bool
    {
        return $this->provider ===
            self::PROVIDER_STRIPE;
    }
}
