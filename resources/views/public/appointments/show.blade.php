<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestionar cita · DocTotal</title>
    <style>
        :root { color-scheme: light; font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
        body { margin:0; background:#f5f7fb; color:#1f2937; }
        .wrap { min-height:100vh; display:grid; place-items:center; padding:24px; }
        .card { width:min(100%,560px); background:#fff; border:1px solid #e5e7eb; border-radius:20px; padding:28px; box-shadow:0 16px 50px rgba(15,23,42,.08); }
        .eyebrow { font-size:12px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; color:#64748b; }
        h1 { margin:8px 0 6px; font-size:28px; }
        .muted { color:#64748b; }
        .detail { margin:22px 0; padding:18px; border-radius:14px; background:#f8fafc; }
        .detail strong { display:block; margin-top:4px; font-size:18px; }
        .status { margin:16px 0; padding:12px 14px; border-radius:12px; background:#ecfdf5; color:#065f46; }
        .actions { display:grid; gap:12px; margin-top:22px; }
        button { width:100%; border:0; border-radius:12px; padding:13px 16px; font-weight:700; cursor:pointer; }
        .primary { background:#111827; color:#fff; }
        .danger { background:#fff1f2; color:#be123c; border:1px solid #fecdd3; }
        textarea { width:100%; box-sizing:border-box; min-height:80px; resize:vertical; border:1px solid #d1d5db; border-radius:12px; padding:12px; margin:8px 0 12px; font:inherit; }
        .state { display:inline-flex; padding:6px 10px; border-radius:999px; background:#eef2ff; color:#4338ca; font-size:13px; font-weight:700; }
    </style>
</head>
<body>
<div class="wrap">
    <main class="card">
        <div class="eyebrow">DocTotal · Cita</div>
        <h1>Gestiona tu cita</h1>
        <p class="muted">Este enlace permite únicamente confirmar o cancelar esta cita.</p>

        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        <div class="detail">
            <span class="muted">Fecha y hora</span>
            <strong>{{ $appointment->starts_at->format('d/m/Y H:i') }}</strong>
            @if ($appointment->doctorProfile)
                <p class="muted" style="margin-bottom:0">
                    Profesional: {{ $appointment->doctorProfile->first_name }} {{ $appointment->doctorProfile->last_name }}
                </p>
            @endif
        </div>

        <span class="state">
            @switch($appointment->status)
                @case('scheduled') Pendiente de confirmación @break
                @case('confirmed') Confirmada @break
                @case('cancelled') Cancelada @break
                @case('checked_in') Registrada en recepción @break
                @case('in_progress') En atención @break
                @case('completed') Finalizada @break
                @case('no_show') No presentada @break
                @default {{ $appointment->status }}
            @endswitch
        </span>

        <div class="actions">
            @if ($appointment->status === \App\Models\Appointment::STATUS_SCHEDULED)
                <form method="POST" action="{{ route('public.appointments.confirm', ['token' => $token]) }}">
                    @csrf
                    <button class="primary" type="submit">Confirmar mi cita</button>
                </form>
            @endif

            @if (in_array($appointment->status, [\App\Models\Appointment::STATUS_SCHEDULED, \App\Models\Appointment::STATUS_CONFIRMED], true))
                <form method="POST" action="{{ route('public.appointments.cancel', ['token' => $token]) }}">
                    @csrf
                    <label class="muted" for="cancellation_reason">Motivo de cancelación (opcional)</label>
                    <textarea id="cancellation_reason" name="cancellation_reason" maxlength="500">{{ old('cancellation_reason') }}</textarea>
                    @error('cancellation_reason')
                        <p style="color:#be123c">{{ $message }}</p>
                    @enderror
                    <button class="danger" type="submit">Cancelar mi cita</button>
                </form>
            @endif
        </div>

        <p class="muted" style="margin-top:24px;font-size:13px">
            Por seguridad, este enlace no muestra expediente, notas clínicas ni datos personales adicionales.
        </p>
    </main>
</div>
</body>
</html>
