<?php

namespace Tests\Feature;

use App\Models\AuditEvent;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AuditLoggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_audit_event_for_current_tenant(): void
    {
        $tenant = $this->createTenant();

        app(TenantContext::class)->set($tenant);

        $patient = $this->createPatient();

        $event = app(AuditLogger::class)->log(
            action: 'patient.updated',
            auditable: $patient,
            description: 'Paciente actualizado',
        );

        $this->assertDatabaseHas('audit_events', [
            'id' => $event->id,
            'tenant_id' => $tenant->id,
            'action' => 'patient.updated',
            'auditable_type' => Patient::class,
            'auditable_id' => $patient->id,
            'description' => 'Paciente actualizado',
        ]);
    }

    public function test_it_uses_authenticated_user_as_actor(): void
    {
        $tenant = $this->createTenant();

        $user = $this->createUser(
            $tenant,
            'doctor@example.com'
        );

        app(TenantContext::class)->set($tenant);

        $this->actingAs($user);

        $event = app(AuditLogger::class)->log(
            action: 'patient.updated',
        );

        $this->assertSame(
            $user->id,
            $event->user_id
        );
    }

    public function test_user_is_optional_for_system_events(): void
    {
        $tenant = $this->createTenant();

        app(TenantContext::class)->set($tenant);

        $event = app(AuditLogger::class)->log(
            action: 'system.test',
        );

        $this->assertNull($event->user_id);
    }

    public function test_it_records_auditable_resource(): void
    {
        $tenant = $this->createTenant();

        app(TenantContext::class)->set($tenant);

        $patient = $this->createPatient();

        $event = app(AuditLogger::class)->log(
            action: 'patient.updated',
            auditable: $patient,
        );

        $this->assertSame(
            Patient::class,
            $event->auditable_type
        );

        $this->assertSame(
            $patient->id,
            $event->auditable_id
        );

        $this->assertTrue(
            $event->auditable->is($patient)
        );
    }

    public function test_it_stores_metadata(): void
    {
        $tenant = $this->createTenant();

        app(TenantContext::class)->set($tenant);

        $event = app(AuditLogger::class)->log(
            action: 'patient.updated',
            metadata: [
                'changed_fields' => [
                    'first_name',
                    'last_name',
                ],
            ],
        );

        $this->assertSame(
            [
                'changed_fields' => [
                    'first_name',
                    'last_name',
                ],
            ],
            $event->metadata
        );
    }

    public function test_it_redacts_sensitive_metadata(): void
    {
        $tenant = $this->createTenant();

        app(TenantContext::class)->set($tenant);

        $event = app(AuditLogger::class)->log(
            action: 'security.test',
            metadata: [
                'email' => 'doctor@example.com',
                'password' => 'super-secret-password',
                'token' => 'secret-token',
                'nested' => [
                    'api_key' => 'secret-key',
                    'safe_value' => 'visible',
                ],
            ],
        );

        $this->assertSame(
            'doctor@example.com',
            $event->metadata['email']
        );

        $this->assertSame(
            '[REDACTED]',
            $event->metadata['password']
        );

        $this->assertSame(
            '[REDACTED]',
            $event->metadata['token']
        );

        $this->assertSame(
            '[REDACTED]',
            $event->metadata['nested']['api_key']
        );

        $this->assertSame(
            'visible',
            $event->metadata['nested']['safe_value']
        );
    }

    public function test_it_records_request_context(): void
    {
        $tenant = $this->createTenant();

        $user = $this->createUser(
            $tenant,
            'doctor@example.com'
        );

        app(TenantContext::class)->set($tenant);

        $this->actingAs($user);

        request()->server->set(
            'REMOTE_ADDR',
            '127.0.0.1'
        );

        request()->headers->set(
            'User-Agent',
            'DocTotal Test Agent'
        );

        $event = app(AuditLogger::class)->log(
            action: 'security.test',
        );

        $this->assertSame(
            '127.0.0.1',
            $event->ip_address
        );

        $this->assertSame(
            'DocTotal Test Agent',
            $event->user_agent
        );
    }

    public function test_event_requires_tenant_context(): void
    {
        app(TenantContext::class)->clear();

        $this->expectException(
            \RuntimeException::class
        );

        app(AuditLogger::class)->log(
            action: 'system.test',
        );
    }

    private function createTenant(
        string $name = 'Tenant Test',
        string $slug = 'tenant-test',
    ): Tenant {
        return Tenant::create([
            'name' => $name,
            'slug' => $slug,
            'onboarding_completed_at' => now(),
        ]);
    }

    private function createUser(
        Tenant $tenant,
        string $email,
    ): User {
        return User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Dr. Test',
            'email' => $email,
            'password' => 'password123',
            'role' => 'owner',
        ]);
    }

    private function createPatient(): Patient
    {
        return Patient::create([
            'first_name' => 'Paciente',
            'last_name' => 'Prueba',
            'second_last_name' => 'Test',
            'birth_date' => '1990-01-15',
        ]);
    }

    public function test_sensitive_metadata_key_variants_are_redacted(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        app(TenantContext::class)->set($tenant);

        $event = app(AuditLogger::class)->log(
            action: 'security.test',
            metadata: [
                'auth_token' => 'secret-auth-token',
                'client_secret' => 'secret-client-value',
                'private_api_key' => 'secret-api-key',
                'nested' => [
                    'refresh_token_value' => 'secret-refresh-token',
                    'safe_value' => 'visible',
                ],
            ],
        );

        $this->assertSame(
            '[REDACTED]',
            $event->metadata['auth_token']
        );

        $this->assertSame(
            '[REDACTED]',
            $event->metadata['client_secret']
        );

        $this->assertSame(
            '[REDACTED]',
            $event->metadata['private_api_key']
        );

        $this->assertSame(
            '[REDACTED]',
            $event->metadata['nested']['refresh_token_value']
        );

        $this->assertSame(
            'visible',
            $event->metadata['nested']['safe_value']
        );
    }

    public function test_safe_log_does_not_break_main_flow_when_audit_fails(): void
    {
        app(TenantContext::class)->clear();

        Log::shouldReceive('error')
            ->once()
            ->withArgs(
                function (
                    string $message,
                    array $context
                ): bool {
                    return
                        $message === 'Audit event could not be recorded.'
                        && $context['action'] === 'patient.updated'
                        && isset($context['exception']);
                }
            );

        $event = app(AuditLogger::class)->safeLog(
            action: 'patient.updated',
            description: 'Paciente actualizado.',
        );

        $this->assertNull($event);
    }
}
