<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>DocTotal Internal</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <div class="min-h-screen lg:flex">
        <aside class="hidden w-72 shrink-0 bg-gradient-to-b from-slate-950 via-indigo-950 to-violet-950 text-white lg:sticky lg:top-0 lg:flex lg:h-screen lg:flex-col lg:self-start">
            <div class="shrink-0 border-b border-white/10 px-6 py-6">
                <div class="flex items-center gap-3">
                    <div class="text-xl font-semibold">DocTotal</div>
                    <span class="rounded-full bg-white/10 px-2 py-1 text-[10px] font-semibold uppercase tracking-wider text-indigo-100">
                        Internal
                    </span>
                </div>

                <p class="mt-2 text-xs text-indigo-200/80">
                    Administración SaaS
                </p>
            </div>

            <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto px-4 py-6">
                <a href="{{ route('internal.dashboard') }}"
                    class="block rounded-xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('internal.dashboard') ? 'bg-white/10 text-white' : 'text-indigo-100 hover:bg-white/5 hover:text-white' }}">
                    Resumen
                </a>

                <a href="{{ route('internal.tenants.index') }}"
                    class="block rounded-xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('internal.tenants.*') ? 'bg-white/10 text-white' : 'text-indigo-100 hover:bg-white/5 hover:text-white' }}">
                    Tenants
                </a>

                <a href="{{ route('internal.billing.index') }}"
                    class="block rounded-xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('internal.billing.*') ? 'bg-white/10 text-white' : 'text-indigo-100 hover:bg-white/5 hover:text-white' }}">
                    Facturación
                </a>

                <a href="{{ route('internal.communications.index') }}"
                    class="block rounded-xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('internal.communications.*') ? 'bg-white/10 text-white' : 'text-indigo-100 hover:bg-white/5 hover:text-white' }}">
                    Comunicaciones
                </a>

                <a href="{{ route('internal.audit.index') }}"
                    class="block rounded-xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('internal.audit.*') ? 'bg-white/10 text-white' : 'text-indigo-100 hover:bg-white/5 hover:text-white' }}">
                    Auditoría
                </a>
            </nav>

            <div class="shrink-0 border-t border-white/10 p-4">
                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                    <div class="flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-200">
                            Consola interna
                        </p>
                    </div>

                    <p class="mt-2 text-sm font-medium text-white">
                        Contexto global
                    </p>

                    <p class="mt-1 text-xs leading-5 text-indigo-200/70">
                        Sin tenant clínico activo
                    </p>
                </div>
            </div>
        </aside>

        <div class="min-w-0 flex-1">
            <header class="sticky top-0 z-30 border-b border-slate-200/90 bg-white/95 backdrop-blur">
                <div class="flex min-h-20 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="font-semibold text-slate-900">
                                Administración interna
                            </h2>

                            <span class="rounded-full bg-indigo-50 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-indigo-700">
                                SaaS
                            </span>
                        </div>

                        <p class="mt-1 text-xs text-slate-500">
                            Contexto operacional global
                        </p>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="hidden text-right sm:block">
                            <div class="text-sm font-medium text-slate-900">
                                {{ auth()->user()->name }}
                            </div>
                            <div class="text-xs text-slate-500">
                                {{ auth()->user()->email }}
                            </div>
                        </div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <button type="submit"
                                class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                                Salir
                            </button>
                        </form>
                    </div>
                </div>

                <nav class="flex gap-2 overflow-x-auto border-t border-slate-100 px-4 py-3 lg:hidden">
                    <a href="{{ route('internal.dashboard') }}"
                        class="whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('internal.dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600' }}">
                        Resumen
                    </a>

                    <a href="{{ route('internal.tenants.index') }}"
                        class="whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('internal.tenants.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600' }}">
                        Tenants
                    </a>

                    <a href="{{ route('internal.billing.index') }}"
                        class="whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('internal.billing.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600' }}">
                        Facturación
                    </a>

                    <a href="{{ route('internal.communications.index') }}"
                        class="whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('internal.communications.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600' }}">
                        Comunicaciones
                    </a>

                    <a href="{{ route('internal.audit.index') }}"
                        class="whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('internal.audit.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600' }}">
                        Auditoría
                    </a>
                </nav>
            </header>

            <main class="px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>

</html>
