<?php

namespace App\Actions\Registration;

use App\Models\DoctorProfile;
use App\Models\LegalAcceptance;
use App\Models\PracticeProfile;
use App\Models\Referral;
use App\Models\Tenant;
use App\Models\TenantPromoAttribution;
use App\Models\User;
use App\Services\Commercial\PromoCodeResolver;
use App\Services\Commercial\TrialSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegisterDoctor
{
    public function __construct(
        private TrialSettings $trialSettings,
        private PromoCodeResolver $promoCodeResolver,
    ) {}

    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $promoCode = null;

            if (! empty($data['promo_code'])) {
                $promoCode = $this->promoCodeResolver
                    ->findValid($data['promo_code']);

                if (! $promoCode) {
                    throw ValidationException::withMessages([
                        'promo_code' => 'El código promocional no es válido o ya no está vigente.',
                    ]);
                }
            }

            $tenant = Tenant::create([
                'name' => $data['practice_name'],
                'slug' => $this->generateUniqueSlug($data['practice_name']),
                'status' => 'trial',
                'trial_started_at' => now(),
                'trial_ends_at' => now()->addDays(
                    $this->trialSettings->days()
                ),
            ]);

            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => trim(
                    $data['first_name'] . ' ' . $data['last_name']
                ),
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'owner',
            ]);

            DoctorProfile::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
            ]);

            PracticeProfile::create([
                'tenant_id' => $tenant->id,
                'public_name' => $data['practice_name'],
            ]);

            if (! empty($data['legal_acceptance'])) {
                LegalAcceptance::create([
                    'tenant_id' => $tenant->id,
                    'user_id' => $user->id,
                    'email_snapshot' => $user->email,
                    'terms_version' => $data['legal_acceptance']['terms_version'],
                    'privacy_version' => $data['legal_acceptance']['privacy_version'],
                    'ip_address' => $data['legal_acceptance']['ip_address'] ?? null,
                    'user_agent' => $data['legal_acceptance']['user_agent'] ?? null,
                    'accepted_at' => now(),
                ]);
            }

            if (! empty($data['referral_code'])) {
                $referrer = Tenant::query()
                    ->where(
                        'referral_code',
                        strtoupper(
                            trim($data['referral_code'])
                        )
                    )
                    ->firstOrFail();

                Referral::create([
                    'referrer_tenant_id' => $referrer->id,
                    'referred_tenant_id' => $tenant->id,
                    'referral_code' => $referrer->referral_code,
                    'status' => Referral::STATUS_PENDING,
                ]);
            }

            if ($promoCode) {
                TenantPromoAttribution::create([
                    'tenant_id' => $tenant->id,
                    'promo_code_id' => $promoCode->id,
                    'sales_partner_id' => $promoCode->sales_partner_id,
                    'code_snapshot' => $promoCode->code,
                    'doctor_discount_percent_snapshot' =>
                        $promoCode->doctor_discount_percent,
                    'commission_percent_snapshot' =>
                        $promoCode->commission_percent,
                    'attributed_at' => now(),
                ]);
            }

            return $user;
        });
    }

    private function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 2;

        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
