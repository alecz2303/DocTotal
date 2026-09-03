<x-layouts.internal>
    <div class="space-y-8">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-indigo-600">Operación SaaS</p>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-950">
                    Comunicaciones
                </h1>
                <p class="mt-1 max-w-3xl text-sm text-slate-600">
                    Estado operativo global de las comunicaciones transaccionales de DocTotal.
                    La consola muestra metadatos operativos y evita exponer el cuerpo o el destinatario.
                </p>
            </div>

            <a href="{{ route('internal.dashboard') }}"
               class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                Volver al resumen
            </a>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
            @php
                $cards = [
                    ['label' => 'Pendientes', 'value' => $summary['pending']],
                    ['label' => 'Enviadas', 'value' => $summary['sent']],
                    ['label' => 'Fallidas', 'value' => $summary['failed']],
                    ['label' => 'Canceladas', 'value' => $summary['cancelled']],
                    ['label' => 'Con reintento', 'value' => $summary['failed_retry_scheduled']],
                    ['label' => 'Agotadas', 'value' => $summary['failed_exhausted']],
                ];
            @endphp

            @foreach ($cards as $card)
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
                    <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">
                        {{ number_format($card['value']) }}
                    </p>
                </div>
            @endforeach
        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                <h2 class="text-base font-semibold text-slate-950">Comunicaciones fallidas</h2>
                <p class="text-sm text-slate-500">
                    Errores, intentos y reintentos programados. No se muestra el cuerpo ni el destinatario.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-5 py-3 sm:px-6">Tenant</th>
                            <th class="px-5 py-3">Tipo</th>
                            <th class="px-5 py-3">Canal</th>
                            <th class="px-5 py-3">Falló</th>
                            <th class="px-5 py-3">Intentos</th>
                            <th class="px-5 py-3">Próximo intento</th>
                            <th class="px-5 py-3 sm:pr-6">Error</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($failedCommunications as $communication)
                            <tr class="align-top">
                                <td class="px-5 py-4 sm:px-6">
                                    @if ($communication->tenant)
                                        <a href="{{ route('internal.tenants.show', $communication->tenant) }}"
                                           class="font-medium text-indigo-700 hover:underline">
                                            {{ $communication->tenant->name }}
                                        </a>
                                        <div class="mt-1 text-xs text-slate-500">{{ $communication->tenant->slug }}</div>
                                    @else
                                        <span class="text-slate-500">Tenant no disponible</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-slate-700">{{ $communication->type }}</td>
                                <td class="px-5 py-4 text-slate-700">{{ $communication->channel }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-600">
                                    {{ $communication->failed_at?->format('d/m/Y H:i') ?? '—' }}
                                </td>
                                <td class="px-5 py-4 text-slate-700">{{ $communication->attempt_count }}</td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    @if ($communication->next_attempt_at)
                                        @if ($communication->next_attempt_at->isPast())
                                            <span class="font-medium text-rose-700">
                                                Vencido · {{ $communication->next_attempt_at->format('d/m/Y H:i') }}
                                            </span>
                                        @else
                                            <span class="text-slate-600">
                                                {{ $communication->next_attempt_at->format('d/m/Y H:i') }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-slate-400">Sin reintento</span>
                                    @endif
                                </td>
                                <td class="max-w-md px-5 py-4 text-slate-600 sm:pr-6">
                                    {{ $communication->last_error ?: 'Sin detalle registrado.' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-slate-500">
                                    No hay comunicaciones fallidas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($failedCommunications->hasPages())
                <div class="border-t border-slate-200 px-5 py-4 sm:px-6">
                    {{ $failedCommunications->withQueryString()->links() }}
                </div>
            @endif
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                <h2 class="text-base font-semibold text-slate-950">Pendientes</h2>
                <p class="text-sm text-slate-500">
                    Cola pendiente ordenada por fecha programada.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-5 py-3 sm:px-6">Tenant</th>
                            <th class="px-5 py-3">Tipo</th>
                            <th class="px-5 py-3">Canal</th>
                            <th class="px-5 py-3">Programada</th>
                            <th class="px-5 py-3 sm:pr-6">Intentos</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($pendingCommunications as $communication)
                            <tr>
                                <td class="px-5 py-4 sm:px-6">
                                    @if ($communication->tenant)
                                        <a href="{{ route('internal.tenants.show', $communication->tenant) }}"
                                           class="font-medium text-indigo-700 hover:underline">
                                            {{ $communication->tenant->name }}
                                        </a>
                                    @else
                                        <span class="text-slate-500">Tenant no disponible</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-slate-700">{{ $communication->type }}</td>
                                <td class="px-5 py-4 text-slate-700">{{ $communication->channel }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-600">
                                    {{ $communication->scheduled_for?->format('d/m/Y H:i') ?? 'Inmediata' }}
                                </td>
                                <td class="px-5 py-4 text-slate-700 sm:pr-6">{{ $communication->attempt_count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-slate-500">
                                    No hay comunicaciones pendientes.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($pendingCommunications->hasPages())
                <div class="border-t border-slate-200 px-5 py-4 sm:px-6">
                    {{ $pendingCommunications->withQueryString()->links() }}
                </div>
            @endif
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                <h2 class="text-base font-semibold text-slate-950">Canceladas</h2>
                <p class="text-sm text-slate-500">
                    Comunicaciones canceladas y motivo operativo registrado.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-5 py-3 sm:px-6">Tenant</th>
                            <th class="px-5 py-3">Tipo</th>
                            <th class="px-5 py-3">Canal</th>
                            <th class="px-5 py-3">Cancelada</th>
                            <th class="px-5 py-3 sm:pr-6">Motivo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($cancelledCommunications as $communication)
                            <tr class="align-top">
                                <td class="px-5 py-4 sm:px-6">
                                    @if ($communication->tenant)
                                        <a href="{{ route('internal.tenants.show', $communication->tenant) }}"
                                           class="font-medium text-indigo-700 hover:underline">
                                            {{ $communication->tenant->name }}
                                        </a>
                                    @else
                                        <span class="text-slate-500">Tenant no disponible</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-slate-700">{{ $communication->type }}</td>
                                <td class="px-5 py-4 text-slate-700">{{ $communication->channel }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-600">
                                    {{ $communication->cancelled_at?->format('d/m/Y H:i') ?? '—' }}
                                </td>
                                <td class="max-w-md px-5 py-4 text-slate-600 sm:pr-6">
                                    {{ $communication->cancellation_reason ?: 'Sin motivo registrado.' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-slate-500">
                                    No hay comunicaciones canceladas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($cancelledCommunications->hasPages())
                <div class="border-t border-slate-200 px-5 py-4 sm:px-6">
                    {{ $cancelledCommunications->withQueryString()->links() }}
                </div>
            @endif
        </section>
    </div>
</x-layouts.internal>
