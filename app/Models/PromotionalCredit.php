<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\CarbonInterface;

class PromotionalCredit extends Model
{
    use BelongsToTenant;

    public const KIND_REFERRER_REWARD = 'referrer_reward';
    public const KIND_REFERRED_DISCOUNT = 'referred_discount';

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_CONSUMED = 'consumed';
    public const STATUS_RESERVED = 'reserved';

    public const REFERRAL_REWARD_AMOUNT = 5000;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'referral_id',
        'payment_id',
        'kind',
        'amount',
        'currency',
        'status',
        'available_at',
        'consumed_at',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'available_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PromotionalCredit $credit): void {
            if (! $credit->uuid) {
                $credit->uuid = (string) Str::uuid();
            }

            if (! $credit->status) {
                $credit->status = self::STATUS_AVAILABLE;
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

    public function referral(): BelongsTo
    {
        return $this->belongsTo(
            Referral::class
        );
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(
            Payment::class
        );
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    public function isConsumed(): bool
    {
        return $this->status === self::STATUS_CONSUMED;
    }

    public function isReserved(): bool
    {
        return $this->status === self::STATUS_RESERVED;
    }

    public function reserve(
        Payment $payment
    ): void {
        if (! $this->isAvailable()) {
            throw new \LogicException(
                sprintf(
                    'El crédito promocional no puede reservarse desde el estado "%s".',
                    $this->status
                )
            );
        }

        $this->update([
            'status' =>
            self::STATUS_RESERVED,

            'payment_id' =>
            $payment->id,
        ]);
    }

    public function consume(
        CarbonInterface $consumedAt
    ): void {
        if (! $this->isReserved()) {
            throw new \LogicException(
                sprintf(
                    'El crédito promocional no puede consumirse desde el estado "%s".',
                    $this->status
                )
            );
        }

        $this->update([
            'status' =>
            self::STATUS_CONSUMED,

            'consumed_at' =>
            $consumedAt,
        ]);
    }

    public function release(): void
    {
        if (! $this->isReserved()) {
            throw new \LogicException(
                sprintf(
                    'El crédito promocional no puede liberarse desde el estado "%s".',
                    $this->status
                )
            );
        }

        $this->update([
            'status' =>
            self::STATUS_AVAILABLE,

            'payment_id' =>
            null,

            'consumed_at' =>
            null,
        ]);
    }
}
