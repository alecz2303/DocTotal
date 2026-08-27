<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use LogicException;

class Subscription extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const BILLING_CYCLE_MONTHLY = 'monthly';
    public const BILLING_CYCLE_YEARLY = 'yearly';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAST_DUE = 'past_due';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tenant_id',
        'uuid',
        'billing_cycle',
        'pending_billing_cycle',
        'status',
        'starts_at',
        'current_period_starts_at',
        'current_period_ends_at',
        'next_billing_at',
        'cancel_at_period_end',
        'cancelled_at',
        'past_due_since',
        'grace_ends_at',
        'next_retry_at',
        'retry_count',
        'billing_amount',
        'billing_currency',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'current_period_starts_at' => 'datetime',
            'current_period_ends_at' => 'datetime',
            'next_billing_at' => 'datetime',
            'cancel_at_period_end' => 'boolean',
            'cancelled_at' => 'datetime',
            'past_due_since' => 'datetime',
            'grace_ends_at' => 'datetime',
            'next_retry_at' => 'datetime',
            'retry_count' => 'integer',
            'billing_amount' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Subscription $subscription): void {
            if (! $subscription->uuid) {
                $subscription->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function isInGracePeriod(): bool
    {
        return $this->isPastDue()
            && $this->grace_ends_at
            && now()->lessThan($this->grace_ends_at);
    }

    public function gracePeriodHasExpired(): bool
    {
        return $this->isPastDue()
            && $this->grace_ends_at
            && now()->greaterThanOrEqualTo($this->grace_ends_at);
    }

    public function retryIsDue(): bool
    {
        return $this->isPastDue()
            && $this->next_retry_at
            && now()->greaterThanOrEqualTo($this->next_retry_at);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isPastDue(): bool
    {
        return $this->status === self::STATUS_PAST_DUE;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function markPastDue(): void
    {
        if (! $this->isActive()) {
            throw new LogicException(
                sprintf(
                    'La suscripción no puede marcarse como vencida desde el estado "%s".',
                    $this->status
                )
            );
        }

        $this->update([
            'status' => self::STATUS_PAST_DUE,
        ]);
    }

    public function reactivate(): void
    {
        if (! $this->isPastDue()) {
            throw new LogicException(
                sprintf(
                    'La suscripción no puede reactivarse desde el estado "%s".',
                    $this->status
                )
            );
        }

        $this->update([
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    public function isMonthly(): bool
    {
        return $this->billing_cycle === self::BILLING_CYCLE_MONTHLY;
    }

    public function isYearly(): bool
    {
        return $this->billing_cycle === self::BILLING_CYCLE_YEARLY;
    }

    public function isCurrent(): bool
    {
        return $this->isActive()
            && now()->greaterThanOrEqualTo($this->current_period_starts_at)
            && now()->lessThan($this->current_period_ends_at);
    }

    public function scheduleCancellation(): void
    {
        if (! $this->isActive()) {
            throw new LogicException(
                sprintf(
                    'La suscripción no puede cancelarse desde el estado "%s".',
                    $this->status
                )
            );
        }

        $this->update([
            'cancel_at_period_end' => true,
            'pending_billing_cycle' => null,
        ]);
    }

    public function cancel(): void
    {
        if ($this->isCancelled()) {
            return;
        }

        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancel_at_period_end' => false,
            'cancelled_at' => now(),
            'next_billing_at' => null,
            'pending_billing_cycle' => null,
        ]);
    }
}
