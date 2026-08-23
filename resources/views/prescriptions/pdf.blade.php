<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">

    <title>Receta médica</title>

    <style>
        @page {
            size: letter;
            margin: 16mm 18mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            color: #0f172a;
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            line-height: 1.5;
        }

        /*
        |--------------------------------------------------------------------------
        | Encabezado
        |--------------------------------------------------------------------------
        */

        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #0f172a;
        }

        .header-table td {
            vertical-align: top;
            padding-bottom: 16px;
        }

        .practice-column {
            width: 60%;
        }

        .doctor-column {
            width: 40%;
            text-align: right;
        }

        .practice-inner {
            width: 100%;
            border-collapse: collapse;
        }

        .practice-inner td {
            vertical-align: top;
            padding: 0;
            border: 0;
        }

        .logo-cell {
            width: 82px;
            padding-right: 14px !important;
        }

        .logo {
            display: block;
            max-width: 72px;
            max-height: 72px;
        }

        .practice-name {
            margin: 0;
            font-size: 20px;
            line-height: 1.2;
            font-weight: bold;
        }

        .practice-description {
            margin-top: 4px;
            color: #64748b;
            font-size: 10px;
            line-height: 1.4;
        }

        .practice-contact {
            margin-top: 7px;
            color: #475569;
            font-size: 10px;
            line-height: 1.5;
        }

        .doctor-name {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
        }

        .doctor-specialty {
            margin-top: 2px;
            color: #475569;
            font-size: 10px;
        }

        .doctor-detail {
            margin-top: 2px;
            color: #334155;
            font-size: 9px;
        }

        /*
        |--------------------------------------------------------------------------
        | Secciones
        |--------------------------------------------------------------------------
        */

        .section {
            margin-top: 20px;
        }

        .section-title {
            margin: 0 0 9px;
            color: #64748b;
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .label {
            display: block;
            margin-bottom: 3px;
            color: #94a3b8;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .value {
            color: #0f172a;
            font-size: 10px;
            line-height: 1.5;
        }

        /*
        |--------------------------------------------------------------------------
        | Paciente
        |--------------------------------------------------------------------------
        */

        .patient-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1px solid #e2e8f0;
        }

        .patient-table td {
            vertical-align: top;
            padding: 8px 10px 12px 0;
        }

        .patient-name {
            width: 45%;
        }

        .patient-birth {
            width: 30%;
        }

        .patient-date {
            width: 25%;
        }

        /*
        |--------------------------------------------------------------------------
        | Medicamentos
        |--------------------------------------------------------------------------
        */

        .medication {
            padding: 14px 0;
            border-bottom: 1px solid #e2e8f0;
            page-break-inside: avoid;
        }

        .medication-name {
            margin: 0;
            color: #0f172a;
            font-size: 12px;
            font-weight: bold;
        }

        .presentation {
            margin-top: 3px;
            color: #475569;
            font-size: 9px;
            line-height: 1.5;
        }

        .medication-data {
            width: 100%;
            margin-top: 9px;
            border-collapse: collapse;
        }

        .medication-data td {
            width: 33.333%;
            vertical-align: top;
            padding-right: 10px;
        }

        .instructions {
            margin-top: 10px;
            padding: 8px 10px;
            background: #f8fafc;
            color: #334155;
            font-size: 9px;
            line-height: 1.5;
        }

        .instructions strong {
            color: #0f172a;
        }

        /*
        |--------------------------------------------------------------------------
        | Indicaciones generales
        |--------------------------------------------------------------------------
        */

        .general {
            padding: 11px;
            border: 1px solid #e2e8f0;
            color: #334155;
            font-size: 9px;
            line-height: 1.6;
        }

        /*
        |--------------------------------------------------------------------------
        | Firma
        |--------------------------------------------------------------------------
        */

        .signature-table {
            width: 100%;
            margin-top: 48px;
            border-collapse: collapse;
            page-break-inside: avoid;
        }

        .signature-spacer {
            width: 50%;
        }

        .signature-cell {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
        }

        .signature-box {
            width: 250px;
            margin-left: auto;
        }

        .signature-image {
            display: block;
            max-width: 170px;
            max-height: 65px;
            margin: 0 auto 6px;
        }

        .signature-line {
            border-top: 1px solid #0f172a;
            margin-bottom: 7px;
        }

        .signature-name {
            color: #0f172a;
            font-size: 10px;
            font-weight: bold;
        }

        .signature-specialty {
            margin-top: 2px;
            color: #64748b;
            font-size: 9px;
        }

        .signature-license {
            margin-top: 2px;
            color: #64748b;
            font-size: 8px;
        }

        /*
        |--------------------------------------------------------------------------
        | Footer
        |--------------------------------------------------------------------------
        */

        .footer {
            margin-top: 38px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 8px;
            line-height: 1.5;
            text-align: center;
        }

        .cancelled-banner {
            margin-bottom: 18px;
            padding: 10px;
            border: 2px solid #991b1b;
            color: #991b1b;
            font-size: 14px;
            font-weight: 700;
            text-align: center;
            letter-spacing: .08em;
        }
    </style>
