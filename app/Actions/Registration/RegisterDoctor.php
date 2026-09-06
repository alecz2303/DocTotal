<?php

namespace App\Actions\Registration;

use App\Models\DoctorProfile;
use App\Models\PracticeProfile;
use App\Models\Referral;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Commercial\TrialSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegisterDoctor
{
    public function __construct(
        private TrialSettings $trialSettings
    ) {}

    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data) {

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
                    'referrer_tenant_id' =>
                    $referrer->id,

                    'referred_tenant_id' =>
                    $tenant->id,

                    'referral_code' =>
                    $referrer->referral_code,

                    'status' =>
                    Referral::STATUS_PENDING,
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
