<?php

namespace Tests\Feature;

use App\Models\AuditEvent;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Fortify;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AccountTwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_settings_show_two_factor_section(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('settings.security'))
            ->assertOk()
            ->assertSee('Verificación en dos pasos')
            ->assertSee('Configurar 2FA');
    }

    public function test_suspended_tenant_can_manage_two_factor_security(): void
    {
        $user = $this->createUser([
            'status' => 'suspended',
            'suspended_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('settings.security'))
            ->assertOk()
            ->assertSee('Verificación en dos pasos');
    }

    public function test_current_password_is_required_to_start_two_factor_setup(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        Livewire::test('pages::settings.security')
            ->set('two_factor_password', 'WrongPassword123!')
            ->call('enableTwoFactorAuthentication')
            ->assertHasErrors(['two_factor_password']);

        $user->refresh();

        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_recovery_codes);
        $this->assertNull($user->two_factor_confirmed_at);
    }

    public function test_user_can_start_two_factor_setup_and_generate_qr_data(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        Livewire::test('pages::settings.security')
            ->set('two_factor_password', 'CurrentPassword123!')
            ->call('enableTwoFactorAuthentication')
            ->assertHasNoErrors()
            ->assertSet('showingQrCode', true);

        $user->refresh();

        $this->assertNotNull($user->two_factor_secret);
        $this->assertNotNull($user->two_factor_recovery_codes);
        $this->assertNull($user->two_factor_confirmed_at);
        $this->assertNotEmpty($user->twoFactorQrCodeSvg());
    }

    public function test_invalid_totp_code_does_not_confirm_two_factor_authentication(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        Livewire::test('pages::settings.security')
            ->set('two_factor_password', 'CurrentPassword123!')
            ->call('enableTwoFactorAuthentication')
            ->set('two_factor_code', '000000')
            ->call('confirmTwoFactorAuthentication')
            ->assertHasErrors(['two_factor_code']);

        $this->assertNull($user->fresh()->two_factor_confirmed_at);
    }

    public function test_user_can_confirm_two_factor_authentication_with_valid_totp_code(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $component = Livewire::test('pages::settings.security')
            ->set('two_factor_password', 'CurrentPassword123!')
            ->call('enableTwoFactorAuthentication')
            ->assertHasNoErrors();

        $code = $this->currentTotpCode($user->fresh());

        $component
            ->set('two_factor_code', $code)
            ->call('confirmTwoFactorAuthentication')
            ->assertHasNoErrors()
            ->assertSet('showingRecoveryCodes', true);

        $this->assertNotNull($user->fresh()->two_factor_confirmed_at);
        $this->assertTrue($user->fresh()->hasEnabledTwoFactorAuthentication());
    }

    public function test_user_can_regenerate_recovery_codes_after_password_confirmation(): void
    {
        $user = $this->createConfirmedTwoFactorUser();
        $oldCodes = $user->recoveryCodes();

        $this->actingAs($user);

        Livewire::test('pages::settings.security')
            ->set('recovery_codes_password', 'CurrentPassword123!')
            ->call('regenerateRecoveryCodes')
            ->assertHasNoErrors()
            ->assertSet('showingRecoveryCodes', true);

        $newCodes = $user->fresh()->recoveryCodes();

        $this->assertCount(8, $newCodes);
        $this->assertNotSame($oldCodes, $newCodes);
    }

    public function test_wrong_password_cannot_regenerate_recovery_codes(): void
    {
        $user = $this->createConfirmedTwoFactorUser();
        $oldCodes = $user->recoveryCodes();

        $this->actingAs($user);

        Livewire::test('pages::settings.security')
            ->set('recovery_codes_password', 'WrongPassword123!')
            ->call('regenerateRecoveryCodes')
            ->assertHasErrors(['recovery_codes_password']);

        $this->assertSame($oldCodes, $user->fresh()->recoveryCodes());
    }

    public function test_user_can_disable_two_factor_authentication_after_password_confirmation(): void
    {
        $user = $this->createConfirmedTwoFactorUser();
        $this->actingAs($user);

        Livewire::test('pages::settings.security')
            ->set('disable_two_factor_password', 'CurrentPassword123!')
            ->call('disableTwoFactorAuthentication')
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_recovery_codes);
        $this->assertNull($user->two_factor_confirmed_at);
    }

    public function test_login_requires_two_factor_challenge_for_confirmed_user(): void
    {
        $user = $this->createConfirmedTwoFactorUser();
        auth()->logout();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'CurrentPassword123!',
        ]);

        $response->assertRedirect(route('two-factor.login'));
        $this->assertGuest();
        $this->assertSame($user->id, session('login.id'));

        $this->get(route('two-factor.login'))
            ->assertOk()
            ->assertSee('Verificación en dos pasos')
            ->assertSee('Código de recuperación');
    }

    public function test_user_can_complete_login_with_valid_totp_code(): void
    {
        $user = $this->createConfirmedTwoFactorUser();
        $code = $this->currentTotpCode($user);
        auth()->logout();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'CurrentPassword123!',
        ])->assertRedirect(route('two-factor.login'));

        $response = $this->post(route('two-factor.login.store'), [
            'code' => $code,
        ]);

        $response->assertRedirect(config('fortify.home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_complete_login_with_recovery_code_and_it_is_rotated(): void
    {
        $user = $this->createConfirmedTwoFactorUser();
        $codesBefore = $user->recoveryCodes();
        $recoveryCode = $codesBefore[0];
        auth()->logout();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'CurrentPassword123!',
        ])->assertRedirect(route('two-factor.login'));

        $response = $this->post(route('two-factor.login.store'), [
            'recovery_code' => $recoveryCode,
        ]);

        $response->assertRedirect(config('fortify.home'));
        $this->assertAuthenticatedAs($user);

        $codesAfter = $user->fresh()->recoveryCodes();

        $this->assertNotContains($recoveryCode, $codesAfter);
        $this->assertCount(count($codesBefore), $codesAfter);
    }


    public function test_internal_admin_keeps_internal_dashboard_redirect_after_two_factor_login(): void
    {
        $user = User::factory()->create([
            'tenant_id' => null,
            'role' => User::ROLE_INTERNAL_ADMIN,
            'email' => 'internal-2fa@doctotal.test',
            'password' => Hash::make('CurrentPassword123!'),
        ]);

        app(EnableTwoFactorAuthentication::class)($user);
        $user->refresh();
        $user->forceFill([
            'two_factor_confirmed_at' => now(),
        ])->save();
        $user->refresh();

        $code = $this->currentTotpCode($user);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'CurrentPassword123!',
        ])->assertRedirect(route('two-factor.login'));

        $this->post(route('two-factor.login.store'), [
            'code' => $code,
        ])->assertRedirect(route('internal.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_two_factor_security_actions_are_audited_without_secrets_or_credentials(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $component = Livewire::test('pages::settings.security')
            ->set('two_factor_password', 'CurrentPassword123!')
            ->call('enableTwoFactorAuthentication')
            ->assertHasNoErrors();

        $pendingUser = $user->fresh();
        $plainSecret = decrypt($pendingUser->two_factor_secret);
        $code = $this->currentTotpCode($pendingUser);

        $component
            ->set('two_factor_code', $code)
            ->call('confirmTwoFactorAuthentication')
            ->assertHasNoErrors();

        $confirmedUser = $user->fresh();
        $recoveryCodes = $confirmedUser->recoveryCodes();

        $component
            ->set('recovery_codes_password', 'CurrentPassword123!')
            ->call('regenerateRecoveryCodes')
            ->assertHasNoErrors()
            ->set('disable_two_factor_password', 'CurrentPassword123!')
            ->call('disableTwoFactorAuthentication')
            ->assertHasNoErrors();

        $actions = AuditEvent::query()
            ->where('user_id', $user->id)
            ->whereIn('action', [
                'account.two_factor.setup_started',
                'account.two_factor.enabled',
                'account.two_factor.recovery_codes.regenerated',
                'account.two_factor.disabled',
            ])
            ->pluck('action')
            ->all();

        $this->assertContains('account.two_factor.setup_started', $actions);
        $this->assertContains('account.two_factor.enabled', $actions);
        $this->assertContains('account.two_factor.recovery_codes.regenerated', $actions);
        $this->assertContains('account.two_factor.disabled', $actions);

        $auditPayload = json_encode(
            AuditEvent::query()
                ->where('user_id', $user->id)
                ->get()
                ->toArray()
        );

        $this->assertStringNotContainsString('CurrentPassword123!', $auditPayload);
        $this->assertStringNotContainsString($plainSecret, $auditPayload);
        $this->assertStringNotContainsString($code, $auditPayload);

        foreach ($recoveryCodes as $recoveryCode) {
            $this->assertStringNotContainsString($recoveryCode, $auditPayload);
        }
    }


    public function test_recovery_codes_cannot_be_revealed_by_tampering_with_livewire_state(): void
    {
        $user = $this->createConfirmedTwoFactorUser();
        $firstRecoveryCode = $user->recoveryCodes()[0];
        $this->actingAs($user);

        Livewire::test('pages::settings.security')
            ->set('showingRecoveryCodes', true)
            ->assertDontSee($firstRecoveryCode);
    }

    public function test_two_factor_attributes_are_hidden_from_user_serialization(): void
    {
        $user = $this->createConfirmedTwoFactorUser();
        $serialized = $user->fresh()->toArray();

        $this->assertArrayNotHasKey('two_factor_secret', $serialized);
        $this->assertArrayNotHasKey('two_factor_recovery_codes', $serialized);
    }

    private function createUser(array $tenantOverrides = []): User
    {
        $tenant = Tenant::query()->create(array_merge([
            'name' => 'Tenant 2FA Test',
            'slug' => 'tenant-2fa-'.uniqid(),
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

    private function createConfirmedTwoFactorUser(): User
    {
        $user = $this->createUser();

        app(EnableTwoFactorAuthentication::class)($user);
        $user->refresh();

        // This helper creates an already-confirmed account for tests that are
        // not testing setup confirmation itself. Do not consume a TOTP here:
        // Fortify rejects replay of an accepted code, and login tests must be
        // free to use the current authenticator code exactly once.
        $user->forceFill([
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $user->fresh();
    }

    private function currentTotpCode(User $user): string
    {
        $secret = Fortify::currentEncrypter()->decrypt($user->two_factor_secret);

        return (new Google2FA())->getCurrentOtp($secret);
    }
}
