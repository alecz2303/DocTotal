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
</head>

<body class="min-h-screen bg-[#f6f8fc] text-slate-900 antialiased">

    @php
        $onboardingTenant = auth()->user()?->tenant;
        $trialDaysRemaining = $onboardingTenant?->isOnTrial()
            ? $onboardingTenant->trialDaysRemaining()
            : null;
    @endphp

    @if ($trialDaysRemaining !== null)
        <div class="border-b border-blue-200 bg-blue-50 px-4 py-2.5 text-center text-sm text-blue-950">
            <span class="font-semibold">Periodo de prueba activo.</span>
            Tienes {{ $trialDaysRemaining }} {{ $trialDaysRemaining === 1 ? 'día' : 'días' }} de prueba disponibles.
        </div>
    @endif

    {{ $slot }}

    <x-flash-messages />

    @livewireScripts
</body>

</html>