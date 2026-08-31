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

    <title>Crear cuenta | DocTotal</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-[#f6f8fc] text-slate-900 antialiased">

    <div class="grid min-h-screen lg:grid-cols-[0.9fr_1.1fr]">

        {{-- Branding --}}
        <section class="relative hidden overflow-hidden bg-slate-950 px-10 py-12 text-white lg:flex">

            <div class="absolute -left-24 top-16 h-72 w-72 rounded-full bg-blue-600/20 blur-3xl"></div>

            <div class="absolute bottom-0 right-0 h-80 w-80 rounded-full bg-violet-600/20 blur-3xl"></div>

            <div class="relative mx-auto flex w-full max-w-xl flex-col justify-between">

                <div>

                    <img
                        src="{{ asset('images/branding/doctotal-logo-white.png') }}"
                        alt="DocTotal"
                        class="h-24 w-auto object-contain">

                    <div class="mt-16 max-w-lg">

                        <span
                            class="inline-flex rounded-full border border-white/10
                               bg-white/5 px-3 py-1 text-xs font-semibold
                               text-blue-200">

                            Empieza tu consultorio digital

                        </span>

                        <h1 class="mt-6 text-4xl font-bold leading-tight tracking-tight">
                            Organiza tu práctica desde el primer día.
                        </h1>

                        <p class="mt-5 max-w-md text-base leading-7 text-slate-400">
                            Crea tu cuenta, configura tu consultorio y empieza a
                            gestionar pacientes, citas, consultas y recetas en un solo lugar.
                        </p>

                    </div>

                </div>

                <div class="grid gap-3 sm:grid-cols-3">

                    <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">

                        <div
                            class="mb-3 flex h-9 w-9 items-center justify-center
                               rounded-xl bg-blue-400/10 text-blue-300">

                            <svg
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8">

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 6v12m6-6H6" />

                            </svg>

                        </div>

                        <p class="text-sm font-semibold">
                            Inicio rápido
                        </p>

                        <p class="mt-1 text-xs leading-5 text-slate-400">
                            Configura tu espacio en pocos minutos.
                        </p>

                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">

                        <div
                            class="mb-3 flex h-9 w-9 items-center justify-center
                               rounded-xl bg-violet-400/10 text-violet-300">

                            <svg
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8">

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M8 7h8M8 12h8M8 17h5M5 3h14v18H5z" />

                            </svg>

                        </div>

                        <p class="text-sm font-semibold">
                            Todo centralizado
                        </p>

                        <p class="mt-1 text-xs leading-5 text-slate-400">
                            Tu información clínica en un solo sistema.
                        </p>

                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">

                        <div
                            class="mb-3 flex h-9 w-9 items-center justify-center
                               rounded-xl bg-emerald-400/10 text-emerald-300">

                            <svg
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8">

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 3v18M3 12h18" />

                            </svg>

                        </div>

                        <p class="text-sm font-semibold">
                            Prueba incluida
                        </p>

                        <p class="mt-1 text-xs leading-5 text-slate-400">
                            {{ config('doctotal.trial_days') }} días gratis para conocer DocTotal.
                        </p>

                    </div>

                </div>

            </div>

        </section>

        {{-- Registration --}}
        <main class="flex items-center justify-center px-4 py-8 sm:px-6 lg:px-10">

            <div class="w-full max-w-2xl">

                {{-- Mobile brand --}}
                <div class="mb-8 lg:hidden">

                    <div class="flex items-center gap-3">

                        <div class="flex h-11 w-11 shrink-0 items-center justify-center">

                            <img
                                src="{{ asset('images/branding/doctotal-icon.png') }}"
                                alt="DocTotal"
                                class="h-11 w-11 object-contain">

                        </div>

                        <div>

                            <p class="text-lg font-bold tracking-tight text-slate-950">
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

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">

                        <div>

                            <p class="text-sm font-semibold text-blue-600">
                                Tu espacio en DocTotal
                            </p>

                            <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                                Crea tu cuenta
                            </h2>

                            <p class="mt-2 text-sm leading-6 text-slate-500">
                                Completa tus datos para comenzar a configurar tu consultorio digital.
                            </p>

                        </div>

                        <div
                            class="inline-flex w-fit items-center rounded-full
                               border border-blue-100 bg-blue-50
                               px-3 py-1 text-xs font-semibold text-blue-700">

                            {{ config('doctotal.trial_days') }} días gratis

                        </div>

                    </div>

                    @if ($errors->any())

                    <div
                        class="mt-6 rounded-2xl border border-rose-200
                           bg-rose-50 px-4 py-3 text-sm text-rose-700">

                        <p class="font-semibold">
                            Revisa los siguientes datos:
                        </p>

                        <ul class="mt-2 list-disc space-y-1 pl-5">

                            @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach

                        </ul>

                    </div>

                    @endif

                    <form
                        method="POST"
                        action="{{ route('register') }}"
                        class="mt-7 space-y-5">

                        @csrf

                        {{-- Practice --}}
                        <div>

                            <label
                                for="practice_name"
                                class="mb-1.5 block text-sm font-medium text-slate-700">

                                Nombre del consultorio

                            </label>

                            <input
                                id="practice_name"
                                name="practice_name"
                                type="text"
                                value="{{ old('practice_name') }}"
                                required
                                autofocus
                                autocomplete="organization"
                                placeholder="Ej. Consultorio Dr. Juan Pérez"
                                class="w-full rounded-xl border border-slate-300
                                   bg-white px-4 py-3 text-sm text-slate-900
                                   outline-none transition
                                   placeholder:text-slate-400
                                   focus:border-blue-500
                                   focus:ring-4 focus:ring-blue-100">

                        </div>

                        {{-- Doctor --}}
                        <div class="grid gap-5 sm:grid-cols-2">

                            <div>

                                <label
                                    for="first_name"
                                    class="mb-1.5 block text-sm font-medium text-slate-700">

                                    Nombre

                                </label>

                                <input
                                    id="first_name"
                                    name="first_name"
                                    type="text"
                                    value="{{ old('first_name') }}"
                                    required
                                    autocomplete="given-name"
                                    class="w-full rounded-xl border border-slate-300
                                       bg-white px-4 py-3 text-sm
                                       outline-none transition
                                       focus:border-blue-500
                                       focus:ring-4 focus:ring-blue-100">

                            </div>

                            <div>

                                <label
                                    for="last_name"
                                    class="mb-1.5 block text-sm font-medium text-slate-700">

                                    Apellido

                                </label>

                                <input
                                    id="last_name"
                                    name="last_name"
                                    type="text"
                                    value="{{ old('last_name') }}"
                                    required
                                    autocomplete="family-name"
                                    class="w-full rounded-xl border border-slate-300
                                       bg-white px-4 py-3 text-sm
                                       outline-none transition
                                       focus:border-blue-500
                                       focus:ring-4 focus:ring-blue-100">

                            </div>

                        </div>

                        {{-- Email --}}
                        <div>

                            <label
                                for="email"
                                class="mb-1.5 block text-sm font-medium text-slate-700">

                                Correo electrónico

                            </label>

                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                required
                                autocomplete="email"
                                placeholder="doctor@ejemplo.com"
                                class="w-full rounded-xl border border-slate-300
                                   bg-white px-4 py-3 text-sm
                                   outline-none transition
                                   placeholder:text-slate-400
                                   focus:border-blue-500
                                   focus:ring-4 focus:ring-blue-100">

                        </div>

                        {{-- Passwords --}}
                        <div class="grid gap-5 sm:grid-cols-2">

                            <div>

                                <label
                                    for="password"
                                    class="mb-1.5 block text-sm font-medium text-slate-700">

                                    Contraseña

                                </label>

                                <div class="relative">

                                    <input
                                        id="password"
                                        name="password"
                                        type="password"
                                        required
                                        autocomplete="new-password"
                                        class="w-full rounded-xl border border-slate-300
                                           bg-white px-4 py-3 pr-12 text-sm
                                           outline-none transition
                                           focus:border-blue-500
                                           focus:ring-4 focus:ring-blue-100">

                                    <button
                                        type="button"
                                        data-password-toggle="password"
                                        class="absolute inset-y-0 right-0
                                           flex items-center px-3
                                           text-slate-400 transition
                                           hover:text-slate-600"
                                        aria-label="Mostrar u ocultar contraseña">

                                        <svg
                                            class="h-5 w-5"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8">

                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" />

                                            <circle
                                                cx="12"
                                                cy="12"
                                                r="2.5" />

                                        </svg>

                                    </button>

                                </div>

                            </div>

                            <div>

                                <label
                                    for="password_confirmation"
                                    class="mb-1.5 block text-sm font-medium text-slate-700">

                                    Confirmar contraseña

                                </label>

                                <div class="relative">

                                    <input
                                        id="password_confirmation"
                                        name="password_confirmation"
                                        type="password"
                                        required
                                        autocomplete="new-password"
                                        class="w-full rounded-xl border border-slate-300
                                           bg-white px-4 py-3 pr-12 text-sm
                                           outline-none transition
                                           focus:border-blue-500
                                           focus:ring-4 focus:ring-blue-100">

                                    <button
                                        type="button"
                                        data-password-toggle="password_confirmation"
                                        class="absolute inset-y-0 right-0
                                           flex items-center px-3
                                           text-slate-400 transition
                                           hover:text-slate-600"
                                        aria-label="Mostrar u ocultar confirmación de contraseña">

                                        <svg
                                            class="h-5 w-5"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8">

                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" />

                                            <circle
                                                cx="12"
                                                cy="12"
                                                r="2.5" />

                                        </svg>

                                    </button>

                                </div>

                            </div>

                        </div>

                        @php
                        $referralCode = old(
                        'referral_code',
                        request('ref')
                        );
                        @endphp

                        {{-- Referral --}}
                        @if ($referralCode)

                        <div
                            class="rounded-2xl border border-emerald-200
                               bg-emerald-50 p-4">

                            <input
                                type="hidden"
                                name="referral_code"
                                value="{{ $referralCode }}">

                            <p class="text-sm font-semibold text-emerald-900">

                                Código de referido aplicado:

                                <span class="uppercase">
                                    {{ strtoupper($referralCode) }}
                                </span>

                            </p>

                            <p class="mt-1 text-xs leading-5 text-emerald-700">
                                Tu invitación será asociada automáticamente al crear tu cuenta.
                            </p>

                        </div>

                        @else

                        <div>

                            <label
                                for="referral_code"
                                class="mb-1.5 block text-sm font-medium text-slate-700">

                                Código de referido

                                <span class="font-normal text-slate-400">
                                    (opcional)
                                </span>

                            </label>

                            <input
                                id="referral_code"
                                name="referral_code"
                                type="text"
                                value="{{ old('referral_code') }}"
                                autocomplete="off"
                                placeholder="Ej. ABC12345"
                                class="w-full rounded-xl border
                                   @error('referral_code')
                                       border-rose-300
                                   @else
                                       border-slate-300
                                   @enderror
                                   bg-white px-4 py-3
                                   text-sm uppercase
                                   outline-none transition
                                   focus:border-blue-500
                                   focus:ring-4 focus:ring-blue-100">

                            <p class="mt-1.5 text-xs leading-5 text-slate-500">
                                ¿Alguien te invitó a DocTotal?
                                Escribe aquí su código de referido.
                            </p>

                            @error('referral_code')

                            <p class="mt-1.5 text-xs text-rose-600">
                                {{ $message }}
                            </p>

                            @enderror

                        </div>

                        @endif

                        {{-- Trial --}}
                        <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4">

                            <p class="text-sm leading-6 text-blue-900">

                                Tendrás

                                <strong>
                                    {{ config('doctotal.trial_days') }} días gratis
                                </strong>

                                para conocer DocTotal y configurar tu consultorio.

                            </p>

                        </div>

                        <button
                            type="submit"
                            class="w-full rounded-xl
                               bg-gradient-to-r from-blue-600 to-violet-600
                               px-4 py-3.5
                               text-sm font-semibold text-white
                               shadow-lg shadow-blue-600/20
                               transition
                               hover:from-blue-700
                               hover:to-violet-700
                               focus:outline-none
                               focus:ring-4 focus:ring-blue-200">

                            Crear mi cuenta

                        </button>

                    </form>

                    <div class="mt-6 border-t border-slate-200 pt-6 text-center">

                        <p class="text-sm text-slate-500">

                            ¿Ya tienes una cuenta?

                            <a
                                href="{{ route('login') }}"
                                class="font-semibold text-blue-600
                                   transition hover:text-blue-700
                                   hover:underline">

                                Iniciar sesión

                            </a>

                        </p>

                    </div>

                </div>

                <p class="mt-5 text-center text-xs leading-5 text-slate-400">
                    Al crear una cuenta aceptas los términos y políticas de DocTotal.
                </p>

            </div>

        </main>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            document
                .querySelectorAll('[data-password-toggle]')
                .forEach(function(button) {

                    button.addEventListener('click', function() {

                        const inputId =
                            button.getAttribute('data-password-toggle');

                        const input =
                            document.getElementById(inputId);

                        if (!input) {
                            return;
                        }

                        input.type =
                            input.type === 'password' ?
                            'text' :
                            'password';

                    });

                });

        });
    </script>

</body>

</html>