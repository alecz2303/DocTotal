<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('images/branding/favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/branding/favicon-32x32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/branding/apple-touch-icon.png') }}">
    <title>Verifica tu correo | DocTotal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f6f8fc] text-slate-900 antialiased">
    <div class="grid min-h-screen lg:grid-cols-[0.9fr_1.1fr]">
        <section class="relative hidden overflow-hidden bg-slate-950 px-10 py-12 text-white lg:flex">
            <div class="absolute -left-24 top-16 h-72 w-72 rounded-full bg-blue-600/20 blur-3xl"></div>
            <div class="absolute bottom-0 right-0 h-80 w-80 rounded-full bg-violet-600/20 blur-3xl"></div>
            <div class="relative mx-auto flex w-full max-w-xl flex-col justify-between">
                <div>
                    <img src="{{ asset('images/branding/doctotal-logo-white.png') }}" alt="DocTotal" class="h-24 w-auto object-contain">
                    <div class="mt-16 max-w-lg">
                        <span class="inline-flex rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold text-blue-200">Seguridad de la cuenta</span>
                        <h1 class="mt-6 text-4xl font-bold leading-tight tracking-tight">Un paso más para proteger tu consultorio.</h1>
                        <p class="mt-5 max-w-md text-base leading-7 text-slate-400">Confirma que la dirección de correo registrada te pertenece antes de continuar usando las áreas protegidas de DocTotal.</p>
                    </div>
                </div>
                <p class="text-sm text-slate-500">DocTotal · Gestión médica inteligente</p>
            </div>
        </section>

        <main class="flex items-center justify-center px-4 py-8 sm:px-6 lg:px-10">
            <div class="w-full max-w-lg">
                <div class="mb-8 lg:hidden">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('images/branding/doctotal-icon.png') }}" alt="DocTotal" class="h-11 w-11 object-contain">
                        <div>
                            <p class="text-lg font-bold tracking-tight text-slate-950">DocTotal</p>
                            <p class="text-xs text-slate-500">Gestión médica inteligente</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xl shadow-slate-200/40 sm:p-8">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <rect x="3" y="5" width="18" height="14" rx="2" />
                            <path d="m4 7 8 6 8-6" />
                        </svg>
                    </div>

                    <h2 class="mt-5 text-2xl font-bold tracking-tight text-slate-950">Verifica tu correo electrónico</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-500">Enviamos un enlace de verificación a:</p>
                    <p class="mt-1 break-all text-sm font-semibold text-slate-900">{{ auth()->user()->email }}</p>

                    @if (session('status') === 'verification-link-sent')
                        <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">Te enviamos un nuevo enlace de verificación.</div>
                    @endif

                    <p class="mt-5 text-sm leading-6 text-slate-600">Abre el mensaje y presiona <strong>“Verificar mi correo”</strong>. Si no lo encuentras, revisa también la carpeta de correo no deseado.</p>

                    <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
                        @csrf
                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition hover:from-blue-700 hover:to-indigo-700">Reenviar enlace de verificación</button>
                    </form>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <a href="{{ route('settings.security') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Ir a Seguridad</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Cerrar sesión</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
