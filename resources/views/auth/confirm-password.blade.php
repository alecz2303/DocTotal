<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('images/branding/favicon.ico') }}" sizes="any">
    <title>Confirmar contraseña | DocTotal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f6f8fc] text-slate-900 antialiased">
    <main class="flex min-h-screen items-center justify-center px-4 py-10 sm:px-6">
        <div class="w-full max-w-md">
            <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-[0_24px_60px_-32px_rgba(15,23,42,0.25)] sm:p-8">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="5" y="10" width="14" height="10" rx="2" />
                        <path d="M8 10V7a4 4 0 0 1 8 0v3" />
                    </svg>
                </div>

                <h1 class="mt-5 text-2xl font-bold tracking-tight text-slate-950">Confirma tu contraseña</h1>
                <p class="mt-2 text-sm leading-6 text-slate-500">
                    Esta acción modifica la seguridad de tu cuenta. Confirma tu contraseña antes de continuar.
                </p>

                <form method="POST" action="{{ route('password.confirm.store') }}" class="mt-6 space-y-5">
                    @csrf
                    <div>
                        <label for="password" class="dt-label">Contraseña</label>
                        <input id="password" name="password" type="password" autocomplete="current-password" autofocus class="dt-input">
                        @error('password')
                            <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="dt-btn dt-btn-primary w-full justify-center">
                        Confirmar contraseña
                    </button>
                </form>
            </section>
        </div>
    </main>
</body>
</html>
