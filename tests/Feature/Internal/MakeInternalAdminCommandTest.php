<?php

namespace Tests\Feature\Internal;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MakeInternalAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_internal_admin_without_tenant(): void
    {
        $this->artisan('doctotal:make-internal-admin')
            ->expectsQuestion('Nombre', 'Administrador DocTotal')
            ->expectsQuestion('Correo electrónico', 'ADMIN@DOCTOTAL.TEST')
            ->expectsQuestion('Contraseña', 'ClaveSegura123!')
            ->expectsQuestion('Confirmar contraseña', 'ClaveSegura123!')
            ->expectsOutputToContain('Administrador interno creado correctamente.')
            ->assertExitCode(0);

        $user = User::query()
            ->where('email', 'admin@doctotal.test')
            ->firstOrFail();

        $this->assertSame(User::ROLE_INTERNAL_ADMIN, $user->role);
        $this->assertNull($user->tenant_id);
        $this->assertTrue($user->isInternalAdmin());
        $this->assertTrue(Hash::check('ClaveSegura123!', $user->password));
    }

    public function test_command_rejects_duplicate_email_including_tenant_users(): void
    {
        $tenant = Tenant::create([
            'name' => 'Clínica Existente',
            'slug' => 'clinica-existente-admin-command',
            'status' => 'trial',
            'trial_started_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);

        User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => User::ROLE_OWNER,
            'email' => 'existente@doctotal.test',
        ]);

        $this->artisan('doctotal:make-internal-admin')
            ->expectsQuestion('Nombre', 'Otro Administrador')
            ->expectsQuestion('Correo electrónico', 'existente@doctotal.test')
            ->expectsQuestion('Contraseña', 'ClaveSegura123!')
            ->expectsQuestion('Confirmar contraseña', 'ClaveSegura123!')
            ->expectsOutputToContain('Ya existe un usuario con ese correo electrónico.')
            ->assertExitCode(1);

        $this->assertSame(
            1,
            User::query()
                ->where('email', 'existente@doctotal.test')
                ->count()
        );
    }

    public function test_command_rejects_password_confirmation_mismatch(): void
    {
        $this->artisan('doctotal:make-internal-admin')
            ->expectsQuestion('Nombre', 'Administrador DocTotal')
            ->expectsQuestion('Correo electrónico', 'nuevo@doctotal.test')
            ->expectsQuestion('Contraseña', 'ClaveSegura123!')
            ->expectsQuestion('Confirmar contraseña', 'OtraClave123!')
            ->expectsOutputToContain('Las contraseñas no coinciden.')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', [
            'email' => 'nuevo@doctotal.test',
        ]);
    }
}
