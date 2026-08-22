<?php

namespace Tests\Feature\Models;

use App\Models\DoctorProfile;
use App\Models\PracticeProfile;
use App\Models\Schedule;
use App\Models\Specialty;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Support\TenantContext;

class CoreModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_generates_uuid_automatically(): void
    {
        $tenant = Tenant::create([
            'name' => 'Consultorio Test',
            'slug' => 'consultorio-test',
        ]);

        $this->assertNotNull($tenant->uuid);
        $this->assertSame('trial', $tenant->status);
        $this->assertSame('America/Mexico_City', $tenant->timezone);
        $this->assertSame('es_MX', $tenant->locale);
        $this->assertSame('MXN', $tenant->currency);
    }

    public function test_user_belongs_to_tenant(): void
    {
        $tenant = Tenant::create([
            'name' => 'Consultorio Test',
            'slug' => 'consultorio-test',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Dr. Test',
            'email' => 'doctor@example.com',
            'password' => 'secret123',
            'role' => 'owner',
        ]);

        $this->assertTrue($user->tenant->is($tenant));
        $this->assertNotNull($user->uuid);
    }

    public function test_doctor_profile_relationships_work(): void
    {
        $tenant = Tenant::create([
            'name' => 'Consultorio Test',
            'slug' => 'consultorio-test',
        ]);

        app(TenantContext::class)->set($tenant);

        $specialty = Specialty::create([
            'name' => 'Medicina General',
            'slug' => 'medicina-general',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Dr. Test',
            'email' => 'doctor@example.com',
            'password' => 'secret123',
            'role' => 'owner',
        ]);

        $doctor = DoctorProfile::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'specialty_id' => $specialty->id,
            'first_name' => 'Doctor',
            'last_name' => 'Test',
        ]);

        $this->assertTrue($doctor->tenant->is($tenant));
        $this->assertTrue($doctor->user->is($user));
        $this->assertTrue($doctor->specialty->is($specialty));
        $this->assertTrue($user->doctorProfile->is($doctor));
    }

    public function test_tenant_has_practice_profile_and_schedule(): void
    {
        $tenant = Tenant::create([
            'name' => 'Consultorio Test',
            'slug' => 'consultorio-test',
        ]);

        app(TenantContext::class)->set($tenant);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Dr. Test',
            'email' => 'doctor@example.com',
            'password' => 'secret123',
            'role' => 'owner',
        ]);

        $doctor = DoctorProfile::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'first_name' => 'Doctor',
            'last_name' => 'Test',
        ]);

        $practice = PracticeProfile::create([
            'tenant_id' => $tenant->id,
            'public_name' => 'Consultorio Test',
        ]);

        $schedule = Schedule::create([
            'tenant_id' => $tenant->id,
            'doctor_profile_id' => $doctor->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '14:00',
            'appointment_duration' => 30,
        ]);

        $this->assertTrue($tenant->practiceProfile->is($practice));
        $this->assertTrue($doctor->schedules->contains($schedule));
        $this->assertTrue($schedule->doctorProfile->is($doctor));
    }
}