</head>

<body>

    @php
    $logoPath = $practice->logo_path
    ? storage_path('app/public/'.$practice->logo_path)
    : null;

    $signaturePath = $prescription->doctorProfile->signature_path
    ? storage_path(
    'app/public/'.
    $prescription->doctorProfile->signature_path
    )
    : null;
    @endphp

    @if ($prescription->status === 'cancelled')
    <div class="cancelled-banner">
        RECETA ANULADA
    </div>
    @endif

    {{-- ENCABEZADO --}}
    <table class="header-table">

        <tr>

            <td class="practice-column">

                <table class="practice-inner">

                    <tr>

                        @if ($logoPath && file_exists($logoPath))
                        <td class="logo-cell">

                            <img
                                src="{{ $logoPath }}"
                                alt="Logo"
                                class="logo">

                        </td>
                        @endif

                        <td>

                            <div class="practice-name">
                                {{ $practice->public_name }}
                            </div>

                            @if ($practice->description)
                            <div class="practice-description">
                                {{ $practice->description }}
                            </div>
                            @endif

                            <div class="practice-contact">

                                @if ($practice->address_line_1)

                                <div>
                                    {{ $practice->address_line_1 }}

                                    @if ($practice->address_line_2)
                                    ,
                                    {{ $practice->address_line_2 }}
                                    @endif
                                </div>

                                @endif

                                @if (
                                $practice->neighborhood
                                || $practice->city
                                || $practice->state
                                || $practice->postal_code
                                )

                                <div>

                                    @if ($practice->neighborhood)
                                    {{ $practice->neighborhood }}
                                    @endif

                                    @if ($practice->city)
                                    · {{ $practice->city }}
                                    @endif

                                    @if ($practice->state)
                                    · {{ $practice->state }}
                                    @endif

                                    @if ($practice->postal_code)
                                    · C.P. {{ $practice->postal_code }}
                                    @endif

                                </div>

                                @endif

                                @if ($practice->phone)
                                <div>
                                    Tel. {{ $practice->phone }}
                                </div>
                                @endif

                                @if ($practice->email)
                                <div>
                                    {{ $practice->email }}
                                </div>
                                @endif

                            </div>

                        </td>

                    </tr>

                </table>

            </td>

            <td class="doctor-column">

                <div class="doctor-name">
                    Dr.
                    {{ $prescription->doctorProfile->first_name }}
                    {{ $prescription->doctorProfile->last_name }}
                    {{ $prescription->doctorProfile->second_last_name }}
                </div>

                @if ($prescription->doctorProfile->specialty)

                <div class="doctor-specialty">
                    {{ $prescription->doctorProfile->specialty->name }}
                </div>

                @endif

                @if ($prescription->doctorProfile->professional_license)

                <div class="doctor-detail">
                    Cédula profesional:
                    {{ $prescription->doctorProfile->professional_license }}
                </div>

                @endif

                @if ($prescription->doctorProfile->phone)

                <div class="doctor-detail">
                    Tel.
                    {{ $prescription->doctorProfile->phone }}
                </div>

                @endif

            </td>

        </tr>

    </table>


    {{-- PACIENTE --}}
    <div class="section">

        <div class="section-title">
            Paciente
        </div>

        <table class="patient-table">

            <tr>

                <td class="patient-name">

                    <span class="label">
                        Nombre
                    </span>

                    <div class="value">
                        {{ $prescription->patient->first_name }}
                        {{ $prescription->patient->last_name }}
                        {{ $prescription->patient->second_last_name }}
                    </div>

                </td>

                <td class="patient-birth">

                    <span class="label">
                        Fecha de nacimiento
                    </span>

                    <div class="value">

                        @if ($prescription->patient->birth_date)

                        {{ $prescription->patient->birth_date->format('d/m/Y') }}

                        ·

                        {{ $prescription->patient->birth_date->age }}
                        años

                        @else
                        —
                        @endif

                    </div>

                </td>

                <td class="patient-date">

                    <span class="label">
                        Fecha receta
                    </span>

                    <div class="value">
                        {{ $prescription->prescribed_at->format('d/m/Y') }}
                    </div>

                    <div class="value">
                        {{ $prescription->prescribed_at->format('H:i') }}
                    </div>

                </td>

            </tr>

        </table>

    </div>


    {{-- PRESCRIPCIÓN --}}
    <div class="section">

        <div class="section-title">
            Prescripción
        </div>

        @foreach ($prescription->items as $item)

        <div class="medication">

            <div class="medication-name">
                {{ $loop->iteration }}.
                {{ $item->medication_name }}
            </div>

            @if ($item->presentation)

            <div class="presentation">
                {{ $item->presentation }}
            </div>

            @endif

            <table class="medication-data">

                <tr>

                    <td>

                        <span class="label">
                            Dosis
                        </span>

                        <div class="value">
                            {{ $item->dose ?: '—' }}
                        </div>

                    </td>

                    <td>

                        <span class="label">
                            Frecuencia
                        </span>

                        <div class="value">
                            {{ $item->frequency ?: '—' }}
                        </div>

                    </td>

                    <td>

                        <span class="label">
                            Duración
                        </span>

                        <div class="value">
                            {{ $item->duration ?: '—' }}
                        </div>

                    </td>

                </tr>

            </table>

            @if ($item->instructions)

            <div class="instructions">

                <strong>
                    Indicaciones:
                </strong>

                {{ $item->instructions }}

            </div>

            @endif

        </div>

        @endforeach

    </div>


    {{-- INDICACIONES GENERALES --}}
    @if ($prescription->general_instructions)

    <div class="section">

        <div class="section-title">
            Indicaciones generales
        </div>

        <div class="general">
            {!! nl2br(
            e($prescription->general_instructions)
            ) !!}
        </div>

    </div>

    @endif


    {{-- FIRMA --}}
    <table class="signature-table">

        <tr>

            <td class="signature-spacer"></td>

            <td class="signature-cell">

                <div class="signature-box">

                    @if (
                    $signaturePath
                    && file_exists($signaturePath)
                    )

                    <img
                        src="{{ $signaturePath }}"
                        alt="Firma"
                        class="signature-image">

                    @endif

                    <div class="signature-line"></div>

                    <div class="signature-name">
                        Dr.
                        {{ $prescription->doctorProfile->first_name }}
                        {{ $prescription->doctorProfile->last_name }}
                        {{ $prescription->doctorProfile->second_last_name }}
                    </div>

                    @if ($prescription->doctorProfile->specialty)

                    <div class="signature-specialty">
                        {{ $prescription->doctorProfile->specialty->name }}
                    </div>

                    @endif

                    @if (
                    $prescription->doctorProfile->professional_license
                    )

                    <div class="signature-license">
                        Cédula profesional:
                        {{ $prescription->doctorProfile->professional_license }}
                    </div>

                    @endif

                </div>

            </td>

        </tr>

    </table>


    {{-- FOOTER --}}
    <div class="footer">

        @if ($practice->print_footer)

        {{ $practice->print_footer }}

        @else

        @if ($practice->phone)
        Tel. {{ $practice->phone }}
        @endif

        @if ($practice->whatsapp)
        · WhatsApp {{ $practice->whatsapp }}
        @endif

        @if ($practice->email)
        · {{ $practice->email }}
        @endif

        @endif

    </div>

</body>

</html>