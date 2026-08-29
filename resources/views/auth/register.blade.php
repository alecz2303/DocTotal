<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Crear cuenta | DocTotal</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-100 flex items-center justify-center px-4 py-10">

    <div class="w-full max-w-lg">

        <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">

            <div class="mb-8 text-center">
                <h1 class="text-3xl font-bold tracking-tight text-slate-900">
                    DocTotal
                </h1>

                <p class="mt-2 text-sm text-slate-500">
                    Crea tu consultorio digital
                </p>
            </div>

            @if ($errors->any())
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form
                method="POST"
                action="{{ route('register') }}"
                class="space-y-5">
                @csrf

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
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm
                               outline-none transition focus:border-slate-500 focus:ring-2
                               focus:ring-slate-200">
                </div>

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
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm
                                   outline-none transition focus:border-slate-500 focus:ring-2
                                   focus:ring-slate-200">
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
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm
                                   outline-none transition focus:border-slate-500 focus:ring-2
                                   focus:ring-slate-200">
                    </div>

                </div>

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
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm
                               outline-none transition focus:border-slate-500 focus:ring-2
                               focus:ring-slate-200">
                </div>

                <div>
                    <label
                        for="password"
                        class="mb-1.5 block text-sm font-medium text-slate-700">
                        Contraseña
                    </label>

                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        autocomplete="new-password"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm
                               outline-none transition focus:border-slate-500 focus:ring-2
                               focus:ring-slate-200">
                </div>

                <div>
                    <label
                        for="password_confirmation"
                        class="mb-1.5 block text-sm font-medium text-slate-700">
                        Confirmar contraseña
                    </label>

                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm
                               outline-none transition focus:border-slate-500 focus:ring-2
                               focus:ring-slate-200">
                </div>

                @php
                $referralCode = old(
                'referral_code',
                request('ref')
                );
                @endphp

                @if ($referralCode)

                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">

                    <input
                        type="hidden"
                        name="referral_code"
                        value="{{ $referralCode }}">

                    <p class="text-sm text-emerald-800">
                        Código de referido aplicado:
                        <strong class="font-semibold">
                            {{ strtoupper($referralCode) }}
                        </strong>
                    </p>

                    <p class="mt-1 text-xs text-emerald-700">
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
                        class="w-full rounded-lg border
                                   @error('referral_code')
                                       border-red-300
                                   @else
                                       border-slate-300
                                   @enderror
                                   px-3 py-2.5 text-sm uppercase
                                   outline-none transition
                                   focus:border-slate-500
                                   focus:ring-2 focus:ring-slate-200">

                    <p class="mt-1.5 text-xs text-slate-500">
                        ¿Alguien te invitó a DocTotal?
                        Escribe aquí su código de referido.
                    </p>

                    @error('referral_code')
                    <p class="mt-1.5 text-xs text-red-600">
                        {{ $message }}
                    </p>
                    @enderror
                </div>

                @endif

                <div class="rounded-lg bg-slate-50 p-4 text-sm text-slate-600">
                    Obtendrás
                    <strong>
                        {{ config('doctotal.trial_days') }} días gratis
                    </strong>
                    para probar DocTotal.
                </div>

                <button
                    type="submit"
                    class="w-full rounded-lg bg-slate-900 px-4 py-3 text-sm font-semibold
                           text-white transition hover:bg-slate-800">
                    Crear mi cuenta
                </button>

            </form>

            <p class="mt-6 text-center text-sm text-slate-500">
                ¿Ya tienes una cuenta?

                <a
                    href="{{ route('login') }}"
                    class="font-medium text-slate-900 hover:underline">
                    Iniciar sesión
                </a>
            </p>

        </div>

        <p class="mt-4 text-center text-xs text-slate-400">
            Al crear una cuenta aceptas los términos y políticas de DocTotal.
        </p>

    </div>

</body>

</html>