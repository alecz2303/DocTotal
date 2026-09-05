@props(['patient'])

@php
    $alerts = app(\App\Services\Clinical\PatientClinicalAlertService::class)
        ->forPatient($patient);
@endphp

@if ($alerts->isNotEmpty())
<section
    {{ $attributes->class([
        'overflow-hidden rounded-2xl border border-amber-200 bg-amber-50/70 shadow-sm',
    ]) }}
    aria-labelledby="patient-clinical-alerts-title">
    <div class="flex items-start justify-between gap-4 border-b border-amber-200/80 px-4 py-3 sm:px-5">
        <div class="flex min-w-0 items-start gap-3">
            <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4.5 w-4.5" aria-hidden="true">
                    <path d="M12 3 2.8 19h18.4L12 3Z" stroke-linejoin="round" />
                    <path d="M12 9v4M12 16.5h.01" stroke-linecap="round" />
                </svg>
            </div>

            <div class="min-w-0">
                <h3 id="patient-clinical-alerts-title" class="text-sm font-bold text-amber-950">
                    Alertas clínicas del expediente
                </h3>
                <p class="mt-0.5 text-xs leading-5 text-amber-800">
                    Información estructurada ya registrada. No constituye diagnóstico ni recomendación terapéutica.
                </p>
            </div>
        </div>

        <span class="shrink-0 rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-bold text-amber-800">
            {{ $alerts->count() }}
        </span>
    </div>

    <div class="grid gap-3 p-4 sm:p-5">
        @foreach ($alerts as $alert)
            @php
                $critical = $alert['severity'] === 'critical';
            @endphp

            <article class="rounded-xl border bg-white/90 px-3.5 py-3 {{ $critical ? 'border-rose-200' : 'border-amber-200' }}">
                <div class="flex items-start gap-3">
                    <span
                        class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full {{ $critical ? 'bg-rose-500' : 'bg-amber-500' }}"
                        aria-hidden="true"></span>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-bold {{ $critical ? 'text-rose-900' : 'text-amber-950' }}">
                                {{ $alert['title'] }}
                            </p>

                            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $critical ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700' }}">
                                {{ $critical ? 'Alta visibilidad' : 'Contexto' }}
                            </span>
                        </div>

                        <p class="mt-1.5 whitespace-pre-line text-sm leading-5 text-slate-800">
                            {{ $alert['message'] }}
                        </p>

                        <p class="mt-2 text-[11px] font-medium text-slate-500">
                            Fuente: {{ $alert['source_label'] }}
                        </p>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
</section>
@endif
