<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use LogicException;

class Appointment extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CHECKED_IN = 'checked_in';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_NO_SHOW = 'no_show';

    public const NO_SHOW_GRACE_MINUTES = 15;

    protected $fillable = [
        'tenant_id',
        'uuid',
        'patient_id',
        'doctor_profile_id',
        'starts_at',
        'ends_at',
        'status',
        'reason',
        'notes',
        'cancellation_reason',
        'confirmed_at',
        'checked_in_at',
        'started_at',
        'completed_at',
        'cancelled_at',
        'no_show_at',
        'public_access_token_hash',
        'public_access_token_generated_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'no_show_at' => 'datetime',
            'public_access_token_generated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Appointment $appointment): void {
            if (! $appointment->uuid) {
                $appointment->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctorProfile(): BelongsTo
    {
        return $this->belongsTo(DoctorProfile::class);
    }

    public function consultation(): HasOne
    {
        return $this->hasOne(Consultation::class);
    }

    public function communications(): HasMany
    {
        return $this->hasMany(Communication::class);
    }

    public function confirm(): void
    {
        $this->assertStatusIn([
            self::STATUS_SCHEDULED,
        ]);

        $this->update([
            'status' => self::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);
    }

    public function checkIn(): void
    {
        $this->assertStatusIn([
            self::STATUS_SCHEDULED,
            self::STATUS_CONFIRMED,
        ]);

        $this->update([
            'status' => self::STATUS_CHECKED_IN,
            'checked_in_at' => now(),
        ]);
    }

    public function start(): void
    {
        $this->assertStatusIn([
            self::STATUS_SCHEDULED,
            self::STATUS_CONFIRMED,
            self::STATUS_CHECKED_IN,
        ]);

        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);
    }

    public function complete(): void
    {
        $this->assertStatusIn([
            self::STATUS_IN_PROGRESS,
        ]);

        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function cancel(?string $reason = null): void
    {
        $this->assertStatusIn([
            self::STATUS_SCHEDULED,
            self::STATUS_CONFIRMED,
            self::STATUS_CHECKED_IN,
        ]);

        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason ?: null,
        ]);
    }

    public function markNoShow(): void
    {
        $this->assertStatusIn([
            self::STATUS_SCHEDULED,
            self::STATUS_CONFIRMED,
        ]);

        if (! $this->hasPassedNoShowGracePeriod()) {
            throw new LogicException(
                sprintf(
                    'La cita no puede marcarse como no presentada hasta %d minutos después de su hora de finalización.',
                    self::NO_SHOW_GRACE_MINUTES
                )
            );
        }

        $this->update([
            'status' => self::STATUS_NO_SHOW,
            'no_show_at' => now(),
        ]);
    }

    public function reschedule(
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
    ): void {
        $this->assertStatusIn([
            self::STATUS_SCHEDULED,
            self::STATUS_CONFIRMED,
        ]);

        $this->update([
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,

            /*
             * La confirmación pertenecía al horario anterior.
             * La cita reprogramada debe confirmarse nuevamente.
             */
            'status' => self::STATUS_SCHEDULED,
            'confirmed_at' => null,
            'public_access_token_hash' => null,
            'public_access_token_generated_at' => null,
        ]);
    }

    public function issuePublicAccessToken(): string
    {
        $token = rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');

        $this->update([
            'public_access_token_hash' => hash('sha256', $token),
            'public_access_token_generated_at' => now(),
        ]);

        return $token;
    }

    public function revokePublicAccessToken(): void
    {
        $this->update([
            'public_access_token_hash' => null,
            'public_access_token_generated_at' => null,
        ]);
    }

    public function canConfirm(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    public function canCheckIn(): bool
    {
        return in_array(
            $this->status,
            [
                self::STATUS_SCHEDULED,
                self::STATUS_CONFIRMED,
            ],
            true
        );
    }

    public function canStart(): bool
    {
        return in_array(
            $this->status,
            [
                self::STATUS_SCHEDULED,
                self::STATUS_CONFIRMED,
                self::STATUS_CHECKED_IN,
            ],
            true
        );
    }

    public function canComplete(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function canCancel(): bool
    {
        return in_array(
            $this->status,
            [
                self::STATUS_SCHEDULED,
                self::STATUS_CONFIRMED,
                self::STATUS_CHECKED_IN,
            ],
            true
        );
    }

    public function canMarkNoShow(): bool
    {
        return in_array(
            $this->status,
            [
                self::STATUS_SCHEDULED,
                self::STATUS_CONFIRMED,
            ],
            true
        )
            && $this->hasPassedNoShowGracePeriod();
    }

    public function canReschedule(): bool
    {
        return in_array(
            $this->status,
            [
                self::STATUS_SCHEDULED,
                self::STATUS_CONFIRMED,
            ],
            true
        );
    }

    public function isTerminal(): bool
    {
        return in_array(
            $this->status,
            [
                self::STATUS_COMPLETED,
                self::STATUS_CANCELLED,
                self::STATUS_NO_SHOW,
            ],
            true
        );
    }

    public function hasPassedNoShowGracePeriod(): bool
    {
        if (! $this->ends_at) {
            return false;
        }

        return now()->greaterThanOrEqualTo(
            $this->ends_at
                ->copy()
                ->addMinutes(
                    self::NO_SHOW_GRACE_MINUTES
                )
        );
    }

    private function assertStatusIn(array $allowedStatuses): void
    {
        if (! in_array(
            $this->status,
            $allowedStatuses,
            true
        )) {
            throw new LogicException(
                sprintf(
                    'La cita no puede cambiar desde el estado "%s".',
                    $this->status
                )
            );
        }
    }

    public function canEditDetails(): bool
    {
        return in_array(
            $this->status,
            [
                self::STATUS_SCHEDULED,
                self::STATUS_CONFIRMED,
            ],
            true
        );
    }

    public function updateDetails(
        ?string $reason,
        ?string $notes,
    ): void {
        $this->assertStatusIn([
            self::STATUS_SCHEDULED,
            self::STATUS_CONFIRMED,
        ]);

        $this->update([
            'reason' => $reason ?: null,
            'notes' => $notes ?: null,
        ]);
    }
}
