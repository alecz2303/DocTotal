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

    <title>{{ $title ?? 'DocTotal' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-app-bg text-text-primary antialiased">

    @php
    $currentUser = auth()->user();
    $doctorProfile = $currentUser->doctorProfile;

    $nextAppointment = $doctorProfile
    ? \App\Models\Appointment::query()
    ->with('patient')
    ->where('doctor_profile_id', $doctorProfile->id)
    ->whereIn('status', [
    \App\Models\Appointment::STATUS_SCHEDULED,
    \App\Models\Appointment::STATUS_CONFIRMED,
    \App\Models\Appointment::STATUS_CHECKED_IN,
    ])
    ->where('ends_at', '>=', now())
    ->orderBy('starts_at')
    ->first()
    : null;

    $hour = now()->hour;

    $greeting = match (true) {
    $hour < 12=> 'Buenos días',
        $hour < 19=> 'Buenas tardes',
            default => 'Buenas noches',
            };

            $currentDate = now()->locale('es')->translatedFormat('l, j \d\e F');

            $appointmentMinutes = $nextAppointment
            ? (int) now()->diffInMinutes($nextAppointment->starts_at, false)
            : null;
            @endphp

            <div
                x-data="{ mobileMenuOpen: false }"
                class="min-h-screen lg:flex">

                {{-- ================================================================
        | DESKTOP SIDEBAR
        ================================================================= --}}
                <aside
                    class="hidden h-screen w-72 shrink-0 border-r border-white/10
                   bg-gradient-to-b from-slate-950 via-slate-900 to-blue-950
                   text-white lg:sticky lg:top-0 lg:flex lg:flex-col">

                    {{-- Brand --}}
                    <div class="flex h-20 items-center border-b border-white/10 px-6">

                        <div class="flex h-11 w-11 shrink-0 items-center justify-center">
                            <img
                                src="{{ asset('images/branding/doctotal-icon.png') }}"
                                alt="DocTotal"
                                class="h-11 w-11 object-contain">
                        </div>

                        <div class="ml-3">

                            <p class="text-lg font-semibold tracking-tight">
                                DocTotal
                            </p>

                            <p class="text-xs text-slate-400">
                                Gestión médica inteligente
                            </p>

                        </div>

                    </div>

                    {{-- Navigation --}}
                    <nav class="flex-1 space-y-1.5 overflow-y-auto px-4 py-6">

                        <p
                            class="mb-3 px-3 text-[11px] font-semibold uppercase
                           tracking-[0.18em] text-slate-500">
                            Principal
                        </p>

                        <a
                            href="{{ route('dashboard') }}"
                            class="dt-nav-link {{ request()->routeIs('dashboard') ? 'dt-nav-link-active' : '' }}">

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                class="h-5 w-5 shrink-0">
                                <rect x="3" y="3" width="7" height="7" rx="2" />
                                <rect x="14" y="3" width="7" height="7" rx="2" />
                                <rect x="3" y="14" width="7" height="7" rx="2" />
                                <rect x="14" y="14" width="7" height="7" rx="2" />
                            </svg>

                            <span>Dashboard</span>
                        </a>

                        <a
                            href="{{ route('appointments.index') }}"
                            class="dt-nav-link {{ request()->routeIs('appointments.*') ? 'dt-nav-link-active' : '' }}">

                            <span
                                class="flex h-8 w-8 shrink-0 items-center justify-center
                           rounded-lg text-slate-300
                           {{ request()->routeIs('appointments.*') ? 'bg-white/10 text-white' : '' }}">

                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5"
                                    aria-hidden="true">

                                    <rect
                                        x="3"
                                        y="5"
                                        width="18"
                                        height="16"
                                        rx="2"
                                        stroke="currentColor"
                                        stroke-width="1.8" />

                                    <path
                                        d="M8 3V7"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        stroke-linecap="round" />

                                    <path
                                        d="M16 3V7"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        stroke-linecap="round" />

                                    <path
                                        d="M3 10H21"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        stroke-linecap="round" />

                                </svg>

                            </span>

                            <span>Agenda</span>
                        </a>

                        <a
                            href="{{ route('patients.index') }}"
                            class="dt-nav-link {{ request()->routeIs('patients.*') ? 'dt-nav-link-active' : '' }}">

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                class="h-5 w-5 shrink-0">
                                <circle cx="9" cy="8" r="4" />
                                <path d="M2.5 21a6.5 6.5 0 0 1 13 0" />
                                <path d="M18 8v6M15 11h6" stroke-linecap="round" />
                            </svg>

                            <span>Pacientes</span>
                        </a>

                        <a
                            href="{{ route('consultations.index') }}"
                            class="dt-nav-link {{ request()->routeIs('consultations.*') ? 'dt-nav-link-active' : '' }}">

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                class="h-5 w-5 shrink-0">
                                <rect x="5" y="3" width="14" height="18" rx="2" />
                                <path d="M9 8h6M9 12h6M9 16h3" />
                            </svg>

                            <span>Consultas</span>
                        </a>

                        <a
                            href="{{ route('prescriptions.index') }}"
                            class="dt-nav-link {{ request()->routeIs('prescriptions.*') ? 'dt-nav-link-active' : '' }}">

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                class="h-5 w-5 shrink-0">
                                <path d="M6 3h12v18H6z" />
                                <path d="M9 8h6M9 12h6M9 16h4" />
                            </svg>

                            <span>Recetas</span>
                        </a>

                        <a href="#" class="dt-nav-link">

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                class="h-5 w-5 shrink-0">
                                <path d="M3 6a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z" />
                            </svg>

                            <span>Archivos</span>
                        </a>

                    </nav>

                    {{-- Bottom --}}
                    <div class="border-t border-white/10 p-4">

                        <a
                            href="{{ route('settings.profile') }}"
                            class="dt-nav-link {{ request()->routeIs('settings.*') ? 'dt-nav-link-active' : '' }}">

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                class="h-5 w-5 shrink-0">
                                <circle cx="12" cy="12" r="3" />
                                <path
                                    d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.12 2.12-.06-.06a1.7 1.7 0 0 0-1.88-.34 1.7 1.7 0 0 0-1.03 1.56V20h-3v-.08a1.7 1.7 0 0 0-1.03-1.56 1.7 1.7 0 0 0-1.88.34l-.06.06-2.12-2.12.06-.06A1.7 1.7 0 0 0 7 15a1.7 1.7 0 0 0-1.56-1.03H5v-3h.44A1.7 1.7 0 0 0 7 9.94a1.7 1.7 0 0 0-.34-1.88L6.6 8l2.12-2.12.06.06a1.7 1.7 0 0 0 1.88.34A1.7 1.7 0 0 0 11.69 4.7V4h3v.7a1.7 1.7 0 0 0 1.03 1.56 1.7 1.7 0 0 0 1.88-.34l.06-.06L19.78 8l-.06.06a1.7 1.7 0 0 0-.34 1.88A1.7 1.7 0 0 0 20.94 11H21v3h-.06A1.7 1.7 0 0 0 19.4 15Z" />
                            </svg>

                            <span>Configuración</span>
                        </a>

                        <div
                            class="mt-4 rounded-2xl border border-white/10
                           bg-white/5 px-4 py-3">

                            <div class="flex items-center gap-3">

                                <div
                                    class="flex h-9 w-9 shrink-0 items-center justify-center
                                   rounded-xl bg-blue-500/15 text-sm font-semibold text-blue-300">
                                    {{ strtoupper(substr(auth()->user()->tenant?->name ?? 'D', 0, 1)) }}
                                </div>

                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-slate-100">
                                        {{ auth()->user()->tenant?->name }}
                                    </p>
                                    <p class="mt-0.5 text-xs text-slate-500">
                                        Consultorio
                                    </p>
                                </div>

                            </div>

                        </div>

                    </div>

                </aside>


                {{-- ================================================================
        | MOBILE DRAWER
        ================================================================= --}}
                <div
                    x-cloak
                    x-show="mobileMenuOpen"
                    x-transition.opacity
                    x-on:keydown.escape.window="mobileMenuOpen = false"
                    class="fixed inset-0 z-50 lg:hidden">

                    {{-- Overlay --}}
                    <button
                        type="button"
                        aria-label="Cerrar menú"
                        x-on:click="mobileMenuOpen = false"
                        class="absolute inset-0 bg-slate-950/55 backdrop-blur-[2px]">
                    </button>

                    {{-- Drawer --}}
                    <aside
                        x-show="mobileMenuOpen"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="-translate-x-full"
                        x-transition:enter-end="translate-x-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="translate-x-0"
                        x-transition:leave-end="-translate-x-full"
                        class="relative flex h-full w-[86%] max-w-sm flex-col
                       border-r border-white/10
                       bg-gradient-to-b from-slate-950 via-slate-900 to-blue-950
                       text-white shadow-2xl">

                        {{-- Mobile brand --}}
                        <div
                            class="flex h-20 items-center justify-between
                           border-b border-white/10 px-5">

                            <div class="flex items-center gap-3">

                                <div class="flex h-10 w-10 shrink-0 items-center justify-center">
                                    <img
                                        src="{{ asset('images/branding/doctotal-icon.png') }}"
                                        alt="DocTotal"
                                        class="h-10 w-10 object-contain">
                                </div>

                                <div>
                                    <p class="font-semibold tracking-tight">
                                        DocTotal
                                    </p>
                                    <p class="text-xs text-slate-400">
                                        Gestión médica inteligente
                                    </p>
                                </div>

                            </div>

                            <button
                                type="button"
                                x-on:click="mobileMenuOpen = false"
                                class="flex h-10 w-10 items-center justify-center
                               rounded-xl text-slate-400
                               hover:bg-white/10 hover:text-white">

                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.9"
                                    class="h-5 w-5">
                                    <path d="M6 6l12 12M18 6 6 18" stroke-linecap="round" />
                                </svg>

                            </button>

                        </div>

                        {{-- Mobile navigation --}}
                        <nav class="flex-1 space-y-1.5 overflow-y-auto px-4 py-5">

                            <p
                                class="mb-3 px-3 text-[11px] font-semibold uppercase
                               tracking-[0.18em] text-slate-500">
                                Principal
                            </p>

                            <a
                                href="{{ route('dashboard') }}"
                                x-on:click="mobileMenuOpen = false"
                                class="dt-nav-link {{ request()->routeIs('dashboard') ? 'dt-nav-link-active' : '' }}">

                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5 shrink-0">
                                    <rect x="3" y="3" width="7" height="7" rx="2" />
                                    <rect x="14" y="3" width="7" height="7" rx="2" />
                                    <rect x="3" y="14" width="7" height="7" rx="2" />
                                    <rect x="14" y="14" width="7" height="7" rx="2" />
                                </svg>

                                <span>Dashboard</span>
                            </a>

                            <a
                                href="{{ route('appointments.index') }}"
                                x-on:click="mobileMenuOpen = false"
                                class="dt-nav-link {{ request()->routeIs('appointments.*') ? 'dt-nav-link-active' : '' }}">

                                <span
                                    class="flex h-8 w-8 shrink-0 items-center justify-center
                               rounded-lg text-slate-300
                               {{ request()->routeIs('appointments.*') ? 'bg-white/10 text-white' : '' }}">

                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5"
                                        aria-hidden="true">

                                        <rect
                                            x="3"
                                            y="5"
                                            width="18"
                                            height="16"
                                            rx="2"
                                            stroke="currentColor"
                                            stroke-width="1.8" />

                                        <path
                                            d="M8 3V7"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                            stroke-linecap="round" />

                                        <path
                                            d="M16 3V7"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                            stroke-linecap="round" />

                                        <path
                                            d="M3 10H21"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                            stroke-linecap="round" />

                                    </svg>

                                </span>

                                <span>Agenda</span>
                            </a>

                            <a
                                href="{{ route('patients.index') }}"
                                x-on:click="mobileMenuOpen = false"
                                class="dt-nav-link {{ request()->routeIs('patients.*') ? 'dt-nav-link-active' : '' }}">

                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5 shrink-0">
                                    <circle cx="9" cy="8" r="4" />
                                    <path d="M2.5 21a6.5 6.5 0 0 1 13 0" />
                                    <path d="M18 8v6M15 11h6" stroke-linecap="round" />
                                </svg>

                                <span>Pacientes</span>
                            </a>

                            <a
                                href="{{ route('consultations.index') }}"
                                x-on:click="mobileMenuOpen = false"
                                class="dt-nav-link {{ request()->routeIs('consultations.*') ? 'dt-nav-link-active' : '' }}">

                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5 shrink-0">
                                    <rect x="5" y="3" width="14" height="18" rx="2" />
                                    <path d="M9 8h6M9 12h6M9 16h3" />
                                </svg>

                                <span>Consultas</span>
                            </a>

                            <a
                                href="{{ route('prescriptions.index') }}"
                                x-on:click="mobileMenuOpen = false"
                                class="dt-nav-link {{ request()->routeIs('prescriptions.*') ? 'dt-nav-link-active' : '' }}">

                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5 shrink-0">
                                    <path d="M6 3h12v18H6z" />
                                    <path d="M9 8h6M9 12h6M9 16h4" />
                                </svg>

                                <span>Recetas</span>
                            </a>

                            <a
                                href="#"
                                x-on:click="mobileMenuOpen = false"
                                class="dt-nav-link">

                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5 shrink-0">
                                    <path d="M3 6a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z" />
                                </svg>

                                <span>Archivos</span>
                            </a>

                        </nav>

                        {{-- Mobile bottom --}}
                        <div class="border-t border-white/10 p-4">

                            <a
                                href="{{ route('settings.profile') }}"
                                x-on:click="mobileMenuOpen = false"
                                class="dt-nav-link {{ request()->routeIs('settings.*') ? 'dt-nav-link-active' : '' }}">

                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5 shrink-0">
                                    <circle cx="12" cy="12" r="3" />
                                    <path d="M19 12a7 7 0 1 1-7-7 7 7 0 0 1 7 7Z" />
                                    <path d="M12 2v3M12 19v3M2 12h3M19 12h3" />
                                </svg>

                                <span>Configuración</span>
                            </a>

                            <div
                                class="mt-4 rounded-2xl border border-white/10
                               bg-white/5 px-4 py-3">

                                <div class="flex items-center gap-3">

                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center
                                       rounded-xl bg-blue-500/15
                                       text-sm font-semibold text-blue-300">
                                        {{ strtoupper(substr(auth()->user()->tenant?->name ?? 'D', 0, 1)) }}
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-slate-100">
                                            {{ auth()->user()->tenant?->name }}
                                        </p>
                                        <p class="mt-0.5 text-xs text-slate-500">
                                            {{ auth()->user()->name }}
                                        </p>
                                    </div>

                                </div>

                            </div>

                            <form method="POST" action="/logout" class="mt-3">
                                @csrf

                                <button
                                    type="submit"
                                    class="flex w-full items-center justify-center gap-2
                                   rounded-xl border border-white/10
                                   bg-white/5 px-4 py-2.5
                                   text-sm font-semibold text-slate-300
                                   hover:bg-white/10 hover:text-white">

                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        class="h-4.5 w-4.5">
                                        <path d="M10 17l5-5-5-5M15 12H3" />
                                        <path d="M14 3h5a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-5" />
                                    </svg>

                                    Cerrar sesión
                                </button>
                            </form>

                        </div>

                    </aside>

                </div>


                {{-- ================================================================
        | MAIN AREA
        ================================================================= --}}
                <div class="min-w-0 flex-1">

                    {{-- Header --}}
                    <header
                        class="sticky top-0 z-30 flex min-h-16 items-center justify-between
                       border-b border-slate-200/80 bg-white/90
                       px-4 py-2 backdrop-blur-xl sm:px-6 lg:min-h-20 lg:px-8">

                        {{-- Mobile brand + menu --}}
                        <div class="flex min-w-0 items-center gap-3 lg:hidden">

                            <button
                                type="button"
                                x-on:click="mobileMenuOpen = true"
                                aria-label="Abrir menú"
                                class="flex h-10 w-10 shrink-0 items-center justify-center
                               rounded-xl border border-slate-200 bg-white
                               text-slate-700 shadow-sm
                               hover:border-slate-300 hover:bg-slate-50">

                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.9"
                                    class="h-5 w-5">
                                    <path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round" />
                                </svg>

                            </button>

                            <div class="flex h-9 w-9 shrink-0 items-center justify-center">
                                <img
                                    src="{{ asset('images/branding/doctotal-icon.png') }}"
                                    alt="DocTotal"
                                    class="h-9 w-9 object-contain">
                            </div>

                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold tracking-tight text-slate-950">
                                    DocTotal
                                </p>
                                <p class="truncate text-[11px] text-slate-500">
                                    {{ $currentUser->tenant?->name }}
                                </p>
                            </div>

                        </div>

                        {{-- Desktop contextual header --}}
                        <div class="hidden min-w-0 flex-1 items-center lg:flex">

                            {{-- Greeting --}}
                            <div class="min-w-0 pr-6">
                                <p class="truncate text-sm font-semibold text-slate-950">
                                    {{ $greeting }}, {{ $currentUser->name }}
                                </p>
                                <p class="mt-1 text-xs capitalize text-slate-500">
                                    {{ $currentDate }}
                                </p>
                            </div>

                            <div class="mx-2 h-9 w-px shrink-0 bg-slate-200"></div>

                            {{-- Next appointment --}}
                            <div class="min-w-0 flex-1 px-6">
                                @if ($nextAppointment)
                                <div class="flex min-w-0 items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center
                                           rounded-xl bg-blue-50 text-blue-600">
                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                            class="h-5 w-5">
                                            <rect x="3" y="5" width="18" height="16" rx="2" />
                                            <path d="M8 3v4M16 3v4M3 10h18" />
                                        </svg>
                                    </div>

                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">
                                                Próxima cita
                                            </p>
                                            <span class="text-xs text-slate-300">•</span>
                                            <p class="text-sm font-bold tabular-nums text-slate-950">
                                                {{ $nextAppointment->starts_at->format('H:i') }}
                                            </p>
                                        </div>

                                        <div class="mt-0.5 flex min-w-0 flex-wrap items-center gap-x-2 text-xs">
                                            <span class="max-w-52 truncate font-medium text-slate-700">
                                                {{ $nextAppointment->patient?->full_name ?? 'Paciente' }}
                                            </span>

                                            @if ($appointmentMinutes !== null && $appointmentMinutes > 0)
                                            <span class="text-slate-400">
                                                · en {{ $appointmentMinutes }} min
                                            </span>
                                            @elseif ($appointmentMinutes !== null && $appointmentMinutes <= 0)
                                                <span class="font-medium text-emerald-600">
                                                · en curso
                                                </span>
                                                @endif

                                                <a
                                                    href="{{ route('appointments.index') }}"
                                                    class="font-semibold text-blue-600 hover:text-blue-700">
                                                    Ver agenda →
                                                </a>
                                        </div>
                                    </div>
                                </div>
                                @else
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center
                                           rounded-xl bg-emerald-50 text-emerald-600">
                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.9"
                                            class="h-5 w-5">
                                            <path d="M5 12.5 9.2 17 19 7" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </div>

                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">
                                            Agenda libre por ahora
                                        </p>
                                        <p class="mt-0.5 text-xs text-slate-500">
                                            No hay citas próximas
                                        </p>
                                    </div>
                                </div>
                                @endif
                            </div>

                        </div>

                        {{-- User actions --}}
                        <div class="flex shrink-0 items-center gap-2 sm:gap-3">

                            {{-- Notifications --}}
                            <button
                                type="button"
                                aria-label="Notificaciones"
                                title="Notificaciones"
                                class="relative flex h-10 w-10 items-center justify-center
                               rounded-xl border border-slate-200 bg-white
                               text-slate-600 shadow-sm
                               transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600">

                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    class="h-5 w-5">
                                    <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" />
                                    <path d="M10 21h4" stroke-linecap="round" />
                                </svg>
                            </button>

                            <div class="hidden text-right xl:block">
                                <p class="text-sm font-semibold text-slate-900">
                                    {{ $currentUser->name }}
                                </p>
                                <p class="mt-0.5 max-w-48 truncate text-xs text-slate-500">
                                    {{ $currentUser->email }}
                                </p>
                            </div>

                            <div
                                class="flex h-10 w-10 items-center justify-center
                               rounded-xl bg-gradient-to-br from-blue-600 to-violet-600
                               text-sm font-semibold text-white shadow-sm">
                                {{ strtoupper(substr($currentUser->name ?? 'U', 0, 1)) }}
                            </div>

                            <form method="POST" action="/logout">
                                @csrf

                                <button
                                    type="submit"
                                    class="dt-btn dt-btn-secondary hidden xl:inline-flex">

                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        class="h-4 w-4">
                                        <path d="M10 17l5-5-5-5M15 12H3" />
                                        <path d="M14 3h5a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-5" />
                                    </svg>

                                    Cerrar sesión
                                </button>
                            </form>

                        </div>

                    </header>

                    {{-- Page --}}
                    <main class="px-3 py-5 sm:px-6 lg:px-8 lg:py-8">

                        <div class="dt-page">
                            {{ $slot }}
                        </div>

                    </main>

                </div>

            </div>

            <x-flash-messages />

            @livewireScripts
</body>

</html>