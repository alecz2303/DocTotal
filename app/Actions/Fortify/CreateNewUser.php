<?php

namespace App\Actions\Fortify;

use App\Actions\Registration\RegisterDoctor;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    public function __construct(
        private RegisterDoctor $registerDoctor
    ) {}

    public function create(array $input): User
    {
        if (! empty($input['referral_code'])) {
            $input['referral_code'] = strtoupper(
                trim($input['referral_code'])
            );
        }

        Validator::make($input, [
            'practice_name' => [
                'required',
                'string',
                'max:255',
            ],

            'first_name' => [
                'required',
                'string',
                'max:100',
            ],

            'last_name' => [
                'required',
                'string',
                'max:100',
            ],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'password' => [
                'required',
                'string',
                Password::default(),
                'confirmed',
            ],

            'referral_code' => [
                'nullable',
                'string',
                'max:16',
                Rule::exists('tenants', 'referral_code')
                    ->whereNull('deleted_at'),
            ],
        ], [
            'referral_code.exists' =>
            'El código de referido no es válido.',
        ])->validate();

        return $this->registerDoctor->handle(
            $input
        );
    }
}
