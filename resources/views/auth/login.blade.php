<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="icon" href="{{ asset('images/branding/favicon.ico') }}" sizes="any">

    <link
        rel="icon"
        type="image/png"
        sizes="32x32"
        href="{{ asset('images/branding/favicon-32x32.png') }}">

    <link
        rel="icon"
        type="image/png"
        sizes="16x16"
        href="{{ asset('images/branding/favicon-16x16.png') }}">

    <link
        rel="apple-touch-icon"
        href="{{ asset('images/branding/apple-touch-icon.png') }}">

    <title>Iniciar sesión | DocTotal</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-[#f6f8fc] text-slate-900 antialiased">

    <div class="grid min-h-screen lg:grid-cols-[1.05fr_0.95fr]">

        {{-- Branding --}}
        <section class="relative hidden overflow-hidden bg-slate-950 px-10 py-12 text-white lg:flex">
            <div class="absolute -left-24 top-16 h-72 w-72 rounded-full bg-blue-600/20 blur-3xl"></div>
            <div class="absolute bottom-0 right-0 h-80 w-80 rounded-full bg-violet-600/20 blur-3xl"></div>

            <div class="relative mx-auto flex w-full max-w-2xl flex-col justify-between">
                <div>
                    <div>
                        <img
                            src="{{ asset('images/branding/doctotal-logo-white.png') }}"
                            alt="DocTotal"
                            class="h-40 w-auto object-contain">
                    </div>

                    <div class="mt-20 max-w-xl">
                        <span class="inline-flex rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold text-blue-200">
                            Tu consultorio, en un solo lugar
                        </span>

                        <h1 class="mt-6 text-4xl font-bold leading-tight tracking-tight">
                            Más claridad para tu consulta. Menos fricción en tu día.
                        </h1>

                        <p class="mt-5 max-w-lg text-base leading-7 text-slate-400">
                            Administra pacientes, citas, consultas y recetas desde una experiencia simple, moderna y centralizada.
                        </p>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                        <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-xl bg-blue-400/10 text-blue-300">
                            <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 2v3m8-3v3M3 9h18M5 5h14a2 2 0 0 1 2 2v12H3V7a2 2 0 0 1 2-2Z" />
                            </svg>
                        </div>
                        <p class="text-sm font-semibold">Agenda</p>
                        <p class="mt-1 text-xs leading-5 text-slate-400">Tus citas siempre organizadas.</p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                        <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-xl bg-violet-400/10 text-violet-300">
                            <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19a6 6 0 0 0-12 0m6-8a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm6 1h6" />
                            </svg>
                        </div>
                        <p class="text-sm font-semibold">Pacientes</p>
                        <p class="mt-1 text-xs leading-5 text-slate-400">Información clínica accesible.</p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                        <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-400/10 text-emerald-300">
                            <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-3-3v6M5 4h14v16H5z" />
                            </svg>
                        </div>
                        <p class="text-sm font-semibold">Consulta</p>
                        <p class="mt-1 text-xs leading-5 text-slate-400">Flujo clínico más sencillo.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Login --}}
        <main class="flex items-center justify-center px-4 py-8 sm:px-6 lg:px-10">
            <div class="w-full max-w-md">
                <div class="mb-8 lg:hidden">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center">
                            <img
                                src="{{ asset('images/branding/doctotal-icon.png') }}"
                                alt="DocTotal"
                                class="h-11 w-11 object-contain">
                        </div>

                        <div>
                            <p class="text-lg font-bold tracking-tight text-slate-950">DocTotal</p>
                            <p class="text-xs text-slate-500">Gestión médica inteligente</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-[0_24px_60px_-32px_rgba(15,23,42,0.25)] sm:p-8">
                    <div>
                        <p class="text-sm font-semibold text-blue-600">Bienvenido de nuevo</p>
                        <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                            Inicia sesión
                        </h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">
                            Ingresa a tu consultorio y continúa donde lo dejaste.
                        </p>
                    </div>

                    @if ($errors->any())
                    <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        {{ $errors->first() }}
                    </div>
                    @endif

                    <form method="POST" action="/login" class="mt-7">
                        @csrf

                        <div>
                            <label for="email" class="dt-label">
                                Correo electrónico
                            </label>

                            <div class="relative">
                                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                                    <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4 6 8 6 8-6M4 6h16v12H4z" />
                                    </svg>
                                </span>

                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    value="{{ old('email') }}"
                                    required
                                    autofocus
                                    autocomplete="email"
                                    placeholder="doctor@ejemplo.com"
                                    class="dt-input pl-10">
                            </div>
                        </div>

                        <div class="mt-5">
                            <label for="password" class="dt-label">
                                Contraseña
                            </label>

                            <div class="relative">
                                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                                    <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 10V8a5 5 0 0 1 10 0v2M5 10h14v10H5z" />
                                    </svg>
                                </span>

                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    required
                                    autocomplete="current-password"
                                    placeholder="••••••••"
                                    class="dt-input pl-10 pr-12">

                                <button
                                    type="button"
                                    onclick="togglePasswordVisibility()"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-slate-700"
                                    aria-label="Mostrar u ocultar contraseña">
                                    <svg id="password-eye-open" class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6S2.5 12 2.5 12Z" />
                                        <circle cx="12" cy="12" r="2.5" />
                                    </svg>

                                    <svg id="password-eye-closed" class="hidden h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8M9.9 5.2A10 10 0 0 1 12 5c6 0 9.5 7 9.5 7a17.8 17.8 0 0 1-2.3 3.2M6.5 6.5C4 8 2.5 12 2.5 12a18 18 0 0 0 4.4 4.8A9.3 9.3 0 0 0 12 19c1 0 1.9-.1 2.7-.4" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="mt-5 flex items-center justify-between gap-4">
                            <label class="flex items-center gap-3 text-sm text-slate-600">
                                <input
                                    type="checkbox"
                                    name="remember"
                                    class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">

                                <span>Recordarme</span>
                            </label>

                            <a
                                href="{{ route('password.request') }}"
                                class="text-sm font-semibold text-blue-600 transition hover:text-blue-700">
                                ¿Olvidaste tu contraseña?
                            </a>
                        </div>

                        <button
                            type="submit"
                            class="mt-6 inline-flex w-full items-center justify-center rounded-xl
                                   bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-3
                                   text-sm font-semibold text-white shadow-lg shadow-blue-600/20
                                   transition hover:from-blue-700 hover:to-indigo-700">
                            Iniciar sesión
                        </button>
                    </form>

                    <div class="mt-6 border-t border-slate-200 pt-6 text-center">
                        <p class="text-sm text-slate-500">
                            ¿Aún no tienes cuenta?
                            <a
                                href="{{ route('register') }}"
                                class="font-semibold text-blue-600 transition hover:text-blue-700">
                                Crear una cuenta
                            </a>
                        </p>
                    </div>
                </div>

                <p class="mt-5 text-center text-xs text-slate-400">
                    DocTotal · Acceso seguro a tu consultorio
                </p>
            </div>
        </main>
    </div>

    <script>
        function togglePasswordVisibility() {
            const input = document.getElementById('password');
            const eyeOpen = document.getElementById('password-eye-open');
            const eyeClosed = document.getElementById('password-eye-closed');

            if (!input) {
                return;
            }

            const showing = input.type === 'text';

            input.type = showing ? 'password' : 'text';

            if (eyeOpen && eyeClosed) {
                eyeOpen.classList.toggle('hidden', !showing);
                eyeClosed.classList.toggle('hidden', showing);
            }
        }
    </script>

</body>

</html>