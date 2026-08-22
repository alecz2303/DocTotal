<?php

namespace App\Actions\Registration;

use App\Models\DoctorProfile;
use App\Models\PracticeProfile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegisterDoctor
{
    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data) {

            $tenant = Tenant::create([
                'name' => $data['practice_name'],
                'slug' => $this->generateUniqueSlug($data['practice_name']),
                'status' => 'trial',
                'trial_started_at' => now(),
                'trial_ends_at' => now()->addDays(
                    config('doctotal.trial_days')
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
