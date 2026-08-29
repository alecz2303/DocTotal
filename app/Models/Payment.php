<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Carbon\CarbonInterface;
use LogicException;
use App\Models\Scopes\TenantScope;

class Payment extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCEEDED = 'succeeded';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELED = 'canceled';

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'uuid',
        'amount',
        'currency',
        'status',
        'attempted_at',
        'paid_at',
        'failed_at',
        'failure_code',
        'failure_message',
        'provider',
        'provider_payment_id',
        'idempotency_key',
        'billing_cycle',
        'gross_amount',
        'referral_discount_amount',
        'promotional_credit_amount',
        'promotional_credits_released_at',
        'canceled_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'attempted_at' => 'datetime',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
            'gross_amount' => 'integer',
            'referral_discount_amount' => 'integer',
            'promotional_credit_amount' => 'integer',
            'promotional_credits_released_at' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }

    public function succeed(
        CarbonInterface $paidAt,
        ?string $providerPaymentId = null,
    ): void {
        if (! $this->isPending()) {
            throw new LogicException(
                sprintf(
                    'El pago no puede completarse desde el estado "%s".',
                    $this->status
                )
            );
        }

        $this->update([
            'status' =>
            self::STATUS_SUCCEEDED,

            'paid_at' =>
            $paidAt,

            'failed_at' =>
            null,

            'failure_code' =>
            null,

            'failure_message' =>
            null,

            'provider_payment_id' =>
            $providerPaymentId
                ?? $this->provider_payment_id,
        ]);
    }

    public function fail(
        CarbonInterface $failedAt,
        ?string $failureCode = null,
        ?string $failureMessage = null,
        ?string $providerPaymentId = null,
    ): void {
        if (! $this->isPending()) {
            throw new LogicException(
                sprintf(
                    'El pago no puede fallar desde el estado "%s".',
                    $this->status
                )
            );
        }

        $this->update([
            'status' =>
            self::STATUS_FAILED,

            'paid_at' =>
            null,

            'failed_at' =>
            $failedAt,

            'failure_code' =>
            $failureCode,

            'failure_message' =>
            $failureMessage,

            'provider_payment_id' =>
            $providerPaymentId
                ?? $this->provider_payment_id,
        ]);
    }

    public function cancel(
        CarbonInterface $canceledAt,
        ?string $providerPaymentId = null,
    ): void {
        if (! $this->isPending()) {
            throw new LogicException(
                sprintf(
                    'El pago no puede cancelarse desde el estado "%s".',
                    $this->status
                )
            );
        }

        $this->update([
            'status' =>
            self::STATUS_CANCELED,

            'paid_at' =>
            null,

            'failed_at' =>
            null,

            'canceled_at' =>
            $canceledAt,

            'failure_code' =>
            null,

            'failure_message' =>
            null,

            'provider_payment_id' =>
            $providerPaymentId
                ?? $this->provider_payment_id,
        ]);
    }

    public function isCanceled(): bool
    {
        return $this->status ===
            self::STATUS_CANCELED;
    }

    protected static function booted(): void
    {
        static::creating(function (Payment $payment): void {
            if (! $payment->uuid) {
                $payment->uuid = (string) Str::uuid();
            }

            if (! $payment->status) {
                $payment->status = self::STATUS_PENDING;
            }
        });
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

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(
            Subscription::class
        )->withoutGlobalScope(
            TenantScope::class
        );
    }

    public function isPending(): bool
    {
        return $this->status ===
            self::STATUS_PENDING;
    }

    public function isSucceeded(): bool
    {
        return $this->status ===
            self::STATUS_SUCCEEDED;
    }

    public function isFailed(): bool
    {
        return $this->status ===
            self::STATUS_FAILED;
    }

    public function totalDiscountAmount(): int
    {
        return
            $this->referral_discount_amount
            + $this->promotional_credit_amount;
    }

    public function contractualAmount(): int
    {
        return $this->gross_amount
            ?? $this->amount;
    }
}
