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

    <title>Recuperar contraseña | DocTotal</title>

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

                            Recuperación segura
                        </span>

                        <h1
                            class="mt-6 text-4xl font-bold
                                   leading-tight tracking-tight">

                            Recupera el acceso a tu consultorio.
                        </h1>

                        <p
                            class="mt-5 max-w-lg text-base
                                   leading-7 text-slate-400">

                            Te enviaremos un enlace seguro para definir
                            una nueva contraseña.
                        </p>

                    </div>

                </div>

            </div>

        </section>

        {{-- Recovery --}}
        <main
            class="flex items-center justify-center
                   px-4 py-8 sm:px-6 lg:px-10">

            <div class="w-full max-w-md">

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

                    <div>

                        <p class="text-sm font-semibold text-blue-600">
                            ¿Olvidaste tu contraseña?
                        </p>

                        <h2
                            class="mt-2 text-3xl font-bold
                                   tracking-tight text-slate-950">

                            Recupera tu acceso
                        </h2>

                        <p
                            class="mt-2 text-sm leading-6
                                   text-slate-500">

                            Escribe tu correo electrónico y te enviaremos
                            un enlace para restablecer tu contraseña.
                        </p>

                    </div>

                    @if (session('status'))

                    <div
                        class="mt-6 rounded-2xl border border-emerald-200
                               bg-emerald-50 px-4 py-3
                               text-sm text-emerald-700">

                        Te hemos enviado por correo electrónico el enlace
                        para restablecer tu contraseña.

                    </div>

                    @endif

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
                        action="{{ route('password.email') }}"
                        class="mt-7">

                        @csrf

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
                                value="{{ old('email') }}"
                                required
                                autofocus
                                autocomplete="email"
                                placeholder="doctor@ejemplo.com"
                                class="dt-input">

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
                                   hover:to-violet-700">

                            Enviar enlace de recuperación

                        </button>

                    </form>

                    <div
                        class="mt-6 border-t border-slate-200
                               pt-6 text-center">

                        <a
                            href="{{ route('login') }}"
                            class="inline-flex items-center gap-1
                                   text-sm font-semibold
                                   text-blue-600
                                   hover:text-blue-700">

                            <span aria-hidden="true">←</span>
                            Volver a iniciar sesión
                        </a>

                    </div>

                </div>

            </div>

        </main>

    </div>

</body>

</html>