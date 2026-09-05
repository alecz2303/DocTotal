<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('images/branding/favicon.ico') }}" sizes="any">
    <title>Verificación en dos pasos | DocTotal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f6f8fc] text-slate-900 antialiased">
    <main class="flex min-h-screen items-center justify-center px-4 py-10 sm:px-6">
        <div class="w-full max-w-lg">
            <div class="mb-7 flex items-center justify-center">
                <img src="{{ asset('images/branding/doctotal-icon.png') }}" alt="DocTotal" class="h-14 w-14 object-contain">
            </div>

            <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-[0_24px_60px_-32px_rgba(15,23,42,0.25)] sm:p-8">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 3 4 6v5c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V6Z" />
                        <path d="M9 12h6M12 9v6" />
                    </svg>
                </div>

                <h1 class="mt-5 text-2xl font-bold tracking-tight text-slate-950">Verificación en dos pasos</h1>
                <p class="mt-2 text-sm leading-6 text-slate-500">
                    Escribe el código de 6 dígitos de tu aplicación autenticadora para completar el inicio de sesión.
                </p>

                @if ($errors->any())
                    <div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        No pudimos validar el código. Revisa el dato e inténtalo nuevamente.
                    </div>
                @endif

                <form method="POST" action="{{ route('two-factor.login.store') }}" class="mt-6 space-y-5">
                    @csrf
                    <div>
                        <label for="code" class="dt-label">Código de autenticación</label>
                        <input
                            id="code"
                            name="code"
                            type="text"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            maxlength="6"
                            autofocus
                            class="dt-input"
                            placeholder="000000"
                        >
                    </div>

                    <button type="submit" class="dt-btn dt-btn-primary w-full justify-center">
                        Verificar y continuar
                    </button>
                </form>

                <div class="my-6 flex items-center gap-3 text-xs text-slate-400">
                    <span class="h-px flex-1 bg-slate-200"></span>
                    <span>o usa un código de recuperación</span>
                    <span class="h-px flex-1 bg-slate-200"></span>
                </div>

                <form method="POST" action="{{ route('two-factor.login.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="recovery_code" class="dt-label">Código de recuperación</label>
                        <input
                            id="recovery_code"
                            name="recovery_code"
                            type="text"
                            autocomplete="one-time-code"
                            class="dt-input font-mono"
                            placeholder="xxxx-xxxxxx"
                        >
                    </div>

                    <button type="submit" class="dt-btn dt-btn-secondary w-full justify-center">
                        Usar código de recuperación
                    </button>
                </form>

                <p class="mt-6 text-center text-xs leading-5 text-slate-400">
                    Cada código de recuperación solo puede utilizarse una vez.
                </p>
            </section>
        </div>
    </main>
</body>
</html>
