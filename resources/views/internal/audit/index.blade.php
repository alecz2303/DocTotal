<x-layouts.internal>
    <div class="space-y-8">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-indigo-600">Seguridad y trazabilidad</p>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-950">
                    Auditoría
                </h1>
                <p class="mt-1 max-w-3xl text-sm text-slate-600">
                    Consulta operativa global de eventos registrados por DocTotal.
                    Esta pantalla es de solo lectura.
                </p>
            </div>

            <a href="{{ route('internal.dashboard') }}"
               class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                Volver al resumen
            </a>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @php
                $cards = [
                    ['label' => 'Eventos totales', 'value' => $summary['total']],
                    ['label' => 'Hoy', 'value' => $summary['today']],
                    ['label' => 'Últimos 7 días', 'value' => $summary['last_7_days']],
                    ['label' => 'Acciones distintas', 'value' => $summary['distinct_actions']],
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

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <form method="GET"
                  action="{{ route('internal.audit.index') }}"
                  class="grid gap-4 lg:grid-cols-4 lg:items-end">
                <div>
                    <label for="action" class="block text-sm font-medium text-slate-700">
                        Acción
                    </label>
                    <select id="action"
                            name="action"
                            class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Todas</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action }}"
                                @selected(($filters['action'] ?? '') === $action)>
                                {{ $action }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="tenant_id" class="block text-sm font-medium text-slate-700">
                        Tenant
                    </label>
                    <select id="tenant_id"
                            name="tenant_id"
                            class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Todos</option>
                        @foreach ($tenants as $tenant)
                            <option value="{{ $tenant->id }}"
                                @selected((string) ($filters['tenant_id'] ?? '') === (string) $tenant->id)>
                                {{ $tenant->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="user_id" class="block text-sm font-medium text-slate-700">
                        ID de usuario
                    </label>
                    <input id="user_id"
                           name="user_id"
                           type="number"
                           min="1"
                           value="{{ $filters['user_id'] ?? '' }}"
                           placeholder="Ej. 25"
                           class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div class="flex gap-2">
                    <button type="submit"
                            class="inline-flex flex-1 items-center justify-center rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
                        Filtrar
                    </button>

                    <a href="{{ route('internal.audit.index') }}"
                       class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        Limpiar
                    </a>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                <h2 class="text-base font-semibold text-slate-950">Eventos de auditoría</h2>
                <p class="text-sm text-slate-500">
                    Los eventos se presentan del más reciente al más antiguo.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-5 py-3 sm:px-6">Fecha</th>
                            <th class="px-5 py-3">Tenant</th>
                            <th class="px-5 py-3">Usuario</th>
                            <th class="px-5 py-3">Acción</th>
                            <th class="px-5 py-3">Objeto</th>
                            <th class="px-5 py-3">Descripción</th>
                            <th class="px-5 py-3 sm:pr-6">Origen</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($events as $event)
                            <tr class="align-top">
                                <td class="whitespace-nowrap px-5 py-4 text-slate-600 sm:px-6">
                                    {{ $event->created_at?->format('d/m/Y H:i:s') ?? '—' }}
                                </td>

                                <td class="px-5 py-4">
                                    @if ($event->tenant)
                                        <a href="{{ route('internal.tenants.show', $event->tenant) }}"
                                           class="font-medium text-indigo-700 hover:underline">
                                            {{ $event->tenant->name }}
                                        </a>
                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ $event->tenant->slug }}
                                        </div>
                                    @else
                                        <span class="text-slate-500">Tenant no disponible</span>
                                    @endif
                                </td>

                                <td class="px-5 py-4">
                                    @if ($event->user)
                                        <div class="font-medium text-slate-800">{{ $event->user->name }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $event->user->email }}</div>
                                    @else
                                        <span class="text-slate-500">Sistema / usuario eliminado</span>
                                    @endif
                                </td>

                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                        {{ $event->action }}
                                    </span>
                                </td>

                                <td class="px-5 py-4 text-slate-600">
                                    @if ($event->auditable_type)
                                        <div class="max-w-xs break-all text-xs">
                                            {{ $event->auditable_type }}
                                        </div>
                                        <div class="mt-1 text-xs text-slate-400">
                                            ID {{ $event->auditable_id ?? '—' }}
                                        </div>
                                    @else
                                        —
                                    @endif
                                </td>

                                <td class="max-w-md px-5 py-4 text-slate-600">
                                    {{ $event->description ?: 'Sin descripción.' }}
                                </td>

                                <td class="px-5 py-4 text-slate-600 sm:pr-6">
                                    <div>{{ $event->ip_address ?: 'IP no disponible' }}</div>
                                    @if ($event->user_agent)
                                        <div class="mt-1 max-w-xs truncate text-xs text-slate-400"
                                             title="{{ $event->user_agent }}">
                                            {{ $event->user_agent }}
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-slate-500">
                                    No hay eventos que coincidan con los filtros.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($events->hasPages())
                <div class="border-t border-slate-200 px-5 py-4 sm:px-6">
                    {{ $events->withQueryString()->links() }}
                </div>
            @endif
        </section>

        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            <strong>Solo lectura.</strong>
            La consola interna no permite modificar ni eliminar eventos de auditoría.
        </div>
    </div>
</x-layouts.internal>
