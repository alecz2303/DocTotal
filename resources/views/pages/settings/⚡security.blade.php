<?php

use App\Services\AccountSessionManager;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
    #[Layout('layouts::app')]
    #[Title('Seguridad | DocTotal')]
    class extends Component
    {
        public string $current_password = '';
        public string $password = '';
        public string $password_confirmation = '';

        public string $two_factor_password = '';
        public string $two_factor_code = '';
        public string $recovery_codes_password = '';
        public string $disable_two_factor_password = '';

        public bool $showingQrCode = false;
        public bool $showingRecoveryCodes = false;

        public string $session_password = '';
        public string $revoke_all_sessions_password = '';

        public function updatePassword(): void
        {
            $validated = $this->validate([
                'current_password' => [
                    'required',
                    'string',
                    'current_password:web',
                ],
                'password' => [
                    'required',
                    'string',
                    Password::default(),
                    'confirmed',
                ],
            ], [
                'current_password.required' => 'Escribe tu contraseña actual.',
                'current_password.current_password' => 'La contraseña actual no es correcta.',
                'password.required' => 'Escribe tu nueva contraseña.',
                'password.confirmed' => 'La confirmación de la nueva contraseña no coincide.',
            ]);

            $user = auth()->user();

            $user->forceFill([
                'password' => Hash::make($validated['password']),
            ])->save();

            app(AuditLogger::class)->safeLog(
                action: 'account.password.updated',
                auditable: $user,
                description: 'El usuario actualizó la contraseña de su cuenta.',
            );

            $this->reset([
                'current_password',
                'password',
                'password_confirmation',
            ]);

            session()->flash(
                'security_success',
                'Tu contraseña se actualizó correctamente.'
            );
        }

        public function enableTwoFactorAuthentication(): void
        {
            $this->validate([
                'two_factor_password' => [
                    'required',
                    'string',
                    'current_password:web',
                ],
            ], [
                'two_factor_password.required' => 'Confirma tu contraseña para activar la verificación en dos pasos.',
                'two_factor_password.current_password' => 'La contraseña no es correcta.',
            ]);

            $user = auth()->user()->fresh();

            if ($user->hasEnabledTwoFactorAuthentication()) {
                $this->addError('two_factor_password', 'La verificación en dos pasos ya está activa.');

                return;
            }

            if (empty($user->two_factor_secret)) {
                app(EnableTwoFactorAuthentication::class)($user);

                app(AuditLogger::class)->safeLog(
                    action: 'account.two_factor.setup_started',
                    auditable: $user,
                    description: 'El usuario inició la configuración de la verificación en dos pasos.',
                );
            }

            $this->authorizeSensitiveTwoFactorView('qr');
            $this->reset('two_factor_password');
            $this->showingQrCode = true;
            $this->showingRecoveryCodes = false;
        }

        public function confirmTwoFactorAuthentication(): void
        {
            $this->validate([
                'two_factor_code' => [
                    'required',
                    'string',
                    'regex:/^\d{6}$/',
                ],
            ], [
                'two_factor_code.required' => 'Escribe el código de 6 dígitos de tu aplicación autenticadora.',
                'two_factor_code.regex' => 'El código debe contener exactamente 6 dígitos.',
            ]);

            $user = auth()->user()->fresh();

            if (! $this->sensitiveTwoFactorViewIsAuthorized('qr')) {
                $this->addError('two_factor_code', 'Confirma nuevamente tu contraseña para continuar con la configuración.');

                return;
            }

            if (empty($user->two_factor_secret)) {
                $this->addError('two_factor_code', 'Primero inicia la configuración de la verificación en dos pasos.');

                return;
            }

            try {
                app(ConfirmTwoFactorAuthentication::class)($user, $this->two_factor_code);
            } catch (ValidationException) {
                $this->addError('two_factor_code', 'El código de autenticación no es válido o ya expiró.');

                return;
            }

            $user = $user->fresh();

            app(AuditLogger::class)->safeLog(
                action: 'account.two_factor.enabled',
                auditable: $user,
                description: 'El usuario activó la verificación en dos pasos.',
            );

            $this->forgetSensitiveTwoFactorViewAuthorization('qr');
            $this->authorizeSensitiveTwoFactorView('recovery');
            $this->reset('two_factor_code');
            $this->showingQrCode = false;
            $this->showingRecoveryCodes = true;

            session()->flash(
                'security_success',
                'La verificación en dos pasos quedó activada correctamente.'
            );
        }

        public function showRecoveryCodes(): void
        {
            $this->validate([
                'recovery_codes_password' => [
                    'required',
                    'string',
                    'current_password:web',
                ],
            ], [
                'recovery_codes_password.required' => 'Confirma tu contraseña para mostrar los códigos de recuperación.',
                'recovery_codes_password.current_password' => 'La contraseña no es correcta.',
            ]);

            $user = auth()->user()->fresh();

            if (! $user->hasEnabledTwoFactorAuthentication()) {
                $this->addError('recovery_codes_password', 'La verificación en dos pasos no está activa.');

                return;
            }

            $this->authorizeSensitiveTwoFactorView('recovery');
            $this->reset('recovery_codes_password');
            $this->showingRecoveryCodes = true;
        }

        public function hideRecoveryCodes(): void
        {
            $this->forgetSensitiveTwoFactorViewAuthorization('recovery');
            $this->showingRecoveryCodes = false;
        }

        public function regenerateRecoveryCodes(): void
        {
            $this->validate([
                'recovery_codes_password' => [
                    'required',
                    'string',
                    'current_password:web',
                ],
            ], [
                'recovery_codes_password.required' => 'Confirma tu contraseña para generar nuevos códigos.',
                'recovery_codes_password.current_password' => 'La contraseña no es correcta.',
            ]);

            $user = auth()->user()->fresh();

            if (! $user->hasEnabledTwoFactorAuthentication()) {
                $this->addError('recovery_codes_password', 'La verificación en dos pasos no está activa.');

                return;
            }

            app(GenerateNewRecoveryCodes::class)($user);

            app(AuditLogger::class)->safeLog(
                action: 'account.two_factor.recovery_codes.regenerated',
                auditable: $user,
                description: 'El usuario regeneró los códigos de recuperación de la verificación en dos pasos.',
            );

            $this->authorizeSensitiveTwoFactorView('recovery');
            $this->reset('recovery_codes_password');
            $this->showingRecoveryCodes = true;

            session()->flash(
                'security_success',
                'Se generaron nuevos códigos de recuperación. Los anteriores dejaron de ser válidos.'
            );
        }

        public function disableTwoFactorAuthentication(): void
        {
            $this->validate([
                'disable_two_factor_password' => [
                    'required',
                    'string',
                    'current_password:web',
                ],
            ], [
                'disable_two_factor_password.required' => 'Confirma tu contraseña para desactivar la verificación en dos pasos.',
                'disable_two_factor_password.current_password' => 'La contraseña no es correcta.',
            ]);

            $user = auth()->user()->fresh();
            $wasConfirmed = $user->hasEnabledTwoFactorAuthentication();

            if (empty($user->two_factor_secret) && empty($user->two_factor_recovery_codes)) {
                $this->addError('disable_two_factor_password', 'No hay una configuración de verificación en dos pasos para desactivar.');

                return;
            }

            app(DisableTwoFactorAuthentication::class)($user);

            app(AuditLogger::class)->safeLog(
                action: $wasConfirmed
                    ? 'account.two_factor.disabled'
                    : 'account.two_factor.setup_cancelled',
                auditable: $user,
                description: $wasConfirmed
                    ? 'El usuario desactivó la verificación en dos pasos.'
                    : 'El usuario canceló la configuración pendiente de la verificación en dos pasos.',
            );

            $this->forgetSensitiveTwoFactorViewAuthorization('qr');
            $this->forgetSensitiveTwoFactorViewAuthorization('recovery');
            $this->reset([
                'disable_two_factor_password',
                'two_factor_code',
            ]);
            $this->showingQrCode = false;
            $this->showingRecoveryCodes = false;

            session()->flash(
                'security_success',
                $wasConfirmed
                    ? 'La verificación en dos pasos fue desactivada.'
                    : 'La configuración pendiente de la verificación en dos pasos fue cancelada.'
            );
        }

        public function twoFactorEnabled(): bool
        {
            return auth()->user()->fresh()->hasEnabledTwoFactorAuthentication();
        }

        public function twoFactorSetupPending(): bool
        {
            $user = auth()->user()->fresh();

            return ! empty($user->two_factor_secret)
                && ! $user->hasEnabledTwoFactorAuthentication();
        }

        public function twoFactorQrCodeSvg(): ?string
        {
            $user = auth()->user()->fresh();

            if (! $this->showingQrCode
                || ! $this->sensitiveTwoFactorViewIsAuthorized('qr')
                || empty($user->two_factor_secret)) {
                return null;
            }

            return $user->twoFactorQrCodeSvg();
        }

        public function recoveryCodes(): array
        {
            $user = auth()->user()->fresh();

            if (! $this->showingRecoveryCodes
                || ! $this->sensitiveTwoFactorViewIsAuthorized('recovery')
                || empty($user->two_factor_recovery_codes)) {
                return [];
            }

            return $user->recoveryCodes();
        }

        public function activeSessions(): array
        {
            return app(AccountSessionManager::class)->sessionsFor(
                user: auth()->user(),
                currentSessionId: session()->getId(),
                currentIpAddress: request()->ip(),
                currentUserAgent: request()->userAgent(),
            );
        }

        public function revokeSession(string $sessionKey): void
        {
            $this->validate([
                'session_password' => [
                    'required',
                    'string',
                    'current_password:web',
                ],
            ], [
                'session_password.required' => 'Confirma tu contraseña para cerrar esta sesión.',
                'session_password.current_password' => 'La contraseña no es correcta.',
            ]);

            $user = auth()->user();
            $revoked = app(AccountSessionManager::class)->revokeSession(
                user: $user,
                sessionKey: $sessionKey,
                currentSessionId: session()->getId(),
            );

            if (! $revoked) {
                $this->addError('session_password', 'La sesión ya no existe, no pertenece a tu cuenta o es la sesión actual.');

                return;
            }

            app(AuditLogger::class)->safeLog(
                action: 'account.session.revoked',
                auditable: $user,
                description: 'El usuario revocó una sesión activa de su cuenta.',
                metadata: [
                    'session_fingerprint' => substr($sessionKey, 0, 16),
                ],
            );

            $this->reset('session_password');

            session()->flash(
                'security_success',
                'La sesión seleccionada fue cerrada correctamente.'
            );
        }

        public function revokeOtherSessions(): void
        {
            $this->validate([
                'revoke_all_sessions_password' => [
                    'required',
                    'string',
                    'current_password:web',
                ],
            ], [
                'revoke_all_sessions_password.required' => 'Confirma tu contraseña para cerrar las demás sesiones.',
                'revoke_all_sessions_password.current_password' => 'La contraseña no es correcta.',
            ]);

            $user = auth()->user();
            $revokedCount = app(AccountSessionManager::class)->revokeOtherSessions(
                user: $user,
                currentSessionId: session()->getId(),
            );

            app(AuditLogger::class)->safeLog(
                action: 'account.sessions.others_revoked',
                auditable: $user,
                description: 'El usuario cerró las demás sesiones activas de su cuenta.',
                metadata: [
                    'revoked_count' => $revokedCount,
                ],
            );

            $this->reset('revoke_all_sessions_password');

            session()->flash(
                'security_success',
                $revokedCount === 0
                    ? 'No había otras sesiones activas para cerrar.'
                    : 'Se cerraron '.$revokedCount.' '.($revokedCount === 1 ? 'sesión adicional.' : 'sesiones adicionales.')
            );
        }

        private function authorizeSensitiveTwoFactorView(string $type): void
        {
            session()->put(
                'security.two_factor.'.$type.'.authorized_at',
                now()->timestamp
            );
        }

        private function sensitiveTwoFactorViewIsAuthorized(string $type): bool
        {
            $authorizedAt = (int) session(
                'security.two_factor.'.$type.'.authorized_at',
                0
            );

            return $authorizedAt > 0
                && $authorizedAt >= now()->subMinutes(5)->timestamp;
        }

        private function forgetSensitiveTwoFactorViewAuthorization(string $type): void
        {
            session()->forget('security.two_factor.'.$type.'.authorized_at');
        }
    };
