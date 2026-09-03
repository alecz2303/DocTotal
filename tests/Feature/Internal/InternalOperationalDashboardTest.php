<?php

namespace Tests\Feature\Internal;

use App\Models\AuditEvent;
use App\Models\Communication;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Internal\InternalSaasOverviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalOperationalDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_overview_aggregates_operational_state_across_tenants(): void
    {
        $firstTenant = $this->createTenant('Alpha', 'alpha');
        $secondTenant = $this->createTenant('Beta', 'beta');

        User::factory()->create([
            'tenant_id' => $firstTenant->id,
            'role' => User::ROLE_OWNER,
        ]);

        User::factory()->create([
            'tenant_id' => $secondTenant->id,
            'role' => User::ROLE_OWNER,
        ]);

        $this->createSubscription(
            $firstTenant,
            Subscription::STATUS_ACTIVE
        );

        $this->createSubscription(
            $secondTenant,
            Subscription::STATUS_PAST_DUE
        );

        $this->createPayment(
            $secondTenant,
            Payment::STATUS_FAILED
        );

        $this->createCommunication(
            $firstTenant,
            'alpha-pending',
            Communication::STATUS_PENDING
        );

        $this->createCommunication(
            $secondTenant,
            'beta-failed',
            Communication::STATUS_FAILED,
            [
                'failed_at' => now(),
                'attempt_count' => 3,
                'next_attempt_at' => null,
                'last_error' => 'Intentos agotados.',
            ]
        );

        $this->createAuditEvent(
            $firstTenant,
            'patient.created'
        );

        $overview = app(InternalSaasOverviewService::class)->overview();

        $this->assertSame(2, $overview['tenants']['total']);
        $this->assertSame(2, $overview['users']['total']);
        $this->assertSame(1, $overview['subscriptions']['active']);
        $this->assertSame(1, $overview['subscriptions']['past_due']);
        $this->assertSame(1, $overview['payments']['failed']);
        $this->assertSame(1, $overview['communications']['pending']);
        $this->assertSame(1, $overview['communications']['failed']);
        $this->assertSame(1, $overview['communications']['exhausted']);
        $this->assertSame(1, $overview['audit']['today']);
        $this->assertSame('attention', $overview['health']['status']);
        $this->assertSame(4, $overview['health']['incidents']);
    }

    public function test_dashboard_does_not_render_communication_recipient_or_body(): void
    {
        $tenant = $this->createTenant('Alpha', 'alpha');

        $this->createCommunication(
            $tenant,
            'sensitive-failure',
            Communication::STATUS_FAILED,
            [
                'recipient' => 'patient@example.test',
                'body' => 'SENSITIVE_COMMUNICATION_BODY',
                'failed_at' => now(),
                'last_error' => 'Transport error.',
            ]
        );

        $admin = User::factory()->create([
            'tenant_id' => null,
            'role' => User::ROLE_INTERNAL_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get(route('internal.dashboard'))
            ->assertOk()
            ->assertDontSee('patient@example.test')
            ->assertDontSee('SENSITIVE_COMMUNICATION_BODY');
    }

    public function test_dashboard_contains_operational_navigation(): void
    {
        $admin = User::factory()->create([
            'tenant_id' => null,
            'role' => User::ROLE_INTERNAL_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get(route('internal.dashboard'))
            ->assertOk()
            ->assertSee(route('internal.tenants.index'), false)
            ->assertSee(route('internal.billing.index'), false)
            ->assertSee(route('internal.communications.index'), false)
            ->assertSee(route('internal.audit.index'), false);
    }

    private function createTenant(
        string $name,
        string $slug
    ): Tenant {
        return Tenant::query()->create([
            'name' => $name,
            'slug' => $slug,
            'status' => 'trial',
        ]);
    }

    private function createSubscription(
        Tenant $tenant,
        string $status
    ): Subscription {
        return Subscription::query()
            ->withoutGlobalScopes()
            ->create([
                'tenant_id' => $tenant->id,
                'billing_cycle' => Subscription::BILLING_CYCLE_MONTHLY,
                'status' => $status,
                'starts_at' => now()->subMonth(),
                'current_period_starts_at' => now()->subDay(),
                'current_period_ends_at' => now()->addMonth(),
                'billing_amount' => 10000,
                'billing_currency' => 'MXN',
            ]);
    }

    private function createPayment(
        Tenant $tenant,
        string $status
    ): Payment {
        return Payment::query()
            ->withoutGlobalScopes()
            ->create([
                'tenant_id' => $tenant->id,
                'subscription_id' => null,
                'amount' => 10000,
                'currency' => 'MXN',
                'status' => $status,
                'attempted_at' => now(),
                'failed_at' => $status === Payment::STATUS_FAILED
                    ? now()
                    : null,
                'failure_code' => $status === Payment::STATUS_FAILED
                    ? 'test_failure'
                    : null,
                'failure_message' => $status === Payment::STATUS_FAILED
                    ? 'Payment failed in test.'
                    : null,
                'provider' => 'test',
                'provider_payment_id' => null,
                'idempotency_key' => 'payment-'.$tenant->id.'-'.$status,
                'billing_cycle' => 'monthly',
            ]);
    }

    private function createCommunication(
        Tenant $tenant,
        string $idempotencyKey,
        string $status,
        array $overrides = []
    ): Communication {
        return Communication::query()
            ->withoutGlobalScopes()
            ->create(array_merge([
                'tenant_id' => $tenant->id,
                'patient_id' => null,
                'appointment_id' => null,
                'type' => Communication::TYPE_APPOINTMENT_REMINDER,
                'channel' => Communication::CHANNEL_EMAIL,
                'recipient' => 'hidden@example.test',
                'subject' => 'Recordatorio',
                'body' => 'Contenido no visible en dashboard.',
                'status' => $status,
                'idempotency_key' => $idempotencyKey,
                'scheduled_for' => now()->addHour(),
                'attempt_count' => 0,
                'metadata' => null,
            ], $overrides));
    }

    private function createAuditEvent(
        Tenant $tenant,
        string $action
    ): AuditEvent {
        return AuditEvent::query()
            ->withoutGlobalScopes()
            ->create([
                'tenant_id' => $tenant->id,
                'user_id' => null,
                'action' => $action,
                'auditable_type' => null,
                'auditable_id' => null,
                'description' => 'Dashboard test event.',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'DocTotal Test',
                'metadata' => null,
            ]);
    }
}
