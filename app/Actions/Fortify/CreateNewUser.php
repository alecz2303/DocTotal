<?php

namespace App\Actions\Fortify;

use App\Actions\Registration\RegisterDoctor;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Commercial\PromoCodeResolver;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    public function __construct(
        private RegisterDoctor $registerDoctor,
        private PromoCodeResolver $promoCodeResolver,
    ) {}

    public function create(array $input): User
    {
        if (! empty($input['code'])) {
            $input['code'] = strtoupper(
                trim($input['code'])
            );
        }

        if (! empty($input['referral_code'])) {
            $input['referral_code'] = strtoupper(
                trim($input['referral_code'])
            );
        }

        if (! empty($input['promo_code'])) {
            $input['promo_code'] = strtoupper(
                trim($input['promo_code'])
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
            'code' => [
                'nullable',
                'string',
                'max:32',
                'prohibits:referral_code,promo_code',
            ],
            'referral_code' => [
                'nullable',
                'string',
                'max:16',
                'prohibits:promo_code,code',
                Rule::exists('tenants', 'referral_code')
                    ->whereNull('deleted_at'),
            ],
            'promo_code' => [
                'nullable',
                'string',
                'max:32',
                'prohibits:referral_code,code',
            ],
            'terms_accepted' => [
                'accepted',
            ],
        ], [
            'referral_code.exists' =>
            'El código de referido no es válido.',
            'referral_code.prohibits' =>
            'Usa sólo un código: referido o promocional.',
            'promo_code.prohibits' =>
            'Usa sólo un código: referido o promocional.',
            'code.prohibits' =>
            'Usa sólo un código por registro.',
            'terms_accepted.accepted' =>
            'Debes aceptar los Términos y Condiciones y el Aviso de Privacidad.',
        ])->validate();

        if (! empty($input['code'])) {
            $referrerExists = Tenant::query()
                ->where('referral_code', $input['code'])
                ->whereNull('deleted_at')
                ->exists();

            if ($referrerExists) {
                $input['referral_code'] = $input['code'];
            } elseif ($this->promoCodeResolver->findValid($input['code'])) {
                $input['promo_code'] = $input['code'];
            } else {
                throw ValidationException::withMessages([
                    'code' => 'El código ingresado no es válido o ya no está vigente.',
                ]);
            }

            unset($input['code']);
        }

        if (
            ! empty($input['promo_code'])
            && ! $this->promoCodeResolver->findValid($input['promo_code'])
        ) {
            throw ValidationException::withMessages([
                'promo_code' => 'El código promocional no es válido o ya no está vigente.',
            ]);
        }

        $input['legal_acceptance'] = [
            'terms_version' => (string) config('legal.terms_version'),
            'privacy_version' => (string) config('legal.privacy_version'),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        return $this->registerDoctor->handle(
            $input
        );
    }
}
