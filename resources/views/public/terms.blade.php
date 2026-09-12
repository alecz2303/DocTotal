<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Términos y Condiciones | DocTotal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
<main class="mx-auto max-w-4xl px-6 py-14">
    <a href="{{ route('home') }}" class="font-semibold text-blue-600">← Volver a DocTotal</a>
    <article class="mt-8 rounded-3xl bg-white p-8 shadow-sm ring-1 ring-slate-200 sm:p-12">
        <p class="text-sm font-semibold uppercase tracking-wider text-blue-600">DocTotal</p>
        <h1 class="mt-2 text-4xl font-bold text-slate-950">Términos y Condiciones</h1>
        <p class="mt-3 text-sm text-slate-500">Versión {{ config('legal.terms_version') }}</p>
        <div class="mt-10 space-y-8 leading-7">
            <section><h2 class="text-xl font-bold text-slate-950">1. Servicio</h2><p class="mt-2">DocTotal proporciona herramientas tecnológicas para organizar la operación de una práctica médica, incluyendo funciones de agenda, pacientes, expediente, consultas, recetas y otras funcionalidades disponibles en la plataforma.</p></section>
            <section><h2 class="text-xl font-bold text-slate-950">2. No es un prestador médico</h2><p class="mt-2">DocTotal no diagnostica, prescribe ni sustituye el criterio profesional. Las decisiones clínicas, la veracidad de la información capturada, la relación con el paciente y el cumplimiento de las obligaciones profesionales corresponden al médico o establecimiento que utiliza el servicio.</p></section>
            <section><h2 class="text-xl font-bold text-slate-950">3. Cuenta y seguridad</h2><p class="mt-2">El usuario debe proporcionar información correcta, proteger sus credenciales y utilizar la plataforma de forma lícita. Es responsable de las acciones realizadas desde sus accesos, salvo los derechos que legalmente no puedan limitarse.</p></section>
            <section><h2 class="text-xl font-bold text-slate-950">4. Planes y pagos</h2><p class="mt-2">Los precios, periodicidad y condiciones aplicables se muestran antes de contratar o pagar. Los beneficios promocionales dependen de las condiciones vigentes del código correspondiente. Un código de referido y un código promocional no se combinan en el mismo registro.</p></section>
            <section><h2 class="text-xl font-bold text-slate-950">5. Disponibilidad y cambios</h2><p class="mt-2">Podemos realizar mantenimiento y mejoras razonables al servicio. Cuando un cambio afecte materialmente las condiciones contratadas o el tratamiento de información, se comunicará conforme corresponda.</p></section>
            <section><h2 class="text-xl font-bold text-slate-950">6. Responsabilidad</h2><p class="mt-2">DocTotal adopta medidas razonables para operar y proteger la plataforma, pero no garantiza disponibilidad ininterrumpida ni riesgo cero. Nada en estos términos pretende excluir derechos del consumidor ni responsabilidades que no puedan renunciarse conforme a la legislación aplicable.</p></section>
            <section><h2 class="text-xl font-bold text-slate-950">7. Privacidad</h2><p class="mt-2">El tratamiento de datos personales relacionado con DocTotal se describe en el <a class="text-blue-600 underline" href="{{ route('privacy') }}">Aviso de Privacidad</a>. Cada médico o establecimiento conserva sus propias obligaciones respecto de los datos de sus pacientes.</p></section>
            @if(config('legal.contact_email'))<p><strong>Contacto:</strong> <a class="text-blue-600 underline" href="mailto:{{ config('legal.contact_email') }}">{{ config('legal.contact_email') }}</a></p>@endif
        </div>
    </article>
</main>
</body>
</html>
