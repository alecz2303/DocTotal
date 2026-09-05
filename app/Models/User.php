<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, HasUuid, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    public const ROLE_OWNER = 'owner';
    public const ROLE_INTERNAL_ADMIN = 'internal_admin';

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function doctorProfile(): HasOne
    {
        return $this->hasOne(DoctorProfile::class);
    }

    /**
     * Envía la notificación personalizada para verificar el correo electrónico.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(
            new \App\Notifications\VerifyEmailNotification()
        );
    }

    /**
     * Envía la notificación personalizada para restablecer la contraseña.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(
            new \App\Notifications\ResetPasswordNotification($token)
        );
    }

    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    public function isInternalAdmin(): bool
    {
        return $this->role === self::ROLE_INTERNAL_ADMIN
            && $this->tenant_id === null;
    }
}
