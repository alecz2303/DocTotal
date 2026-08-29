<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'referral_code',
        'status',
        'timezone',
        'locale',
        'currency',
        'suspended_at',
        'deletion_due_at',
        'trial_started_at',
        'trial_ends_at',
        'onboarding_completed_at',
    ];

    protected $attributes = [
        'status' => 'trial',
        'timezone' => 'America/Mexico_City',
        'locale' => 'es_MX',
        'currency' => 'MXN',
    ];

    protected function casts(): array
    {
        return [
            'suspended_at' => 'datetime',
            'deletion_due_at' => 'datetime',
            'trial_started_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'onboarding_completed_at' => 'datetime',
        ];
    }
    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant): void {
            if (! $tenant->referral_code) {
                $tenant->referral_code =
                    self::generateUniqueReferralCode();
            }
        });
    }

    private static function generateUniqueReferralCode(): string
    {
        do {
            $code = Str::upper(
                Str::random(8)
            );
        } while (
            self::query()
            ->where('referral_code', $code)
            ->exists()
        );

        return $code;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function doctorProfiles(): HasMany
    {
        return $this->hasMany(DoctorProfile::class);
    }

    public function practiceProfile(): HasOne
    {
        return $this->hasOne(PracticeProfile::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function scheduleExceptions(): HasMany
    {
        return $this->hasMany(ScheduleException::class);
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(
            PaymentMethod::class
        );
    }

    public function billingCustomers(): HasMany
    {
        return $this->hasMany(
            BillingCustomer::class
        );
    }

    public function referralsGiven(): HasMany
    {
        return $this->hasMany(
            Referral::class,
            'referrer_tenant_id'
        );
    }

    public function referralReceived(): HasOne
    {
        return $this->hasOne(
            Referral::class,
            'referred_tenant_id'
        );
    }

    public function promotionalCredits(): HasMany
    {
        return $this->hasMany(
            PromotionalCredit::class
        );
    }

    public function stripeBillingCustomer(): ?BillingCustomer
    {
        return BillingCustomer::query()
            ->withoutGlobalScope(
                TenantScope::class
            )
            ->where(
                'tenant_id',
                $this->id
            )
            ->where(
                'provider',
                BillingCustomer::PROVIDER_STRIPE
            )
            ->first();
    }

    public function defaultPaymentMethod(): ?PaymentMethod
    {
        return PaymentMethod::query()
            ->withoutGlobalScope(
                TenantScope::class
            )
            ->where(
                'tenant_id',
                $this->id
            )
            ->where(
                'is_active',
                true
            )
            ->where(
                'is_default',
                true
            )
            ->first();
    }

    public function isOnTrial(): bool
    {
        return $this->status === 'trial'
            && $this->trial_ends_at
            && $this->trial_ends_at->isFuture();
    }

    public function trialHasExpired(): bool
    {
        return $this->status === 'trial'
            && $this->trial_ends_at
            && $this->trial_ends_at->isPast();
    }

    public function hasCompletedOnboarding(): bool
    {
        return $this->onboarding_completed_at !== null;
    }

    public function currentSubscription(): ?Subscription
    {
        return Subscription::query()
            ->withoutGlobalScope(
                TenantScope::class
            )
            ->where(
                'tenant_id',
                $this->id
            )
            ->where(function ($query): void {
                $query
                    ->where(function ($query): void {
                        $query
                            ->where(
                                'status',
                                Subscription::STATUS_ACTIVE
                            )
                            ->where(
                                'current_period_starts_at',
                                '<=',
                                now()
                            )
                            ->where(
                                'current_period_ends_at',
                                '>',
                                now()
                            );
                    })
                    ->orWhere(function ($query): void {
                        $query
                            ->where(
                                'status',
                                Subscription::STATUS_PAST_DUE
                            )
                            ->whereNotNull(
                                'grace_ends_at'
                            )
                            ->where(
                                'grace_ends_at',
                                '>',
                                now()
                            );
                    });
            })
            ->latest(
                'current_period_ends_at'
            )
            ->first();
    }

    public function hasCurrentSubscription(): bool
    {
        return $this->currentSubscription() !== null;
    }

    public function hasAccessToService(): bool
    {
        if (
            in_array(
                $this->status,
                [
                    'suspended',
                    'cancelled',
                ],
                true
            )
        ) {
            return false;
        }

        if ($this->isOnTrial()) {
            return true;
        }

        return $this->hasCurrentSubscription();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(
            Payment::class
        );
    }
}
