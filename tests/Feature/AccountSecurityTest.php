<?php

namespace Tests\Feature;

use App\Models\AuditEvent;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_security_settings(): void
    {
        $this->get(route('settings.security'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_security_settings(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('settings.security'))
            ->assertOk()
            ->assertSee('Seguridad de la cuenta')
            ->assertSee('Cambiar contraseña');
    }

    public function test_suspended_tenant_can_still_access_security_settings(): void
    {
        $user = $this->createUser([
            'status' => 'suspended',
            'suspended_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('settings.security'))
            ->assertOk();
    }

    public function test_user_can_change_password_with_current_password(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        Livewire::test('pages::settings.security')
            ->set('current_password', 'CurrentPassword123!')
            ->set('password', 'NewPassword456!')
            ->set('password_confirmation', 'NewPassword456!')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $this->assertTrue(
            Hash::check('NewPassword456!', $user->fresh()->password)
        );
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        Livewire::test('pages::settings.security')
            ->set('current_password', 'WrongPassword123!')
            ->set('password', 'NewPassword456!')
            ->set('password_confirmation', 'NewPassword456!')
            ->call('updatePassword')
            ->assertHasErrors(['current_password']);

        $this->assertTrue(
            Hash::check('CurrentPassword123!', $user->fresh()->password)
        );
    }

    public function test_password_confirmation_must_match(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        Livewire::test('pages::settings.security')
            ->set('current_password', 'CurrentPassword123!')
            ->set('password', 'NewPassword456!')
            ->set('password_confirmation', 'DifferentPassword789!')
            ->call('updatePassword')
            ->assertHasErrors(['password']);
    }

    public function test_password_change_is_audited_without_credentials(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        Livewire::test('pages::settings.security')
            ->set('current_password', 'CurrentPassword123!')
            ->set('password', 'NewPassword456!')
            ->set('password_confirmation', 'NewPassword456!')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $event = AuditEvent::query()
            ->where('action', 'account.password.updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($event);
        $this->assertSame($user->id, $event->user_id);

        $payload = json_encode($event->toArray());

        $this->assertStringNotContainsString('CurrentPassword123!', $payload);
        $this->assertStringNotContainsString('NewPassword456!', $payload);
    }

    private function createUser(array $tenantOverrides = []): User
    {
        $tenant = Tenant::query()->create(array_merge([
            'name' => 'Tenant Security Test',
            'slug' => 'tenant-security-'.uniqid(),
            'status' => 'trial',
            'onboarding_completed_at' => now(),
        ], $tenantOverrides));

        app(TenantContext::class)->set($tenant);

        return User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => User::ROLE_OWNER,
            'password' => Hash::make('CurrentPassword123!'),
        ]);
    }
}