?>

<div class="mx-auto w-full max-w-6xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-7 overflow-hidden rounded-3xl bg-slate-950 text-white shadow-sm">
        <div class="grid gap-6 px-6 py-7 lg:grid-cols-[1fr_auto] lg:items-center lg:px-8">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-blue-300">Configuración</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">Seguridad de la cuenta</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300">
                    Protege el acceso a DocTotal y administra las credenciales de tu cuenta.
                </p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/10 px-5 py-4 backdrop-blur">
                <p class="text-xs text-slate-300">Cuenta</p>
                <p class="mt-1 max-w-72 truncate text-sm font-semibold">{{ auth()->user()->email }}</p>
            </div>
        </div>
    </div>

    <div class="mb-7 border-b border-slate-200">
        <nav class="-mb-px flex gap-7 overflow-x-auto">
            <a href="{{ route('settings.profile') }}" class="whitespace-nowrap border-b-2 border-transparent px-1 pb-3 text-sm font-medium text-slate-500 transition hover:border-slate-300 hover:text-slate-900">Perfil y consultorio</a>
            <a href="{{ route('settings.billing') }}" class="whitespace-nowrap border-b-2 border-transparent px-1 pb-3 text-sm font-medium text-slate-500 transition hover:border-slate-300 hover:text-slate-900">Facturación</a>
            <a href="{{ route('settings.security') }}" class="whitespace-nowrap border-b-2 border-blue-600 px-1 pb-3 text-sm font-semibold text-blue-700">Seguridad</a>
        </nav>
    </div>

    @if (session('security_success'))
        <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ session('security_success') }}
        </div>
    @endif

    <div class="space-y-6">
        <section class="dt-card overflow-hidden">
            <div class="dt-card-header flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="5" width="18" height="14" rx="2" />
                        <path d="m4 7 8 6 8-6" />
                    </svg>
                </div>
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-semibold text-slate-950">Correo electrónico de acceso</h2>
                        @if (auth()->user()->hasVerifiedEmail())
                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Verificado</span>
                        @else
                            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">Pendiente de verificar</span>
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-slate-500">
                        Confirma que la dirección utilizada para iniciar sesión realmente te pertenece.
                    </p>
                </div>
            </div>

            <div class="dt-card-body">
                <div class="grid gap-5 lg:grid-cols-[1fr_auto] lg:items-center">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Correo registrado</p>
                        <p class="mt-1 break-all text-sm font-semibold text-slate-900">{{ auth()->user()->email }}</p>
                        @if (auth()->user()->hasVerifiedEmail())
                            <p class="mt-2 text-sm leading-6 text-slate-500">Tu correo está verificado y puede utilizarse con normalidad para acceder a las áreas protegidas de DocTotal.</p>
                        @else
                            <p class="mt-2 text-sm leading-6 text-slate-500">Debes verificar este correo antes de entrar al consultorio, facturación o administración interna. Seguridad permanece disponible para que puedas proteger tu cuenta.</p>
                        @endif
                    </div>

                    @if (! auth()->user()->hasVerifiedEmail())
                        <form method="POST" action="{{ route('verification.send') }}" class="lg:min-w-56">
                            @csrf
                            <button type="submit" class="dt-btn dt-btn-primary w-full justify-center">Reenviar verificación</button>
                        </form>
                    @endif
                </div>

                @if (session('status') === 'verification-link-sent')
                    <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                        Te enviamos un nuevo enlace de verificación.
                    </div>
                @endif
            </div>
        </section>

        <section class="dt-card overflow-hidden">
            <div class="dt-card-header flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 3 4 6v5c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V6Z" />
                        <path d="M9 12h6M12 9v6" />
                    </svg>
                </div>
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-semibold text-slate-950">Verificación en dos pasos</h2>
                        @if ($this->twoFactorEnabled())
                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Activa</span>
                        @elseif ($this->twoFactorSetupPending())
                            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">Configuración pendiente</span>
                        @else
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">Inactiva</span>
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-slate-500">
                        Añade un código temporal de tu aplicación autenticadora al proceso de inicio de sesión.
                    </p>
                </div>
            </div>

            <div class="dt-card-body space-y-6">
                @if (! $this->twoFactorEnabled())
                    @if (! $showingQrCode)
                        <div class="grid gap-5 lg:grid-cols-[1fr_280px] lg:items-end">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900">{{ $this->twoFactorSetupPending() ? 'Retoma la configuración pendiente' : 'Activa una segunda capa de acceso' }}</h3>
                                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                                    {{ $this->twoFactorSetupPending()
                                        ? 'Confirma tu contraseña para volver a mostrar el QR y terminar la activación sin generar un secreto nuevo.'
                                        : 'Necesitarás una aplicación compatible con códigos TOTP, como un autenticador instalado en tu teléfono. Confirma tu contraseña para iniciar la configuración.' }}
                                </p>
                            </div>
                            <form wire:submit="enableTwoFactorAuthentication" class="space-y-3">
                                <div>
                                    <label for="two_factor_password" class="dt-label">Contraseña actual</label>
                                    <input id="two_factor_password" wire:model="two_factor_password" type="password" autocomplete="current-password" class="dt-input">
                                    @error('two_factor_password')
                                        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <button type="submit" class="dt-btn dt-btn-primary w-full justify-center" wire:loading.attr="disabled" wire:target="enableTwoFactorAuthentication">
                                    <span wire:loading.remove wire:target="enableTwoFactorAuthentication">{{ $this->twoFactorSetupPending() ? 'Retomar configuración' : 'Configurar 2FA' }}</span>
                                    <span wire:loading wire:target="enableTwoFactorAuthentication">Preparando…</span>
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="grid gap-6 lg:grid-cols-[220px_1fr]">
                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                @if ($this->twoFactorQrCodeSvg())
                                    <div class="mx-auto w-fit rounded-xl bg-white p-2">
                                        {!! $this->twoFactorQrCodeSvg() !!}
                                    </div>
                                @endif
                            </div>

                            <div>
                                <h3 class="text-base font-semibold text-slate-950">Escanea el código QR</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-500">
                                    Escanéalo con tu aplicación autenticadora. Después escribe el código de 6 dígitos que aparezca para confirmar que la configuración funciona.
                                </p>

                                <form wire:submit="confirmTwoFactorAuthentication" class="mt-5 max-w-sm space-y-3">
                                    <div>
                                        <label for="two_factor_code" class="dt-label">Código de autenticación</label>
                                        <input id="two_factor_code" wire:model="two_factor_code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" class="dt-input" placeholder="000000">
                                        @error('two_factor_code')
                                            <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <button type="submit" class="dt-btn dt-btn-primary" wire:loading.attr="disabled" wire:target="confirmTwoFactorAuthentication">
                                        Confirmar y activar
                                    </button>
                                </form>

                                <div class="mt-6 border-t border-slate-100 pt-5">
                                    <p class="text-xs text-slate-500">Si decidiste no continuar, puedes eliminar esta configuración pendiente.</p>
                                    <form wire:submit="disableTwoFactorAuthentication" class="mt-3 flex max-w-lg flex-col gap-3 sm:flex-row sm:items-end">
                                        <div class="flex-1">
                                            <label for="disable_pending_two_factor_password" class="dt-label">Contraseña actual</label>
                                            <input id="disable_pending_two_factor_password" wire:model="disable_two_factor_password" type="password" autocomplete="current-password" class="dt-input">
                                            @error('disable_two_factor_password')
                                                <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <button type="submit" class="dt-btn dt-btn-secondary">Cancelar configuración</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="grid gap-6 lg:grid-cols-2">
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-5">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 3 4 6v5c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V6Z" />
                                        <path d="m9 12 2 2 4-4" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-emerald-950">2FA está protegiendo tu cuenta</h3>
                                    <p class="text-xs text-emerald-800/80">Se solicitará un segundo factor al iniciar sesión.</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 p-5">
                            <h3 class="text-sm font-semibold text-slate-950">Códigos de recuperación</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-500">
                                Úsalos si pierdes acceso a tu aplicación autenticadora. Cada código funciona una sola vez.
                            </p>

                            @if ($showingRecoveryCodes)
                                <div class="mt-4 grid gap-2 rounded-2xl bg-slate-950 p-4 sm:grid-cols-2">
                                    @foreach ($this->recoveryCodes() as $recoveryCode)
                                        <code class="select-all text-sm text-slate-100">{{ $recoveryCode }}</code>
                                    @endforeach
                                </div>
                                <button type="button" wire:click="hideRecoveryCodes" class="mt-3 text-xs font-semibold text-slate-500 hover:text-slate-900">Ocultar códigos</button>
                            @endif

                            <form wire:submit="{{ $showingRecoveryCodes ? 'regenerateRecoveryCodes' : 'showRecoveryCodes' }}" class="mt-4 space-y-3">
                                <div>
                                    <label for="recovery_codes_password" class="dt-label">Contraseña actual</label>
                                    <input id="recovery_codes_password" wire:model="recovery_codes_password" type="password" autocomplete="current-password" class="dt-input">
                                    @error('recovery_codes_password')
                                        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <button type="submit" class="dt-btn dt-btn-secondary">
                                    {{ $showingRecoveryCodes ? 'Generar códigos nuevos' : 'Mostrar códigos' }}
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="border-t border-slate-100 pt-6">
                        <div class="grid gap-5 lg:grid-cols-[1fr_320px] lg:items-end">
                            <div>
                                <h3 class="text-sm font-semibold text-rose-700">Desactivar verificación en dos pasos</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-500">
                                    Tu cuenta volverá a depender únicamente de la contraseña. Los códigos de recuperación actuales se eliminarán.
                                </p>
                            </div>
                            <form wire:submit="disableTwoFactorAuthentication" class="space-y-3">
                                <div>
                                    <label for="disable_two_factor_password" class="dt-label">Contraseña actual</label>
                                    <input id="disable_two_factor_password" wire:model="disable_two_factor_password" type="password" autocomplete="current-password" class="dt-input">
                                    @error('disable_two_factor_password')
                                        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <button type="submit" class="dt-btn dt-btn-danger w-full justify-center" wire:loading.attr="disabled" wire:target="disableTwoFactorAuthentication">
                                    Desactivar 2FA
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        <section class="dt-card overflow-hidden">
            <div class="dt-card-header flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="12" rx="2" />
                        <path d="M8 20h8M12 16v4" />
                    </svg>
                </div>
                <div class="flex-1">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="font-semibold text-slate-950">Sesiones y dispositivos</h2>
                            <p class="mt-1 text-sm text-slate-500">Revisa dónde está abierta tu cuenta y cierra accesos que ya no reconozcas o necesites.</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ count($this->activeSessions()) }} {{ count($this->activeSessions()) === 1 ? 'sesión' : 'sesiones' }}</span>
                    </div>
                </div>
            </div>

            <div class="dt-card-body space-y-6">
                <div class="space-y-3">
                    @foreach ($this->activeSessions() as $activeSession)
                        <div class="rounded-2xl border {{ $activeSession['is_current'] ? 'border-emerald-200 bg-emerald-50/50' : 'border-slate-200 bg-white' }} p-4">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-semibold text-slate-900">{{ $activeSession['device_label'] }}</p>
                                        @if ($activeSession['is_current'])
                                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Este dispositivo</span>
                                        @endif
                                    </div>
                                    <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-xs text-slate-500">
                                        <span>IP: {{ $activeSession['ip_address'] }}</span>
                                        <span>Última actividad: {{ $activeSession['last_active_at']->diffForHumans() }}</span>
                                    </div>
                                </div>

                                @if (! $activeSession['is_current'])
                                    <button
                                        type="button"
                                        wire:click="revokeSession('{{ $activeSession['key'] }}')"
                                        wire:loading.attr="disabled"
                                        wire:target="revokeSession('{{ $activeSession['key'] }}')"
                                        class="dt-btn dt-btn-secondary shrink-0">
                                        Cerrar sesión
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="grid gap-5 border-t border-slate-100 pt-6 lg:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-5">
                        <h3 class="text-sm font-semibold text-slate-950">Cerrar una sesión específica</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Escribe tu contraseña actual y después usa “Cerrar sesión” en el dispositivo que quieras revocar. La sesión actual nunca puede cerrarse desde esta lista.</p>
                        <div class="mt-4">
                            <label for="session_password" class="dt-label">Contraseña actual</label>
                            <input id="session_password" wire:model="session_password" type="password" autocomplete="current-password" class="dt-input">
                            @error('session_password')
                                <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <form wire:submit="revokeOtherSessions" class="rounded-2xl border border-rose-200 bg-rose-50/50 p-5">
                        <h3 class="text-sm font-semibold text-rose-800">Cerrar todas las demás sesiones</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Mantendremos abierto este dispositivo y cerraremos todas las demás sesiones de tu cuenta, incluso las que usaron “Recordarme”.</p>
                        <div class="mt-4">
                            <label for="revoke_all_sessions_password" class="dt-label">Contraseña actual</label>
                            <input id="revoke_all_sessions_password" wire:model="revoke_all_sessions_password" type="password" autocomplete="current-password" class="dt-input">
                            @error('revoke_all_sessions_password')
                                <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="dt-btn dt-btn-danger mt-4 w-full justify-center" wire:loading.attr="disabled" wire:target="revokeOtherSessions">
                            Cerrar todas las demás sesiones
                        </button>
                    </form>
                </div>

                <p class="text-xs leading-5 text-slate-400">
                    Los nombres de navegador y dispositivo se estiman a partir de la información que envía cada navegador. DocTotal no muestra ni expone identificadores internos de sesión.
                </p>
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
            <section class="dt-card overflow-hidden">
                <div class="dt-card-header flex items-start gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="5" y="10" width="14" height="10" rx="2" />
                            <path d="M8 10V7a4 4 0 0 1 8 0v3" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="font-semibold text-slate-950">Cambiar contraseña</h2>
                        <p class="mt-1 text-sm text-slate-500">Confirma tu contraseña actual antes de establecer una nueva.</p>
                    </div>
                </div>

                <form wire:submit="updatePassword" class="dt-card-body space-y-5">
                    <div>
                        <label for="current_password" class="dt-label">Contraseña actual</label>
                        <input id="current_password" wire:model="current_password" type="password" autocomplete="current-password" class="dt-input">
                        @error('current_password')
                            <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="password" class="dt-label">Nueva contraseña</label>
                            <input id="password" wire:model="password" type="password" autocomplete="new-password" class="dt-input">
                            @error('password')
                                <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="password_confirmation" class="dt-label">Confirmar nueva contraseña</label>
                            <input id="password_confirmation" wire:model="password_confirmation" type="password" autocomplete="new-password" class="dt-input">
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                        <p class="max-w-xl text-xs leading-5 text-slate-500">
                            La nueva contraseña debe cumplir las reglas de seguridad configuradas por DocTotal. Nunca almacenamos tu contraseña en texto legible.
                        </p>
                        <button type="submit" class="dt-btn dt-btn-primary" wire:loading.attr="disabled" wire:target="updatePassword">
                            <span wire:loading.remove wire:target="updatePassword">Actualizar contraseña</span>
                            <span wire:loading wire:target="updatePassword">Actualizando…</span>
                        </button>
                    </div>
                </form>
            </section>

            <aside class="space-y-5">
                <section class="dt-card overflow-hidden">
                    <div class="dt-card-body">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 3 4 6v5c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V6Z" />
                                    <path d="m9 12 2 2 4-4" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-slate-950">Acceso protegido</h3>
                                <p class="text-xs text-slate-500">Sesión autenticada</p>
                            </div>
                        </div>
                        <div class="mt-5 border-t border-slate-100 pt-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Correo de acceso</p>
                            <p class="mt-1 break-all text-sm font-medium text-slate-700">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl border border-blue-100 bg-blue-50/70 p-5">
                    <h3 class="text-sm font-semibold text-blue-950">Seguridad por capas</h3>
                    <p class="mt-2 text-sm leading-6 text-blue-800/80">
                        La contraseña, la verificación en dos pasos y las sesiones activas pueden administrarse desde esta pantalla. Si detectas un acceso que no reconoces, revócalo y considera actualizar tu contraseña.
                    </p>
                </section>
            </aside>
        </div>
    </div>
</div>
