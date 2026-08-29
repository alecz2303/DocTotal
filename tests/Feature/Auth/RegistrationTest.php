<?php

namespace Tests\Feature\Auth;

use App\Actions\Registration\RegisterDoctor;
use App\Models\DoctorProfile;
use App\Models\PracticeProfile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Referral;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_see_registration_page(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('Crear mi cuenta');
    }

    public function test_doctor_can_register(): void
    {
        config([
            'doctotal.trial_days' => 5,
        ]);

        $response = $this->post('/register', [
            'practice_name' => 'Consultorio San José',
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'email' => 'juan@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');

        $this->assertAuthenticated();

        $this->assertDatabaseCount('tenants', 1);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('doctor_profiles', 1);
        $this->assertDatabaseCount('practice_profiles', 1);

        $tenant = Tenant::firstOrFail();
        $user = User::firstOrFail();

        $this->assertSame('Consultorio San José', $tenant->name);
        $this->assertSame('consultorio-san-jose', $tenant->slug);

        $this->assertSame('trial', $tenant->status);
        $this->assertNotNull($tenant->trial_started_at);
        $this->assertNotNull($tenant->trial_ends_at);

        $this->assertEquals(
            5,
            $tenant->trial_started_at->diffInDays($tenant->trial_ends_at)
        );

        $this->assertSame($tenant->id, $user->tenant_id);
        $this->assertSame('owner', $user->role);
        $this->assertSame('Juan Pérez', $user->name);
        $this->assertSame('juan@example.com', $user->email);
    }

    public function test_registration_creates_doctor_profile(): void
    {
        $this->post('/register', [
            'practice_name' => 'Consultorio Test',
            'first_name' => 'María',
            'last_name' => 'López',
            'email' => 'maria@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $tenant = Tenant::firstOrFail();
        $user = User::firstOrFail();

        $doctor = DoctorProfile::withoutGlobalScopes()->firstOrFail();

        $this->assertSame($tenant->id, $doctor->tenant_id);
        $this->assertSame($user->id, $doctor->user_id);

        $this->assertSame('María', $doctor->first_name);
        $this->assertSame('López', $doctor->last_name);
    }

    public function test_registration_creates_practice_profile(): void
    {
        $this->post('/register', [
            'practice_name' => 'Clínica del Centro',
            'first_name' => 'Pedro',
            'last_name' => 'García',
            'email' => 'pedro@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $tenant = Tenant::firstOrFail();

        $practice = PracticeProfile::withoutGlobalScopes()->firstOrFail();

        $this->assertSame($tenant->id, $practice->tenant_id);
        $this->assertSame('Clínica del Centro', $practice->public_name);
    }

    public function test_two_practices_can_have_same_name_and_receive_unique_slugs(): void
    {
        $action = app(RegisterDoctor::class);

        $action->handle([
            'practice_name' => 'Consultorio San José',
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'email' => 'juan@example.com',
            'password' => 'password123',
        ]);

        $action->handle([
            'practice_name' => 'Consultorio San José',
            'first_name' => 'María',
            'last_name' => 'López',
            'email' => 'maria@example.com',
            'password' => 'password123',
        ]);

        $this->assertDatabaseHas('tenants', [
            'slug' => 'consultorio-san-jose',
        ]);

        $this->assertDatabaseHas('tenants', [
            'slug' => 'consultorio-san-jose-2',
        ]);
    }

    public function test_registration_requires_required_fields(): void
    {
        $response = $this->from('/register')
            ->post('/register', []);

        $response
            ->assertRedirect('/register')
            ->assertSessionHasErrors([
                'practice_name',
                'first_name',
                'last_name',
                'email',
                'password',
            ]);

        $this->assertDatabaseCount('tenants', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        $tenant = Tenant::create([
            'name' => 'Consultorio Existente',
            'slug' => 'consultorio-existente',
        ]);

        User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Usuario Existente',
            'email' => 'doctor@example.com',
            'password' => 'password123',
            'role' => 'owner',
        ]);

        $response = $this->from('/register')
            ->post('/register', [
                'practice_name' => 'Nuevo Consultorio',
                'first_name' => 'Nuevo',
                'last_name' => 'Doctor',
                'email' => 'doctor@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response
            ->assertRedirect('/register')
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('tenants', 1);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_registration_with_valid_referral_code_creates_pending_referral(): void
    {
        $referrer = Tenant::create([
            'name' => 'Consultorio Referidor',
            'slug' => 'consultorio-referidor',
        ]);

        $response = $this->post('/register', [
            'practice_name' =>
            'Consultorio Referido',

            'first_name' =>
            'Ana',

            'last_name' =>
            'López',

            'email' =>
            'ana@example.com',

            'password' =>
            'password123',

            'password_confirmation' =>
            'password123',

            'referral_code' =>
            $referrer->referral_code,
        ]);

        $response->assertRedirect('/dashboard');

        $referred = Tenant::query()
            ->where(
                'slug',
                'consultorio-referido'
            )
            ->firstOrFail();

        $referral = Referral::query()
            ->where(
                'referred_tenant_id',
                $referred->id
            )
            ->firstOrFail();

        $this->assertSame(
            $referrer->id,
            $referral->referrer_tenant_id
        );

        $this->assertSame(
            $referred->id,
            $referral->referred_tenant_id
        );

        $this->assertSame(
            $referrer->referral_code,
            $referral->referral_code
        );

        $this->assertSame(
            Referral::STATUS_PENDING,
            $referral->status
        );

        $this->assertNull(
            $referral->qualified_at
        );

        $this->assertNull(
            $referral->qualifying_payment_id
        );
    }

    public function test_registration_without_referral_code_creates_no_referral(): void
    {
        $response = $this->post('/register', [
            'practice_name' =>
            'Consultorio Independiente',

            'first_name' =>
            'Ana',

            'last_name' =>
            'López',

            'email' =>
            'ana@example.com',

            'password' =>
            'password123',

            'password_confirmation' =>
            'password123',
        ]);

        $response->assertRedirect('/dashboard');

        $this->assertDatabaseCount(
            'referrals',
            0
        );
    }

    public function test_registration_rejects_invalid_referral_code(): void
    {
        $response = $this->from('/register')
            ->post('/register', [
                'practice_name' =>
                'Consultorio Referido',

                'first_name' =>
                'Ana',

                'last_name' =>
                'López',

                'email' =>
                'ana@example.com',

                'password' =>
                'password123',

                'password_confirmation' =>
                'password123',

                'referral_code' =>
                'INVALIDO',
            ]);

        $response
            ->assertRedirect('/register')
            ->assertSessionHasErrors(
                'referral_code'
            );

        $this->assertDatabaseCount(
            'tenants',
            0
        );

        $this->assertDatabaseCount(
            'users',
            0
        );

        $this->assertDatabaseCount(
            'referrals',
            0
        );
    }

    public function test_registration_normalizes_lowercase_referral_code(): void
    {
        $referrer = Tenant::create([
            'name' => 'Consultorio Referidor',
            'slug' => 'consultorio-referidor',
        ]);

        $response = $this->post('/register', [
            'practice_name' =>
            'Consultorio Referido',

            'first_name' =>
            'Ana',

            'last_name' =>
            'López',

            'email' =>
            'ana@example.com',

            'password' =>
            'password123',

            'password_confirmation' =>
            'password123',

            'referral_code' =>
            strtolower(
                $referrer->referral_code
            ),
        ]);

        $response->assertRedirect(
            '/dashboard'
        );

        $this->assertDatabaseHas(
            'referrals',
            [
                'referrer_tenant_id' =>
                $referrer->id,

                'referral_code' =>
                $referrer->referral_code,

                'status' =>
                Referral::STATUS_PENDING,
            ]
        );
    }

    public function test_registration_page_preserves_referral_code_from_link(): void
    {
        $referrer = Tenant::create([
            'name' => 'Consultorio Referidor',
            'slug' => 'consultorio-referidor',
        ]);

        $this->get(
            '/register?ref='
                . $referrer->referral_code
        )
            ->assertOk()
            ->assertSee(
                'name="referral_code"',
                false
            )
            ->assertSee(
                'value="'
                    . $referrer->referral_code
                    . '"',
                false
            );
    }
}
