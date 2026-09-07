<x-layouts.internal>
    <div class="space-y-8">
        @php
            $statusMeta = match ($status) {
                'ready' => [
                    'label' => 'Operativo',
                    'title' => 'La estrategia de respaldo está correctamente declarada.',
                    'classes' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
                    'badge' => 'bg-emerald-100 text-emerald-700',
                ],
                'unsafe' => [
                    'label' => 'Inseguro',
                    'title' => 'Hay una condición de durabilidad que debe corregirse antes de producción.',
                    'classes' => 'border-rose-200 bg-rose-50 text-rose-900',
                    'badge' => 'bg-rose-100 text-rose-700',
                ],
                default => [
                    'label' => 'Incompleto',
                    'title' => 'La estrategia de respaldo aún no está completa.',
                    'classes' => 'border-amber-200 bg-amber-50 text-amber-900',
                    'badge' => 'bg-amber-100 text-amber-700',
                ],
            };
        @endphp

        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-indigo-600">Operación y continuidad</p>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-950">
                    Backups y restauración
                </h1>
                <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-600">
                    Visibilidad de la estrategia de respaldo de producción definida por DocTotal.
                    Esta pantalla es informativa: no ejecuta, descarga ni restaura copias de seguridad.
                </p>
            </div>

            <a href="{{ route('internal.dashboard') }}"
               class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                Volver al resumen
            </a>
        </div>

        <section class="rounded-2xl border p-5 shadow-sm {{ $statusMeta['classes'] }}">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusMeta['badge'] }}">
                        {{ $statusMeta['label'] }}
                    </span>
                    <h2 class="mt-3 text-lg font-semibold">{{ $statusMeta['title'] }}</h2>
                    <p class="mt-1 max-w-3xl text-sm opacity-80">
                        El estado se calcula con el mismo checker canónico usado por production readiness.
                    </p>
                </div>
                <code class="rounded-lg bg-white/70 px-3 py-2 text-xs">php artisan doctotal:check-data-durability</code>
            </div>
        </section>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @php
                $cards = [
                    ['label' => 'Base de datos', 'ok' => $backup['database'], 'value' => $backup['database'] ? 'Incluida' : 'No declarada'],
                    ['label' => 'Archivos clínicos', 'ok' => $backup['private_files'], 'value' => $backup['private_files'] ? 'Incluidos' : 'No declarados'],
                    ['label' => 'Frecuencia', 'ok' => $backup['frequency_hours'] >= 1 && $backup['frequency_hours'] <= 168, 'value' => $backup['frequency_hours'] > 0 ? 'Cada '.$backup['frequency_hours'].' h' : 'Sin definir'],
                    ['label' => 'Copias mínimas', 'ok' => $backup['retention_copies'] >= 2, 'value' => $backup['retention_copies'] > 0 ? $backup['retention_copies'].' copias' : 'Sin definir'],
                ];
            @endphp

            @foreach ($cards as $card)
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
                        <span class="h-2.5 w-2.5 rounded-full {{ $card['ok'] ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                    </div>
                    <p class="mt-3 text-xl font-semibold text-slate-950">{{ $card['value'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="text-lg font-semibold text-slate-950">Cobertura de backup</h2>
                <dl class="mt-5 space-y-4 text-sm">
                    <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                        <dt class="text-slate-500">Backups habilitados</dt>
                        <dd class="font-medium {{ $backup['enabled'] ? 'text-emerald-700' : 'text-amber-700' }}">{{ $backup['enabled'] ? 'Sí' : 'No' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                        <dt class="text-slate-500">Mecanismo / proveedor</dt>
                        <dd class="max-w-xs break-words text-right font-medium text-slate-900">{{ $backup['provider'] ?? 'No configurado' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                        <dt class="text-slate-500">Base de datos</dt>
                        <dd class="font-medium text-slate-900">{{ $backup['database'] ? 'Incluida' : 'Pendiente' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-slate-500">Archivos privados</dt>
                        <dd class="font-medium text-slate-900">{{ $backup['private_files'] ? 'Incluidos' : 'Pendientes' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="text-lg font-semibold text-slate-950">Restauración</h2>
                <dl class="mt-5 space-y-4 text-sm">
                    <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                        <dt class="text-slate-500">Runbook versionado</dt>
                        <dd class="font-medium {{ $restore['runbook_exists'] ? 'text-emerald-700' : 'text-amber-700' }}">{{ $restore['runbook_exists'] ? 'Disponible' : 'No disponible' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                        <dt class="text-slate-500">Ruta del runbook</dt>
                        <dd class="max-w-sm break-all text-right font-mono text-xs text-slate-700">{{ $restore['runbook'] ?: 'No configurada' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-slate-500">Verificación posterior</dt>
                        <dd class="font-medium text-slate-900">{{ $restore['verification_required'] ? 'Obligatoria' : 'No exigida' }}</dd>
                    </div>
                </dl>
            </section>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950">Retención clínica</h2>
                    <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-600">
                        La retención de backups y la retención legal de información clínica son conceptos distintos.
                        DocTotal no realiza borrado automático mientras no exista una política legal y operativa aprobada.
                    </p>
                </div>

                <div class="grid min-w-64 gap-3 sm:grid-cols-2">
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Modo</p>
                        <p class="mt-1 font-semibold text-slate-900">{{ $retention['mode'] ?: 'No definido' }}</p>
                    </div>
                    <div class="rounded-xl {{ $retention['automatic_deletion_enabled'] ? 'bg-rose-50' : 'bg-emerald-50' }} p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Borrado automático</p>
                        <p class="mt-1 font-semibold {{ $retention['automatic_deletion_enabled'] ? 'text-rose-700' : 'text-emerald-700' }}">
                            {{ $retention['automatic_deletion_enabled'] ? 'Habilitado' : 'Deshabilitado' }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        @if ($failures !== [])
            <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm sm:p-6">
                <h2 class="text-lg font-semibold text-amber-950">Requiere atención</h2>
                <p class="mt-1 text-sm text-amber-800">
                    Corrige estas condiciones en la infraestructura/configuración de producción antes de considerar el entorno listo.
                </p>
                <ul class="mt-4 space-y-3">
                    @foreach ($failures as $failure)
                        <li class="rounded-xl border border-amber-200 bg-white/70 p-4">
                            <code class="text-xs font-semibold text-amber-900">{{ $failure['key'] }}</code>
                            <p class="mt-1 text-sm text-amber-900">{{ $failure['message'] }}</p>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5 sm:p-6">
            <h2 class="font-semibold text-indigo-950">Qué hace y qué no hace esta pantalla</h2>
            <p class="mt-2 text-sm leading-6 text-indigo-900/80">
                DocTotal valida y hace visible la estrategia declarada. El respaldo real lo ejecuta la infraestructura configurada.
                Esta interfaz no contiene credenciales, no expone archivos clínicos y no permite descargar ni restaurar copias de seguridad.
            </p>
        </section>
    </div>
</x-layouts.internal>
