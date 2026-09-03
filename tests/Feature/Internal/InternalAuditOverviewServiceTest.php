<?php

namespace Tests\Feature\Internal;

use App\Models\AuditEvent;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Internal\InternalAuditOverviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalAuditOverviewServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_reads_audit_events_across_tenants(): void
    {
        $firstTenant = $this->createTenant('Alpha', 'alpha');
        $secondTenant = $this->createTenant('Beta', 'beta');

        $this->createAuditEvent($firstTenant, 'patient.created');
        $this->createAuditEvent($secondTenant, 'appointment.updated');
        $this->createAuditEvent($secondTenant, 'patient.created');

        $summary = app(InternalAuditOverviewService::class)->summary();

        $this->assertSame(3, $summary['total']);
        $this->assertSame(3, $summary['today']);
        $this->assertSame(3, $summary['last_7_days']);
        $this->assertSame(2, $summary['distinct_actions']);
    }

    public function test_events_can_filter_by_tenant_action_and_user(): void
    {
        $firstTenant = $this->createTenant('Alpha', 'alpha');
        $secondTenant = $this->createTenant('Beta', 'beta');

        $firstUser = User::factory()->create([
            'tenant_id' => $firstTenant->id,
            'role' => User::ROLE_OWNER,
        ]);

        $secondUser = User::factory()->create([
            'tenant_id' => $secondTenant->id,
            'role' => User::ROLE_OWNER,
        ]);

        $this->createAuditEvent(
            $firstTenant,
            'patient.created',
            $firstUser
        );

        $this->createAuditEvent(
            $secondTenant,
            'patient.created',
            $secondUser
        );

        $this->createAuditEvent(
            $secondTenant,
            'appointment.updated',
            $secondUser
        );

        $service = app(InternalAuditOverviewService::class);

        $byTenant = $service->events([
            'tenant_id' => $secondTenant->id,
        ]);

        $this->assertSame(2, $byTenant->total());

        $byAction = $service->events([
            'action' => 'patient.created',
        ]);

        $this->assertSame(2, $byAction->total());

        $byUser = $service->events([
            'user_id' => $firstUser->id,
        ]);

        $this->assertSame(1, $byUser->total());
        $this->assertSame(
            $firstUser->id,
            $byUser->first()->user_id
        );
    }

    public function test_internal_audit_view_does_not_render_metadata(): void
    {
        $tenant = $this->createTenant('Alpha', 'alpha');

        $this->createAuditEvent(
            $tenant,
            'security.test',
            null,
            [
                'metadata' => [
                    'internal_note' =>
                        'THIS_METADATA_MUST_NOT_BE_RENDERED',
                ],
            ]
        );

        $admin = User::factory()->create([
            'tenant_id' => null,
            'role' => User::ROLE_INTERNAL_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get(route('internal.audit.index'))
            ->assertOk()
            ->assertDontSee(
                'THIS_METADATA_MUST_NOT_BE_RENDERED'
            );
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

    private function createAuditEvent(
        Tenant $tenant,
        string $action,
        ?User $user = null,
        array $overrides = []
    ): AuditEvent {
        return AuditEvent::query()
            ->withoutGlobalScopes()
            ->create(array_merge([
                'tenant_id' => $tenant->id,
                'user_id' => $user?->id,
                'action' => $action,
                'auditable_type' => null,
                'auditable_id' => null,
                'description' => 'Evento de prueba.',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'DocTotal Test',
                'metadata' => null,
            ], $overrides));
    }
}
