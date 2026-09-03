<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Acceso temporalmente no disponible · DocTotal</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-950 text-slate-900 antialiased">
    <main class="flex min-h-screen items-center justify-center px-4 py-10">
        <div class="w-full max-w-2xl overflow-hidden rounded-3xl bg-white shadow-2xl">
            <div class="border-b border-slate-200 bg-gradient-to-r from-slate-50 to-indigo-50 px-6 py-6 sm:px-8">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-indigo-700">DocTotal</p>
                        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">
                            Acceso al servicio no disponible
                        </h1>
                    </div>

                    <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">
                        Acceso restringido
                    </span>
                </div>
            </div>

            <div class="space-y-6 px-6 py-7 sm:px-8">
                <div>
                    <p class="text-sm text-slate-600">
                        La cuenta <strong class="text-slate-900">{{ $tenant->name }}</strong>
                        no tiene acceso operativo a las funciones clínicas en este momento.
                    </p>

                    <p class="mt-3 text-sm text-slate-600">
                        Tus datos permanecen en la cuenta. Mientras el acceso esté restringido,
                        no se pueden consultar ni modificar pacientes, citas, consultas,
                        recetas o documentos clínicos.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Estado de la cuenta
                        </p>
                        <p class="mt-2 font-semibold text-slate-950">
                            {{ $tenant->effectiveServiceStatusLabel() }}
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Trial
                        </p>
                        <p class="mt-2 font-semibold text-slate-950">
                            @if ($tenant->trialHasExpired())
                                Finalizó {{ $tenant->trial_ends_at?->format('d/m/Y') }}
                            @elseif ($tenant->isOnTrial())
                                Activo hasta {{ $tenant->trial_ends_at?->format('d/m/Y') }}
                            @else
                                No activo
                            @endif
                        </p>
                    </div>
                </div>

                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                    <p class="text-sm font-semibold text-amber-950">
                        ¿Qué puedes hacer?
                    </p>
                    <p class="mt-1 text-sm leading-6 text-amber-900">
                        Si consideras que el acceso debería estar activo, revisa el estado
                        de tu suscripción o contacta al administrador de DocTotal.
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('settings.billing') }}"
                       class="inline-flex flex-1 items-center justify-center rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700">
                        Revisar facturación
                    </a>

                    <form method="POST"
                          action="{{ route('logout') }}"
                          class="flex-1">
                        @csrf
                        <button type="submit"
                                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            Cerrar sesión
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
