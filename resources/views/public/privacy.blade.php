<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aviso de Privacidad | DocTotal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
<main class="mx-auto max-w-4xl px-6 py-14">
    <a href="{{ route('home') }}" class="font-semibold text-blue-600">← Volver a DocTotal</a>
    <article class="mt-8 rounded-3xl bg-white p-8 shadow-sm ring-1 ring-slate-200 sm:p-12">
        <p class="text-sm font-semibold uppercase tracking-wider text-blue-600">DocTotal</p>
        <h1 class="mt-2 text-4xl font-bold text-slate-950">Aviso de Privacidad</h1>
        <p class="mt-3 text-sm text-slate-500">Versión {{ config('legal.privacy_version') }}</p>

        <div class="mt-10 space-y-8 leading-7">
            <section><h2 class="text-xl font-bold text-slate-950">Responsable y alcance</h2><p class="mt-2">{{ config('legal.legal_name') ?: 'El responsable de DocTotal' }} pone a disposición este aviso respecto de los datos personales tratados para crear y administrar cuentas, prestar el servicio, gestionar facturación, soporte, seguridad y comunicaciones relacionadas con la plataforma.</p></section>
            <section><h2 class="text-xl font-bold text-slate-950">Información clínica</h2><p class="mt-2">DocTotal es una plataforma tecnológica para profesionales de la salud. El médico o consultorio que captura información de sus pacientes determina el uso clínico de esa información y conserva sus obligaciones profesionales y legales frente al paciente. El aviso de DocTotal no sustituye el aviso de privacidad ni los consentimientos que correspondan al médico o establecimiento.</p></section>
            <section><h2 class="text-xl font-bold text-slate-950">Datos y finalidades</h2><p class="mt-2">Podemos tratar datos de identificación, contacto, acceso, operación, soporte, facturación y seguridad necesarios para operar DocTotal. La plataforma también puede alojar datos de salud capturados por los usuarios autorizados para integrar y administrar expedientes, consultas, recetas, estudios y documentos clínicos.</p></section>
            <section><h2 class="text-xl font-bold text-slate-950">Seguridad y conservación</h2><p class="mt-2">Aplicamos controles técnicos y operativos orientados a proteger la información, incluyendo autenticación, controles de acceso, almacenamiento privado de documentos, respaldos y registros operativos. Ningún sistema conectado a Internet puede garantizar riesgo cero.</p></section>
            <section><h2 class="text-xl font-bold text-slate-950">Derechos y contacto</h2><p class="mt-2">Para solicitudes relacionadas con tus datos personales, correcciones, acceso, oposición u otras consultas de privacidad, utiliza el canal de privacidad indicado a continuación. La atención estará sujeta a los requisitos y excepciones previstos por la legislación aplicable.</p></section>

            @if(config('legal.privacy_email'))
                <p><strong>Privacidad:</strong> <a class="text-blue-600 underline" href="mailto:{{ config('legal.privacy_email') }}">{{ config('legal.privacy_email') }}</a></p>
            @endif
            @if(config('legal.address'))<p><strong>Domicilio:</strong> {{ config('legal.address') }}</p>@endif
            @if(config('legal.rfc'))<p><strong>RFC:</strong> {{ config('legal.rfc') }}</p>@endif
        </div>
    </article>
</main>
</body>
</html>
