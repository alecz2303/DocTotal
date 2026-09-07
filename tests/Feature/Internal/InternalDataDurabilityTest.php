<?php

namespace Tests\Feature\Internal;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalDataDurabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_admin_can_view_ready_backup_status(): void
    {
        $this->configureReadyDurability();

        $admin = User::factory()->create([
            'tenant_id' => null,
            'role' => User::ROLE_INTERNAL_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get(route('internal.data-durability.index'))
            ->assertOk()
            ->assertSee('Backups y restauración')
            ->assertSee('Operativo')
            ->assertSee('managed-backup')
            ->assertSee('Incluida')
            ->assertSee('Incluidos')
            ->assertSee('Cada 24 h')
            ->assertSee('7 copias')
            ->assertSee('Deshabilitado');
    }

    public function test_internal_admin_sees_incomplete_configuration_without_secrets(): void
    {
        config([
            'data_durability.backup.enabled' => true,
            'data_durability.backup.database' => true,
            'data_durability.backup.private_files' => false,
            'data_durability.backup.provider' => null,
            'data_durability.backup.frequency_hours' => 24,
            'data_durability.backup.retention_copies' => 7,
            'data_durability.restore.runbook' => 'docs/OPERATIONS_DATA_DURABILITY.md',
            'data_durability.restore.verification_required' => true,
            'data_durability.retention.mode' => 'manual',
            'data_durability.retention.automatic_deletion_enabled' => false,
            'services.stripe.secret' => 'SENSITIVE_SECRET_VALUE',
        ]);

        $admin = User::factory()->create([
            'tenant_id' => null,
            'role' => User::ROLE_INTERNAL_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get(route('internal.data-durability.index'))
            ->assertOk()
            ->assertSee('Incompleto')
            ->assertSee('durability.backup.private_files')
            ->assertSee('durability.backup.provider')
            ->assertDontSee('SENSITIVE_SECRET_VALUE');
    }

    public function test_automatic_clinical_deletion_is_presented_as_unsafe(): void
    {
        $this->configureReadyDurability();
        config(['data_durability.retention.automatic_deletion_enabled' => true]);

        $admin = User::factory()->create([
            'tenant_id' => null,
            'role' => User::ROLE_INTERNAL_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get(route('internal.data-durability.index'))
            ->assertOk()
            ->assertSee('Inseguro')
            ->assertSee('durability.retention.automatic_deletion')
            ->assertSee('Habilitado');
    }

    public function test_tenant_user_cannot_access_internal_backup_visibility(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Tenant clínico',
            'slug' => 'tenant-clinico',
            'status' => 'trial',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => User::ROLE_OWNER,
        ]);

        $this->actingAs($user)
            ->get(route('internal.data-durability.index'))
            ->assertForbidden();
    }

    private function configureReadyDurability(): void
    {
        config([
            'data_durability.backup.enabled' => true,
            'data_durability.backup.database' => true,
            'data_durability.backup.private_files' => true,
            'data_durability.backup.provider' => 'managed-backup',
            'data_durability.backup.frequency_hours' => 24,
            'data_durability.backup.retention_copies' => 7,
            'data_durability.restore.runbook' => 'docs/OPERATIONS_DATA_DURABILITY.md',
            'data_durability.restore.verification_required' => true,
            'data_durability.retention.mode' => 'manual',
            'data_durability.retention.automatic_deletion_enabled' => false,
        ]);
    }
}
