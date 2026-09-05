<?php

namespace Tests\Feature;

use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\AuditEvent;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AccountEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $preserveUnverifiedUsers = true;

    public function test_guest_cannot_access_email_verification_notice(): void
    {
        $this->get(route('verification.notice'))
            ->assertRedirect(route('login'));
    }

    public function test_unverified_user_can_see_custom_email_verification_notice(): void
    {
        $user = $this->createUser(unverified: true);

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertSee('Verifica tu correo electrónico')
            ->assertSee($user->email);
    }

    public function test_registration_sends_custom_verification_notification(): void
    {
        Notification::fake();

        $this->post('/register', [
            'practice_name' => 'Consultorio Verificación',
            'first_name' => 'Elena',
            'last_name' => 'Torres',
            'email' => 'elena@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect('/dashboard');

        $user = User::query()->where('email', 'elena@example.com')->firstOrFail();

        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_unverified_user_can_resend_verification_notification(): void
    {
        Notification::fake();
        $user = $this->createUser(unverified: true);

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect();

        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_verified_user_does_not_receive_another_verification_notification(): void
    {
        Notification::fake();
        $user = $this->createUser();

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect();

        Notification::assertNothingSent();
    }

    public function test_valid_signed_link_verifies_email_and_redirects_to_dashboard(): void
    {
        $user = $this->createUser(unverified: true);
        $url = $this->verificationUrl($user);

        $this->actingAs($user)
            ->get($url)
            ->assertRedirect(config('fortify.home'));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_invalid_verification_signature_is_rejected(): void
    {
        $user = $this->createUser(unverified: true);
        $url = $this->verificationUrl($user).'&tampered=1';

        $this->actingAs($user)
            ->get($url)
            ->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_unverified_user_is_blocked_from_protected_application_routes(): void
    {
        $user = $this->createUser(unverified: true);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));

        $this->actingAs($user)
            ->get(route('onboarding'))
            ->assertRedirect(route('verification.notice'));

        $this->actingAs($user)
            ->get(route('settings.billing'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_unverified_user_can_still_access_security_settings(): void
    {
        $user = $this->createUser(unverified: true);

        $this->actingAs($user)
            ->get(route('settings.security'))
            ->assertOk()
            ->assertSee('Correo electrónico de acceso')
            ->assertSee('Pendiente de verificar')
            ->assertSee('Reenviar verificación');
    }

    public function test_suspended_unverified_tenant_can_access_security_and_verification_notice(): void
    {
        $user = $this->createUser(
            unverified: true,
            tenantOverrides: [
                'status' => 'suspended',
                'suspended_at' => now(),
            ],
        );

        $this->actingAs($user)
            ->get(route('settings.security'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertOk();
    }

    public function test_email_verification_is_audited_without_signed_link_data(): void
    {
        $user = $this->createUser(unverified: true);
        $url = $this->verificationUrl($user);
        $verificationHash = sha1($user->getEmailForVerification());

        $this->actingAs($user)
            ->get($url)
            ->assertRedirect(config('fortify.home'));

        $event = AuditEvent::query()
            ->where('action', 'account.email.verified')
            ->latest('id')
            ->first();

        $this->assertNotNull($event);
        $this->assertSame($user->id, $event->user_id);
        $this->assertSame($user->id, $event->auditable_id);

        $payload = json_encode($event->toArray());

        $this->assertStringNotContainsString($verificationHash, $payload);
        $this->assertStringNotContainsString('verification.verify', $payload);
        $this->assertStringNotContainsString($url, $payload);
    }

    public function test_reusing_verification_link_does_not_duplicate_verification_audit(): void
    {
        $user = $this->createUser(unverified: true);
        $url = $this->verificationUrl($user);

        $this->actingAs($user)->get($url);
        $this->actingAs($user)->get($url);

        $this->assertSame(
            1,
            AuditEvent::query()
                ->where('action', 'account.email.verified')
                ->where('user_id', $user->id)
                ->count()
        );
    }

    public function test_changing_verified_email_marks_it_unverified_and_sends_new_link(): void
    {
        Notification::fake();
        $user = $this->createUser();

        app(UpdateUserProfileInformation::class)->update($user, [
            'name' => $user->name,
            'email' => 'nuevo-correo@example.com',
        ]);

        $user->refresh();

        $this->assertSame('nuevo-correo@example.com', $user->email);
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_internal_admin_returns_to_internal_dashboard_after_verification(): void
    {
        $user = User::factory()->unverified()->create([
            'tenant_id' => null,
            'role' => User::ROLE_INTERNAL_ADMIN,
            'email' => 'verify-internal@doctotal.test',
        ]);

        $this->actingAs($user)
            ->get($this->verificationUrl($user))
            ->assertRedirect(route('internal.dashboard'));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    private function createUser(
        bool $unverified = false,
        array $tenantOverrides = [],
    ): User {
        $tenant = Tenant::query()->create(array_merge([
            'name' => 'Tenant Email Verification Test',
            'slug' => 'tenant-email-verification-'.uniqid(),
            'status' => 'trial',
            'onboarding_completed_at' => now(),
        ], $tenantOverrides));

        app(TenantContext::class)->set($tenant);

        $factory = User::factory();

        if ($unverified) {
            $factory = $factory->unverified();
        }

        return $factory->create([
            'tenant_id' => $tenant->id,
            'role' => User::ROLE_OWNER,
        ]);
    }

    private function verificationUrl(User $user): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ],
        );
    }
}
