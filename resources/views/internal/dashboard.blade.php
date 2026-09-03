<x-layouts.internal>
    <div class="space-y-8">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <p class="text-sm font-semibold text-indigo-600">
                    DocTotal Internal
                </p>

                <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-950">
                    Centro operativo SaaS
                </h1>

                <p class="mt-2 max-w-3xl text-sm text-slate-600">
                    Estado global de tenants, suscripciones, cobros, comunicaciones
                    y trazabilidad operativa sin entrar al contexto clínico.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('internal.tenants.index') }}"
                   class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                    Ver tenants
                </a>

                <a href="{{ route('internal.billing.index') }}"
                   class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-slate-800">
                    Revisar incidencias
                </a>
            </div>
        </div>

        <section class="rounded-2xl border p-5 shadow-sm
            {{ $overview['health']['status'] === 'healthy'
                ? 'border-emerald-200 bg-emerald-50'
                : 'border-amber-200 bg-amber-50' }}">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider
                        {{ $overview['health']['status'] === 'healthy'
                            ? 'text-emerald-700'
                            : 'text-amber-700' }}">
                        Salud operativa
                    </p>

                    <h2 class="mt-1 text-xl font-semibold
                        {{ $overview['health']['status'] === 'healthy'
                            ? 'text-emerald-950'
                            : 'text-amber-950' }}">
                        @if ($overview['health']['status'] === 'healthy')
                            Sin incidencias operativas detectadas
                        @else
                            Requiere atención operativa
                        @endif
                    </h2>

                    <p class="mt-1 text-sm
                        {{ $overview['health']['status'] === 'healthy'
                            ? 'text-emerald-800'
                            : 'text-amber-800' }}">
                        {{ number_format($overview['health']['incidents']) }}
                        incidencias contabilizadas entre suscripciones vencidas,
                        pagos fallidos y comunicaciones con error.
                    </p>
                </div>

                <div class="text-4xl font-semibold
                    {{ $overview['health']['status'] === 'healthy'
                        ? 'text-emerald-700'
                        : 'text-amber-700' }}">
                    {{ number_format($overview['health']['incidents']) }}
                </div>
            </div>
        </section>

        <section>
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950">Vista general</h2>
                    <p class="text-sm text-slate-500">Indicadores principales de la plataforma.</p>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <a href="{{ route('internal.tenants.index') }}"
                   class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-200 hover:shadow-md">
                    <p class="text-sm font-medium text-slate-500">Tenants</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-950">
                        {{ number_format($overview['tenants']['total']) }}
                    </p>
                    <p class="mt-3 text-xs text-slate-500">
                        {{ number_format($overview['tenants']['trial']) }} en trial ·
                        {{ number_format($overview['tenants']['suspended']) }} suspendidos
                    </p>
                </a>

                <a href="{{ route('internal.billing.index') }}"
                   class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-200 hover:shadow-md">
                    <p class="text-sm font-medium text-slate-500">Suscripciones</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-950">
                        {{ number_format($overview['subscriptions']['active']) }}
                    </p>
                    <p class="mt-3 text-xs {{ $overview['subscriptions']['past_due'] > 0 ? 'font-medium text-rose-600' : 'text-slate-500' }}">
                        {{ number_format($overview['subscriptions']['past_due']) }} vencidas
                    </p>
                </a>

                <a href="{{ route('internal.billing.index') }}"
                   class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-200 hover:shadow-md">
                    <p class="text-sm font-medium text-slate-500">Pagos fallidos</p>
                    <p class="mt-2 text-3xl font-semibold {{ $overview['payments']['failed'] > 0 ? 'text-rose-700' : 'text-slate-950' }}">
                        {{ number_format($overview['payments']['failed']) }}
                    </p>
                    <p class="mt-3 text-xs text-slate-500">
                        Abrir monitor de facturación
                    </p>
                </a>

                <a href="{{ route('internal.communications.index') }}"
                   class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-200 hover:shadow-md">
                    <p class="text-sm font-medium text-slate-500">Comunicaciones fallidas</p>
                    <p class="mt-2 text-3xl font-semibold {{ $overview['communications']['failed'] > 0 ? 'text-rose-700' : 'text-slate-950' }}">
                        {{ number_format($overview['communications']['failed']) }}
                    </p>
                    <p class="mt-3 text-xs text-slate-500">
                        {{ number_format($overview['communications']['pending']) }} pendientes ·
                        {{ number_format($overview['communications']['exhausted']) }} agotadas
                    </p>
                </a>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Usuarios SaaS</p>
                <p class="mt-2 text-2xl font-semibold text-slate-950">
                    {{ number_format($overview['users']['total']) }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Onboarding pendiente</p>
                <p class="mt-2 text-2xl font-semibold text-slate-950">
                    {{ number_format($overview['tenants']['onboarding_pending']) }}
                </p>
            </div>

            <a href="{{ route('internal.communications.index') }}"
               class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-200 hover:shadow-md">
                <p class="text-sm font-medium text-slate-500">Comunicaciones pendientes</p>
                <p class="mt-2 text-2xl font-semibold text-slate-950">
                    {{ number_format($overview['communications']['pending']) }}
                </p>
            </a>

            <a href="{{ route('internal.audit.index') }}"
               class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-200 hover:shadow-md">
                <p class="text-sm font-medium text-slate-500">Auditoría hoy</p>
                <p class="mt-2 text-2xl font-semibold text-slate-950">
                    {{ number_format($overview['audit']['today']) }}
                </p>
                <p class="mt-3 text-xs text-slate-500">
                    Ver trazabilidad
                </p>
            </a>
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 sm:px-6">
                    <div>
                        <h2 class="font-semibold text-slate-950">Pagos fallidos recientes</h2>
                        <p class="text-sm text-slate-500">Últimas incidencias registradas.</p>
                    </div>

                    <a href="{{ route('internal.billing.index') }}"
                       class="text-sm font-medium text-indigo-700 hover:underline">
                        Ver todos
                    </a>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse ($overview['recent_failed_payments'] as $payment)
                        <div class="flex items-start justify-between gap-4 px-5 py-4 sm:px-6">
                            <div class="min-w-0">
                                @if ($payment->tenant)
                                    <a href="{{ route('internal.tenants.show', $payment->tenant) }}"
                                       class="font-medium text-slate-900 hover:text-indigo-700">
                                        {{ $payment->tenant->name }}
                                    </a>
                                @else
                                    <p class="font-medium text-slate-700">Tenant no disponible</p>
                                @endif

                                <p class="mt-1 truncate text-xs text-slate-500">
                                    {{ $payment->failure_code ?: 'Sin código' }}
                                    @if ($payment->failure_message)
                                        · {{ $payment->failure_message }}
                                    @endif
                                </p>
                            </div>

                            <div class="shrink-0 text-right">
                                <p class="font-semibold text-rose-700">
                                    {{ number_format($payment->amount / 100, 2) }}
                                    {{ $payment->currency }}
                                </p>
                                <p class="mt-1 text-xs text-slate-400">
                                    {{ $payment->failed_at?->format('d/m H:i') ?? '—' }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-8 text-center text-sm text-slate-500">
                            No hay pagos fallidos.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 sm:px-6">
                    <div>
                        <h2 class="font-semibold text-slate-950">Errores de comunicación recientes</h2>
                        <p class="text-sm text-slate-500">Sin mostrar destinatario ni contenido.</p>
                    </div>

                    <a href="{{ route('internal.communications.index') }}"
                       class="text-sm font-medium text-indigo-700 hover:underline">
                        Ver todos
                    </a>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse ($overview['recent_failed_communications'] as $communication)
                        <div class="flex items-start justify-between gap-4 px-5 py-4 sm:px-6">
                            <div class="min-w-0">
                                @if ($communication->tenant)
                                    <a href="{{ route('internal.tenants.show', $communication->tenant) }}"
                                       class="font-medium text-slate-900 hover:text-indigo-700">
                                        {{ $communication->tenant->name }}
                                    </a>
                                @else
                                    <p class="font-medium text-slate-700">Tenant no disponible</p>
                                @endif

                                <p class="mt-1 truncate text-xs text-slate-500">
                                    {{ $communication->type }} · {{ $communication->channel }}
                                </p>
                            </div>

                            <div class="shrink-0 text-right">
                                <p class="text-sm font-medium text-rose-700">
                                    {{ $communication->attempt_count }} intento(s)
                                </p>
                                <p class="mt-1 text-xs text-slate-400">
                                    {{ $communication->failed_at?->format('d/m H:i') ?? '—' }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-8 text-center text-sm text-slate-500">
                            No hay comunicaciones fallidas.
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 sm:px-6">
                <div>
                    <h2 class="font-semibold text-slate-950">Tenants recientes</h2>
                    <p class="text-sm text-slate-500">Últimas cuentas incorporadas a DocTotal.</p>
                </div>

                <a href="{{ route('internal.tenants.index') }}"
                   class="text-sm font-medium text-indigo-700 hover:underline">
                    Ver todos
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-5 py-3 sm:px-6">Tenant</th>
                            <th class="px-5 py-3">Estado</th>
                            <th class="px-5 py-3">Onboarding</th>
                            <th class="px-5 py-3 sm:pr-6">Alta</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($overview['recent_tenants'] as $tenant)
                            <tr>
                                <td class="px-5 py-4 sm:px-6">
                                    <a href="{{ route('internal.tenants.show', $tenant) }}"
                                       class="font-medium text-indigo-700 hover:underline">
                                        {{ $tenant->name }}
                                    </a>
                                    <div class="mt-1 text-xs text-slate-500">{{ $tenant->slug }}</div>
                                </td>
                                <td class="px-5 py-4 text-slate-700">
                                    {{ $tenant->status }}
                                    @if ($tenant->suspended_at)
                                        <span class="ml-1 text-xs font-medium text-rose-600">Suspendido</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    @if ($tenant->onboarding_completed_at)
                                        <span class="text-emerald-700">Completo</span>
                                    @else
                                        <span class="text-amber-700">Pendiente</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-600 sm:pr-6">
                                    {{ $tenant->created_at?->format('d/m/Y') ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-8 text-center text-slate-500">
                                    No hay tenants registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section>
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-slate-950">Accesos operativos</h2>
                <p class="text-sm text-slate-500">Herramientas principales de administración SaaS.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <a href="{{ route('internal.tenants.index') }}"
                   class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md">
                    <p class="font-semibold text-slate-950">Tenants</p>
                    <p class="mt-1 text-sm text-slate-500">Cuentas, usuarios y estado operativo.</p>
                </a>

                <a href="{{ route('internal.billing.index') }}"
                   class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md">
                    <p class="font-semibold text-slate-950">Facturación</p>
                    <p class="mt-1 text-sm text-slate-500">Pagos fallidos y suscripciones vencidas.</p>
                </a>

                <a href="{{ route('internal.communications.index') }}"
                   class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md">
                    <p class="font-semibold text-slate-950">Comunicaciones</p>
                    <p class="mt-1 text-sm text-slate-500">Cola, errores, reintentos y cancelaciones.</p>
                </a>

                <a href="{{ route('internal.audit.index') }}"
                   class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md">
                    <p class="font-semibold text-slate-950">Auditoría</p>
                    <p class="mt-1 text-sm text-slate-500">Trazabilidad operativa de solo lectura.</p>
                </a>
            </div>
        </section>
    </div>
</x-layouts.internal>
