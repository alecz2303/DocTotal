<?php

namespace Tests\Feature\Internal;

use App\Models\Communication;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Internal\InternalCommunicationOverviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalCommunicationOverviewServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_reads_communication_state_across_tenants(): void
    {
        $firstTenant = $this->createTenant('Alpha', 'alpha');
        $secondTenant = $this->createTenant('Beta', 'beta');

        $this->createCommunication(
            $firstTenant,
            'alpha-pending',
            Communication::STATUS_PENDING
        );

        $this->createCommunication(
            $firstTenant,
            'alpha-sent',
            Communication::STATUS_SENT,
            [
                'sent_at' => now(),
            ]
        );

        $this->createCommunication(
            $secondTenant,
            'beta-failed-retry',
            Communication::STATUS_FAILED,
            [
                'failed_at' => now()->subMinute(),
                'attempt_count' => 1,
                'next_attempt_at' => now()->addMinutes(5),
                'last_error' => 'Transport temporalmente no disponible.',
            ]
        );

        $this->createCommunication(
            $secondTenant,
            'beta-failed-exhausted',
            Communication::STATUS_FAILED,
            [
                'failed_at' => now()->subMinute(),
                'attempt_count' => 3,
                'next_attempt_at' => null,
                'last_error' => 'Intentos agotados.',
            ]
        );

        $this->createCommunication(
            $secondTenant,
            'beta-cancelled',
            Communication::STATUS_CANCELLED,
            [
                'cancelled_at' => now(),
                'cancellation_reason' => 'Cita cancelada.',
            ]
        );

        $summary = app(InternalCommunicationOverviewService::class)->summary();

        $this->assertSame(1, $summary['pending']);
        $this->assertSame(1, $summary['sent']);
        $this->assertSame(2, $summary['failed']);
        $this->assertSame(1, $summary['cancelled']);
        $this->assertSame(1, $summary['failed_retry_scheduled']);
        $this->assertSame(1, $summary['failed_exhausted']);
    }

    public function test_failed_list_contains_only_failed_communications_across_tenants(): void
    {
        $firstTenant = $this->createTenant('Alpha', 'alpha');
        $secondTenant = $this->createTenant('Beta', 'beta');

        $this->createCommunication(
            $firstTenant,
            'alpha-failed',
            Communication::STATUS_FAILED,
            [
                'failed_at' => now(),
                'last_error' => 'Alpha error',
            ]
        );

        $this->createCommunication(
            $secondTenant,
            'beta-failed',
            Communication::STATUS_FAILED,
            [
                'failed_at' => now(),
                'last_error' => 'Beta error',
            ]
        );

        $this->createCommunication(
            $firstTenant,
            'alpha-sent',
            Communication::STATUS_SENT,
            [
                'sent_at' => now(),
            ]
        );

        $communications = app(InternalCommunicationOverviewService::class)
            ->failedCommunications();

        $this->assertSame(2, $communications->total());

        $this->assertTrue(
            $communications->getCollection()->every(
                fn (Communication $communication): bool =>
                    $communication->status === Communication::STATUS_FAILED
            )
        );

        $this->assertEqualsCanonicalizing(
            [$firstTenant->id, $secondTenant->id],
            $communications->getCollection()
                ->pluck('tenant_id')
                ->unique()
                ->values()
                ->all()
        );
    }

    public function test_operational_lists_do_not_expose_recipient_or_body_in_the_view(): void
    {
        $tenant = $this->createTenant('Alpha', 'alpha');

        $this->createCommunication(
            $tenant,
            'alpha-sensitive',
            Communication::STATUS_FAILED,
            [
                'failed_at' => now(),
                'last_error' => 'Error técnico controlado.',
                'recipient' => 'paciente@example.test',
                'body' => 'Contenido clínico que no debe aparecer en la consola interna.',
            ]
        );

        $admin = User::factory()->create([
            'tenant_id' => null,
            'role' => User::ROLE_INTERNAL_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get(route('internal.communications.index'))
            ->assertOk()
            ->assertDontSee('paciente@example.test')
            ->assertDontSee(
                'Contenido clínico que no debe aparecer en la consola interna.'
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
                'body' => 'Contenido no visible en consola interna.',
                'status' => $status,
                'idempotency_key' => $idempotencyKey,
                'scheduled_for' => now()->addHour(),
                'attempt_count' => 0,
                'metadata' => null,
            ], $overrides));
    }
}
