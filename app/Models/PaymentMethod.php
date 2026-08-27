<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

class PaymentMethod extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const PROVIDER_STRIPE = 'stripe';

    public const TYPE_CARD = 'card';

    protected $fillable = [
        'tenant_id',
        'uuid',
        'provider',
        'provider_payment_method_id',
        'type',
        'brand',
        'last_four',
        'expires_month',
        'expires_year',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'expires_month' => 'integer',
            'expires_year' => 'integer',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(
            function (
                PaymentMethod $paymentMethod
            ): void {
                if (! $paymentMethod->uuid) {
                    $paymentMethod->uuid =
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

    public function isCard(): bool
    {
        return $this->type ===
            self::TYPE_CARD;
    }

    public function isDefault(): bool
    {
        return $this->is_default;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function setAsDefault(): void
    {
        if (! $this->isActive()) {
            throw new LogicException(
                'Un método de pago inactivo no puede establecerse como predeterminado.'
            );
        }

        DB::transaction(
            function (): void {
                PaymentMethod::query()
                    ->withoutGlobalScope(
                        TenantScope::class
                    )
                    ->where(
                        'tenant_id',
                        $this->tenant_id
                    )
                    ->where(
                        'id',
                        '!=',
                        $this->id
                    )
                    ->where(
                        'is_default',
                        true
                    )
                    ->update([
                        'is_default' =>
                        false,
                    ]);

                if (! $this->is_default) {
                    $this->update([
                        'is_default' =>
                        true,
                    ]);
                }
            }
        );

        $this->refresh();
    }

    public function deactivate(): void
    {
        if (! $this->isActive()) {
            return;
        }

        $this->update([
            'is_active' =>
            false,

            'is_default' =>
            false,
        ]);
    }
}
