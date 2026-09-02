<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Communication extends Model
{
    use BelongsToTenant;

    public const CHANNEL_EMAIL = 'email';
    public const CHANNEL_WHATSAPP = 'whatsapp';
    public const CHANNEL_SMS = 'sms';

    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    public const TYPE_APPOINTMENT_CONFIRMATION = 'appointment_confirmation';
    public const TYPE_APPOINTMENT_REMINDER = 'appointment_reminder';


    protected $fillable = [
        'tenant_id',
        'patient_id',
        'appointment_id',
        'type',
        'channel',
        'recipient',
        'subject',
        'body',
        'status',
        'idempotency_key',
        'scheduled_for',
        'sent_at',
        'failed_at',
        'attempt_count',
        'last_error',
        'metadata',
        'next_attempt_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'attempt_count' => 0,
    ];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
            'attempt_count' => 'integer',
            'metadata' => 'array',
            'next_attempt_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function markSent(): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
            'failed_at' => null,
            'next_attempt_at' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'last_error' => null,
        ]);
    }

    public function markFailed(
        string $error,
        ?\DateTimeInterface $nextAttemptAt = null,
    ): void {
        $this->update([
            'status' => self::STATUS_FAILED,
            'failed_at' => now(),
            'next_attempt_at' => $nextAttemptAt,
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'last_error' => $error,
        ]);
    }

    public function markCancelled(
        ?string $reason = null
    ): void {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'next_attempt_at' => null,
        ]);
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function registerAttempt(): void
    {
        $this->increment('attempt_count');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }
}
