<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">

    <title>
        Receta médica
    </title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f8fafc;
            color: #0f172a;
            font-family: Arial, Helvetica, sans-serif;
        }

        .print-actions {
            width: 216mm;
            margin: 24px auto 0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .button {
            display: inline-block;
            padding: 10px 16px;
            border-radius: 7px;
            border: 1px solid #cbd5e1;
            background: white;
            color: #334155;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .button-primary {
            border-color: #0f172a;
            background: #0f172a;
            color: white;
        }

        .page {
            width: 216mm;
            min-height: 279mm;
            margin: 24px auto;
            padding: 16mm 18mm;
            background: white;
            box-shadow:
                0 1px 3px rgba(15, 23, 42, .08),
                0 10px 30px rgba(15, 23, 42, .08);
        }

        .header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 28px;
            padding-bottom: 18px;
            border-bottom: 2px solid #0f172a;
        }

        .practice {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            flex: 1;
        }

        .logo {
            width: 72px;
            height: 72px;
            object-fit: contain;
        }

        .practice-name {
            margin: 0;
            font-size: 22px;
            line-height: 1.2;
            font-weight: 700;
        }

        .practice-description {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 12px;
            line-height: 1.5;
        }

        .practice-contact {
            margin-top: 8px;
            color: #475569;
            font-size: 12px;
            line-height: 1.6;
        }

        .doctor {
            min-width: 230px;
            text-align: right;
            font-size: 12px;
            line-height: 1.55;
        }

        .doctor-name {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
        }

        .doctor-specialty {
            color: #475569;
        }

        .section {
            margin-top: 24px;
        }

        .section-title {
            margin: 0 0 10px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #64748b;
        }

        .patient-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr 140px;
            gap: 20px;
            padding: 14px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .label {
            display: block;
            margin-bottom: 3px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #94a3b8;
        }

        .value {
            font-size: 13px;
            line-height: 1.5;
        }

        .medication {
            padding: 18px 0;
            border-bottom: 1px solid #e2e8f0;
            page-break-inside: avoid;
        }

        .medication:last-child {
            border-bottom: none;
        }

        .medication-name {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
        }

        .presentation {
            margin: 5px 0 0;
            color: #475569;
            font-size: 12px;
            line-height: 1.5;
        }

        .medication-grid {
            display: grid;
            grid-template-columns:
                repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-top: 12px;
        }

        .instructions {
            margin-top: 12px;
            padding: 10px 12px;
            background: #f8fafc;
            border-radius: 6px;
            font-size: 12px;
            line-height: 1.5;
        }

        .general {
            padding: 14px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 12px;
            line-height: 1.6;
            white-space: pre-line;
        }

        .signature {
            display: flex;
            justify-content: flex-end;
            margin-top: 56px;
            page-break-inside: avoid;
        }

        .signature-box {
            width: 280px;
            text-align: center;
        }

        .signature-image {
            display: block;
            max-width: 180px;
            max-height: 80px;
            margin: 0 auto 8px;
            object-fit: contain;
        }

        .signature-line {
            border-top: 1px solid #0f172a;
            margin-bottom: 8px;
        }

        .signature-name {
            font-size: 13px;
            font-weight: 700;
        }

        .signature-specialty,
        .signature-license {
            margin-top: 3px;
            color: #64748b;
            font-size: 11px;
        }

        .footer {
            margin-top: 44px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 10px;
            text-align: center;
            line-height: 1.5;
        }

        @media print {
            @page {
                size: Letter;
                margin: 0;
            }

            body {
                background: white;
            }

            .print-actions {
                display: none;
            }

            .page {
                margin: 0;
                width: 216mm;
                min-height: 279mm;
                padding: 16mm 18mm;
                box-shadow: none;
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
        }
    </style>
</head>

<body>

    <div class="print-actions">

        <button
            type="button"
            class="button"
            onclick="window.close()">
            Cerrar
        </button>

        <a
            href="{{ route('prescriptions.pdf', [
                'uuid' => $prescription->uuid
            ]) }}"
            class="button">
            Descargar PDF
        </a>

        <button
            type="button"
            class="button button-primary"
            onclick="window.print()">
            Imprimir receta
        </button>

    </div>

    <main class="page">

        @if ($prescription->status === 'cancelled')
        <div class="cancelled-banner">
            RECETA ANULADA
        </div>
        @endif

        <header class="header">

            <div class="practice">

                @if ($practice->logo_path)
                <img
                    src="{{ asset('storage/'.$practice->logo_path) }}"
                    alt="Logo"
                    class="logo">
                @endif

                <div>

                    <h1 class="practice-name">
                        {{ $practice->public_name }}
                    </h1>

                    @if ($practice->description)
                    <p class="practice-description">
                        {{ $practice->description }}
                    </p>
                    @endif

                    <div class="practice-contact">

                        @if ($practice->address_line_1)
                        <div>
                            {{ $practice->address_line_1 }}

                            @if ($practice->address_line_2)
                            , {{ $practice->address_line_2 }}
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

                </div>

            </div>

            <div class="doctor">

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
                <div>
                    Cédula profesional:
                    {{ $prescription->doctorProfile->professional_license }}
                </div>
                @endif

                @if ($prescription->doctorProfile->phone)
                <div>
                    Tel.
                    {{ $prescription->doctorProfile->phone }}
                </div>
                @endif

            </div>

        </header>

        <section class="section">

            <h2 class="section-title">
                Paciente
            </h2>

            <div class="patient-grid">

                <div>

                    <span class="label">
                        Nombre
                    </span>

                    <div class="value">
                        {{ $prescription->patient->first_name }}
                        {{ $prescription->patient->last_name }}
                        {{ $prescription->patient->second_last_name }}
                    </div>

                </div>

                <div>

                    <span class="label">
                        Fecha de nacimiento
                    </span>

                    <div class="value">
                        @if ($prescription->patient->birth_date)
                        {{ $prescription->patient->birth_date->format('d/m/Y') }}
                        ·
                        {{ $prescription->patient->birth_date->age }} años
                        @else
                        —
                        @endif
                    </div>

                </div>

                <div>

                    <span class="label">
                        Fecha receta
                    </span>

                    <div class="value">
                        {{ $prescription->prescribed_at->format('d/m/Y') }}
                    </div>

                    <div class="value">
                        {{ $prescription->prescribed_at->format('H:i') }}
                    </div>

                </div>

            </div>

        </section>

        <section class="section">

            <h2 class="section-title">
                Prescripción
            </h2>

            @foreach ($prescription->items as $item)

            <article class="medication">

                <h3 class="medication-name">
                    {{ $loop->iteration }}.
                    {{ $item->medication_name }}
                </h3>

                @if ($item->presentation)
                <p class="presentation">
                    {{ $item->presentation }}
                </p>
                @endif

                <div class="medication-grid">

                    <div>

                        <span class="label">
                            Dosis
                        </span>

                        <div class="value">
                            {{ $item->dose ?: '—' }}
                        </div>

                    </div>

                    <div>

                        <span class="label">
                            Frecuencia
                        </span>

                        <div class="value">
                            {{ $item->frequency ?: '—' }}
                        </div>

                    </div>

                    <div>

                        <span class="label">
                            Duración
                        </span>

                        <div class="value">
                            {{ $item->duration ?: '—' }}
                        </div>

                    </div>

                </div>

                @if ($item->instructions)

                <div class="instructions">

                    <strong>
                        Indicaciones:
                    </strong>

                    {{ $item->instructions }}

                </div>

                @endif

            </article>

            @endforeach

        </section>

        @if ($prescription->general_instructions)

        <section class="section">

            <h2 class="section-title">
                Indicaciones generales
            </h2>

            <div class="general">
                {{ $prescription->general_instructions }}
            </div>

        </section>

        @endif

        <div class="signature">

            <div class="signature-box">

                @if ($prescription->doctorProfile->signature_path)

                <img
                    src="{{ asset(
                            'storage/'.
                            $prescription->doctorProfile->signature_path
                        ) }}"
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

                @if ($prescription->doctorProfile->professional_license)

                <div class="signature-license">
                    Cédula profesional:
                    {{ $prescription->doctorProfile->professional_license }}
                </div>

                @endif

            </div>

        </div>

        <footer class="footer">

            @if ($practice->print_footer)

            {{ $practice->print_footer }}

            @else

            @if ($practice->phone)
            Tel. {{ $practice->phone }}
            @endif

            @if ($practice->whatsapp)
            · WhatsApp {{ $practice->whatsapp }}
            @endif

            @endif

        </footer>

    </main>

</body>

</html>