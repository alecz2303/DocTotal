<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MakeInternalAdmin extends Command
{
    protected $signature = 'doctotal:make-internal-admin';

    protected $description = 'Crea un administrador interno de DocTotal sin tenant asociado';

    public function handle(): int
    {
        $this->components->info('Crear administrador interno de DocTotal');

        $name = trim((string) $this->ask('Nombre'));
        $email = strtolower(trim((string) $this->ask('Correo electrónico')));
        $password = (string) $this->secret('Contraseña');
        $passwordConfirmation = (string) $this->secret('Confirmar contraseña');

        $validator = Validator::make(
            [
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $passwordConfirmation,
            ],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique((new User())->getTable(), 'email'),
                ],
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'confirmed',
                ],
            ],
            [
                'email.unique' => 'Ya existe un usuario con ese correo electrónico.',
                'password.confirmed' => 'Las contraseñas no coinciden.',
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $user = new User();
        $user->name = $name;
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->role = User::ROLE_INTERNAL_ADMIN;
        $user->tenant_id = null;
        $user->save();

        $this->newLine();
        $this->components->info('Administrador interno creado correctamente.');
        $this->line("Correo: {$user->email}");
        $this->line('Acceso: /internal');

        return self::SUCCESS;
    }
}
