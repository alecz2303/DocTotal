<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? 'DocTotal' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="bg-slate-50 text-slate-900 antialiased">

    <div class="min-h-screen lg:flex">

        {{-- Sidebar --}}
        <aside class="hidden w-64 shrink-0 border-r border-slate-200 bg-white lg:flex lg:flex-col">

            <div class="flex h-16 items-center border-b border-slate-200 px-6">
                <span class="text-xl font-bold tracking-tight">
                    DocTotal
                </span>
            </div>

            <nav class="flex-1 space-y-1 p-4">

                <a href="{{ route('dashboard') }}"
                    class="flex items-center rounded-lg px-3 py-2 text-sm font-medium
               {{ request()->routeIs('dashboard')
                    ? 'bg-slate-900 text-white'
                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                    Dashboard
                </a>

                <a
                    href="{{ route('appointments.index') }}"
                    class="flex items-center rounded-lg px-3 py-2 text-sm font-medium
                        {{ request()->routeIs('appointments.*')
                            ? 'bg-slate-900 text-white'
                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                    Agenda
                </a>

                <a
                    href="{{ route('patients.index') }}"
                    class="flex items-center rounded-lg px-3 py-2 text-sm font-medium
                        {{ request()->routeIs('patients.*')
                            ? 'bg-slate-900 text-white'
                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                    Pacientes
                </a>

                <a
                    href="{{ route('consultations.index') }}"
                    class="flex items-center rounded-lg px-3 py-2 text-sm font-medium
                        {{ request()->routeIs('consultations.*')
                            ? 'bg-slate-900 text-white'
                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                    Consultas
                </a>

                <a
                    href="{{ route('prescriptions.index') }}"
                    class="flex items-center rounded-lg px-3 py-2 text-sm font-medium
                        {{ request()->routeIs('prescriptions.*')
                            ? 'bg-slate-900 text-white'
                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                    Recetas
                </a>

                <a href="#"
                    class="flex items-center rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900">
                    Archivos
                </a>

            </nav>

            <div class="border-t border-slate-200 p-4">

                <a
                    href="{{ route('settings.profile') }}"
                    class="flex items-center rounded-lg px-3 py-2 text-sm font-medium
                        {{ request()->routeIs('settings.*')
                            ? 'bg-slate-900 text-white'
                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                    Configuración
                </a>

                <div class="mt-4 px-3">
                    <p class="truncate text-xs font-medium text-slate-900">
                        {{ auth()->user()->tenant?->name }}
                    </p>

                    <p class="mt-1 text-xs text-slate-500">
                        Consultorio
                    </p>
                </div>

            </div>

        </aside>

        {{-- Content --}}
        <div class="min-w-0 flex-1">

            {{-- Header --}}
            <header class="flex h-16 items-center justify-between border-b border-slate-200 bg-white px-6">

                <div>
                    <span class="font-semibold lg:hidden">
                        DocTotal
                    </span>
                </div>

                <div class="flex items-center gap-4">

                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-medium text-slate-900">
                            {{ auth()->user()->name }}
                        </p>

                        <p class="text-xs text-slate-500">
                            {{ auth()->user()->email }}
                        </p>
                    </div>

                    <form method="POST" action="/logout">
                        @csrf

                        <button
                            type="submit"
                            class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                            Cerrar sesión
                        </button>
                    </form>

                </div>

            </header>

            <main class="p-6 lg:p-8">
                {{ $slot }}
            </main>

        </div>

    </div>

    <x-flash-messages />

    @livewireScripts

</body>

</html>