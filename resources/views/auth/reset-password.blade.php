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

    <title>Nueva contraseña | DocTotal</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-[#f6f8fc] text-slate-900 antialiased">

    <div class="grid min-h-screen lg:grid-cols-[1.05fr_0.95fr]">

        {{-- Branding --}}
        <section
            class="relative hidden overflow-hidden
                   bg-slate-950 px-10 py-12 text-white lg:flex">

            <div
                class="absolute -left-24 top-16 h-72 w-72
                       rounded-full bg-blue-600/20 blur-3xl">
            </div>

            <div
                class="absolute bottom-0 right-0 h-80 w-80
                       rounded-full bg-violet-600/20 blur-3xl">
            </div>

            <div
                class="relative mx-auto flex w-full max-w-2xl
                       flex-col justify-between">

                <div>

                    <img
                        src="{{ asset('images/branding/doctotal-logo-white.png') }}"
                        alt="DocTotal"
                        class="h-24 w-auto object-contain">

                    <div class="mt-16 max-w-xl">

                        <span
                            class="inline-flex rounded-full
                                   border border-white/10
                                   bg-white/5 px-3 py-1
                                   text-xs font-semibold text-blue-200">

                            Último paso

                        </span>

                        <h1
                            class="mt-6 text-4xl font-bold
                                   leading-tight tracking-tight">

                            Crea una nueva contraseña segura.

                        </h1>

                        <p
                            class="mt-5 max-w-lg text-base
                                   leading-7 text-slate-400">

                            Después podrás volver a ingresar normalmente
                            a tu consultorio.

                        </p>

                    </div>

                </div>

                <div
                    class="rounded-2xl border border-white/10
                           bg-white/[0.04] p-4">

                    <div class="flex items-start gap-3">

                        <div
                            class="flex h-10 w-10 shrink-0
                                   items-center justify-center
                                   rounded-xl bg-blue-400/10
                                   text-blue-300">

                            <svg
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8">

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M7 10V8a5 5 0 0 1 10 0v2M5 10h14v10H5z" />

                            </svg>

                        </div>

                        <div>

                            <p class="text-sm font-semibold">
                                Protege tu cuenta
                            </p>

                            <p class="mt-1 text-xs leading-5 text-slate-400">
                                Utiliza una contraseña que no uses en otros servicios.
                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </section>

        {{-- Formulario --}}
        <main
            class="flex items-center justify-center
                   px-4 py-8 sm:px-6 lg:px-10">

            <div class="w-full max-w-md">

                {{-- Mobile brand --}}
                <div class="mb-8 lg:hidden">

                    <div class="flex items-center gap-3">

                        <div
                            class="flex h-11 w-11 shrink-0
                                   items-center justify-center">

                            <img
                                src="{{ asset('images/branding/doctotal-icon.png') }}"
                                alt="DocTotal"
                                class="h-11 w-11 object-contain">

                        </div>

                        <div>

                            <p
                                class="text-lg font-bold
                                       tracking-tight text-slate-950">

                                DocTotal

                            </p>

                            <p class="text-xs text-slate-500">
                                Gestión médica inteligente
                            </p>

                        </div>

                    </div>

                </div>

                <div
                    class="rounded-3xl border border-slate-200/80
                           bg-white p-6
                           shadow-[0_24px_60px_-32px_rgba(15,23,42,0.25)]
                           sm:p-8">

                    <div>

                        <p class="text-sm font-semibold text-blue-600">
                            Restablecer contraseña
                        </p>

                        <h2
                            class="mt-2 text-3xl font-bold
                                   tracking-tight text-slate-950">

                            Crea una nueva contraseña

                        </h2>

                        <p
                            class="mt-2 text-sm leading-6
                                   text-slate-500">

                            Confirma tu correo y elige una contraseña nueva
                            para tu cuenta.

                        </p>

                    </div>

                    @if ($errors->any())

                    <div
                        class="mt-6 rounded-2xl
                               border border-rose-200
                               bg-rose-50 px-4 py-3
                               text-sm text-rose-700">

                        {{ $errors->first() }}

                    </div>

                    @endif

                    <form
                        method="POST"
                        action="{{ route('password.update') }}"
                        class="mt-7">

                        @csrf

                        <input
                            type="hidden"
                            name="token"
                            value="{{ $request->route('token') }}">

                        {{-- Email --}}
                        <div>

                            <label
                                for="email"
                                class="dt-label">

                                Correo electrónico

                            </label>

                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email', $request->email) }}"
                                required
                                autocomplete="email"
                                class="dt-input">

                        </div>

                        {{-- Password --}}
                        <div class="mt-5">

                            <label
                                for="password"
                                class="dt-label">

                                Nueva contraseña

                            </label>

                            <div class="relative">

                                <span
                                    class="pointer-events-none
                                           absolute left-3 top-1/2
                                           -translate-y-1/2
                                           text-slate-400">

                                    <svg
                                        class="h-5 w-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8">

                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M7 10V8a5 5 0 0 1 10 0v2M5 10h14v10H5z" />

                                    </svg>

                                </span>

                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    required
                                    autocomplete="new-password"
                                    class="dt-input pl-10 pr-12">

                                <button
                                    type="button"
                                    onclick="togglePasswordVisibility(
                                        'password',
                                        'password-eye-open',
                                        'password-eye-closed'
                                    )"
                                    class="absolute right-3 top-1/2
                                           -translate-y-1/2
                                           text-slate-400 transition
                                           hover:text-slate-700"
                                    aria-label="Mostrar u ocultar contraseña">

                                    <svg
                                        id="password-eye-open"
                                        class="h-5 w-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8">

                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6S2.5 12 2.5 12Z" />

                                        <circle
                                            cx="12"
                                            cy="12"
                                            r="2.5" />

                                    </svg>

                                    <svg
                                        id="password-eye-closed"
                                        class="hidden h-5 w-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8">

                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8M9.9 5.2A10 10 0 0 1 12 5c6 0 9.5 7 9.5 7a17.8 17.8 0 0 1-2.3 3.2M6.5 6.5C4 8 2.5 12 2.5 12a18 18 0 0 0 4.4 4.8A9.3 9.3 0 0 0 12 19c1 0 1.9-.1 2.7-.4" />

                                    </svg>

                                </button>

                            </div>

                        </div>

                        {{-- Confirmation --}}
                        <div class="mt-5">

                            <label
                                for="password_confirmation"
                                class="dt-label">

                                Confirmar contraseña

                            </label>

                            <div class="relative">

                                <span
                                    class="pointer-events-none
                                           absolute left-3 top-1/2
                                           -translate-y-1/2
                                           text-slate-400">

                                    <svg
                                        class="h-5 w-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8">

                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M7 10V8a5 5 0 0 1 10 0v2M5 10h14v10H5z" />

                                    </svg>

                                </span>

                                <input
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    type="password"
                                    required
                                    autocomplete="new-password"
                                    class="dt-input pl-10 pr-12">

                                <button
                                    type="button"
                                    onclick="togglePasswordVisibility(
                                        'password_confirmation',
                                        'confirmation-eye-open',
                                        'confirmation-eye-closed'
                                    )"
                                    class="absolute right-3 top-1/2
                                           -translate-y-1/2
                                           text-slate-400 transition
                                           hover:text-slate-700"
                                    aria-label="Mostrar u ocultar confirmación de contraseña">

                                    <svg
                                        id="confirmation-eye-open"
                                        class="h-5 w-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8">

                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6S2.5 12 2.5 12Z" />

                                        <circle
                                            cx="12"
                                            cy="12"
                                            r="2.5" />

                                    </svg>

                                    <svg
                                        id="confirmation-eye-closed"
                                        class="hidden h-5 w-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8">

                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8M9.9 5.2A10 10 0 0 1 12 5c6 0 9.5 7 9.5 7a17.8 17.8 0 0 1-2.3 3.2M6.5 6.5C4 8 2.5 12 2.5 12a18 18 0 0 0 4.4 4.8A9.3 9.3 0 0 0 12 19c1 0 1.9-.1 2.7-.4" />

                                    </svg>

                                </button>

                            </div>

                        </div>

                        <button
                            type="submit"
                            class="mt-6 inline-flex w-full
                                   items-center justify-center
                                   rounded-xl
                                   bg-gradient-to-r
                                   from-blue-600 to-violet-600
                                   px-4 py-3
                                   text-sm font-semibold text-white
                                   shadow-lg shadow-blue-600/20
                                   transition
                                   hover:from-blue-700
                                   hover:to-violet-700
                                   focus:outline-none
                                   focus:ring-4 focus:ring-blue-200">

                            Guardar nueva contraseña

                        </button>

                    </form>

                    <div
                        class="mt-6 border-t border-slate-200
                               pt-6 text-center">

                        <a
                            href="{{ route('login') }}"
                            class="inline-flex items-center gap-1
                                   text-sm font-semibold
                                   text-blue-600 transition
                                   hover:text-blue-700">

                            <span aria-hidden="true">←</span>
                            Volver a iniciar sesión

                        </a>

                    </div>

                </div>

                <p class="mt-5 text-center text-xs text-slate-400">
                    DocTotal · Recuperación segura
                </p>

            </div>

        </main>

    </div>

    <script>
        function togglePasswordVisibility(inputId, openId, closedId) {

            const input = document.getElementById(inputId);
            const eyeOpen = document.getElementById(openId);
            const eyeClosed = document.getElementById(closedId);

            if (!input) {
                return;
            }

            const showing = input.type === 'text';

            input.type = showing ?
                'password' :
                'text';

            if (eyeOpen && eyeClosed) {

                eyeOpen.classList.toggle(
                    'hidden',
                    !showing
                );

                eyeClosed.classList.toggle(
                    'hidden',
                    showing
                );

            }

        }
    </script>

</body>

</html>