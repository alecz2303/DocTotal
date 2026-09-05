<?php

namespace Tests\Feature;

use App\Models\AuditEvent;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AccountSessionManager;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AccountSessionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_page_shows_sessions_and_current_device(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('settings.security'))
            ->assertOk()
            ->assertSee('Sesiones y dispositivos')
            ->assertSee('Este dispositivo')
            ->assertSee('Cerrar todas las demás sesiones');
    }

    public function test_suspended_tenant_can_manage_sessions(): void
    {
        $user = $this->createUser([
            'status' => 'suspended',
            'suspended_at' => now(),
        ]);
        $this->actingAs($user);

        $targetId = $this->insertSession($user, '203.0.113.10');
        $targetKey = app(AccountSessionManager::class)->fingerprint($targetId);

        Livewire::test('pages::settings.security')
            ->set('session_password', 'CurrentPassword123!')
            ->call('revokeSession', $targetKey)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('sessions', ['id' => $targetId]);
    }

    public function test_only_sessions_belonging_to_authenticated_user_are_listed(): void
    {
        $user = $this->createUser();
        $otherUser = User::factory()->create([
            'tenant_id' => $user->tenant_id,
            'password' => Hash::make('OtherPassword123!'),
        ]);

        $ownSessionId = $this->insertSession($user, '203.0.113.20', 'Mozilla/5.0 (Windows NT 10.0) Chrome/140.0.0.0 Safari/537.36');
        $foreignSessionId = $this->insertSession($otherUser, '198.51.100.77', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Version/18.0 Safari/605.1.15');

        $this->actingAs($user);

        Livewire::test('pages::settings.security')
            ->assertSee('203.0.113.20')
            ->assertSee('Google Chrome en Windows')
            ->assertDontSee('198.51.100.77')
            ->assertDontSee('Safari en macOS')
            ->assertDontSee($ownSessionId)
            ->assertDontSee($foreignSessionId);
    }

    public function test_user_can_revoke_a_specific_other_session_with_current_password(): void
    {
        $user = $this->createUser();
        $user->forceFill(['remember_token' => 'remember-token-before'])->save();
        $this->actingAs($user);

        $targetId = $this->insertSession($user, '203.0.113.30');
        $targetKey = app(AccountSessionManager::class)->fingerprint($targetId);

        Livewire::test('pages::settings.security')
            ->set('session_password', 'CurrentPassword123!')
            ->call('revokeSession', $targetKey)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('sessions', ['id' => $targetId]);
        $this->assertNotSame('remember-token-before', $user->fresh()->getRememberToken());
    }

    public function test_wrong_password_cannot_revoke_a_session(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $targetId = $this->insertSession($user, '203.0.113.31');
        $targetKey = app(AccountSessionManager::class)->fingerprint($targetId);

        Livewire::test('pages::settings.security')
            ->set('session_password', 'WrongPassword123!')
            ->call('revokeSession', $targetKey)
            ->assertHasErrors(['session_password']);

        $this->assertDatabaseHas('sessions', ['id' => $targetId]);
    }

    public function test_user_cannot_revoke_another_users_session_by_tampering_with_session_key(): void
    {
        $user = $this->createUser();
        $otherUser = User::factory()->create([
            'tenant_id' => $user->tenant_id,
            'password' => Hash::make('OtherPassword123!'),
        ]);
        $this->actingAs($user);

        $otherSessionId = $this->insertSession($otherUser, '198.51.100.90');
        $otherSessionKey = app(AccountSessionManager::class)->fingerprint($otherSessionId);

        Livewire::test('pages::settings.security')
            ->set('session_password', 'CurrentPassword123!')
            ->call('revokeSession', $otherSessionKey)
            ->assertHasErrors(['session_password']);

        $this->assertDatabaseHas('sessions', [
            'id' => $otherSessionId,
            'user_id' => $otherUser->id,
        ]);
    }

    public function test_current_session_cannot_be_revoked_from_sessions_list(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $currentSessionId = session()->getId();
        $this->insertSessionWithId($currentSessionId, $user, '127.0.0.1');
        $currentKey = app(AccountSessionManager::class)->fingerprint($currentSessionId);

        Livewire::test('pages::settings.security')
            ->set('session_password', 'CurrentPassword123!')
            ->call('revokeSession', $currentKey)
            ->assertHasErrors(['session_password']);

        $this->assertDatabaseHas('sessions', [
            'id' => $currentSessionId,
            'user_id' => $user->id,
        ]);
    }

    public function test_user_can_revoke_all_other_sessions_without_closing_current_session(): void
    {
        $user = $this->createUser();
        $otherUser = User::factory()->create([
            'tenant_id' => $user->tenant_id,
            'password' => Hash::make('OtherPassword123!'),
        ]);
        $this->actingAs($user);

        $currentSessionId = session()->getId();
        $this->insertSessionWithId($currentSessionId, $user, '127.0.0.1');
        $firstOther = $this->insertSession($user, '203.0.113.40');
        $secondOther = $this->insertSession($user, '203.0.113.41');
        $foreignSession = $this->insertSession($otherUser, '198.51.100.41');

        Livewire::test('pages::settings.security')
            ->set('revoke_all_sessions_password', 'CurrentPassword123!')
            ->call('revokeOtherSessions')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('sessions', ['id' => $currentSessionId]);
        $this->assertDatabaseMissing('sessions', ['id' => $firstOther]);
        $this->assertDatabaseMissing('sessions', ['id' => $secondOther]);
        $this->assertDatabaseHas('sessions', ['id' => $foreignSession]);
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_cannot_revoke_all_other_sessions(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $targetId = $this->insertSession($user, '203.0.113.50');

        Livewire::test('pages::settings.security')
            ->set('revoke_all_sessions_password', 'WrongPassword123!')
            ->call('revokeOtherSessions')
            ->assertHasErrors(['revoke_all_sessions_password']);

        $this->assertDatabaseHas('sessions', ['id' => $targetId]);
    }

    public function test_session_revocation_is_audited_without_credentials_or_real_session_ids(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $targetId = $this->insertSession($user, '203.0.113.60');
        $targetKey = app(AccountSessionManager::class)->fingerprint($targetId);
        $secondTargetId = $this->insertSession($user, '203.0.113.61');

        $component = Livewire::test('pages::settings.security')
            ->set('session_password', 'CurrentPassword123!')
            ->call('revokeSession', $targetKey)
            ->assertHasNoErrors()
            ->set('revoke_all_sessions_password', 'CurrentPassword123!')
            ->call('revokeOtherSessions')
            ->assertHasNoErrors();

        $actions = AuditEvent::query()
            ->where('user_id', $user->id)
            ->whereIn('action', [
                'account.session.revoked',
                'account.sessions.others_revoked',
            ])
            ->pluck('action')
            ->all();

        $this->assertContains('account.session.revoked', $actions);
        $this->assertContains('account.sessions.others_revoked', $actions);

        $auditPayload = json_encode(
            AuditEvent::query()
                ->where('user_id', $user->id)
                ->get()
                ->toArray()
        );

        $this->assertStringNotContainsString('CurrentPassword123!', $auditPayload);
        $this->assertStringNotContainsString($targetId, $auditPayload);
        $this->assertStringNotContainsString($secondTargetId, $auditPayload);
    }

    private function createUser(array $tenantOverrides = []): User
    {
        $tenant = Tenant::query()->create(array_merge([
            'name' => 'Tenant Sessions Test',
            'slug' => 'tenant-sessions-'.uniqid(),
            'status' => 'trial',
            'onboarding_completed_at' => now(),
        ], $tenantOverrides));

        app(TenantContext::class)->set($tenant);

        return User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'sessions-'.uniqid().'@doctotal.test',
            'password' => Hash::make('CurrentPassword123!'),
        ]);
    }

    private function insertSession(
        User $user,
        string $ipAddress,
        string $userAgent = 'Mozilla/5.0 (Windows NT 10.0) Chrome/140.0.0.0 Safari/537.36',
    ): string {
        $id = 'session-'.bin2hex(random_bytes(20));

        $this->insertSessionWithId($id, $user, $ipAddress, $userAgent);

        return $id;
    }

    private function insertSessionWithId(
        string $id,
        User $user,
        string $ipAddress,
        string $userAgent = 'Mozilla/5.0 (Windows NT 10.0) Chrome/140.0.0.0 Safari/537.36',
    ): void {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $user->id,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'payload' => 'test-payload',
            'last_activity' => now()->timestamp,
        ]);
    }
}
