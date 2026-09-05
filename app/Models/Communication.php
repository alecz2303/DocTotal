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
    public const STATUS_PROCESSING = 'processing';
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
        'processing_started_at',
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
            'processing_started_at' => 'datetime',
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

    public function markProcessing(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'processing_started_at' => now(),
            'next_attempt_at' => null,
        ]);

        $this->increment('attempt_count');
    }

    public function markSent(): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'processing_started_at' => null,
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
            'processing_started_at' => null,
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
            'processing_started_at' => null,
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

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
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
