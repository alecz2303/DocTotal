<x-layouts.internal>
    <div class="space-y-8">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-indigo-600">Operación SaaS</p>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-950">
                    Incidencias de billing
                </h1>
                <p class="mt-1 max-w-3xl text-sm text-slate-600">
                    Visibilidad global de cobros fallidos y suscripciones vencidas.
                    Esta pantalla es de consulta operativa y no modifica el estado financiero.
                </p>
            </div>

            <a href="{{ route('internal.dashboard') }}"
               class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                Volver al resumen
            </a>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Pagos fallidos</p>
                <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">
                    {{ number_format($summary['failed_payments']) }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Suscripciones past due</p>
                <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">
                    {{ number_format($summary['past_due_subscriptions']) }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">En periodo de gracia</p>
                <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">
                    {{ number_format($summary['past_due_in_grace']) }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Gracia vencida</p>
                <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">
                    {{ number_format($summary['past_due_grace_expired']) }}
                </p>
            </div>
        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-slate-950">Pagos fallidos</h2>
                        <p class="text-sm text-slate-500">Intentos de cobro que terminaron en estado failed.</p>
                    </div>
                    <span class="text-sm font-medium text-slate-500">
                        {{ number_format($failedPayments->total()) }} registros
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-5 py-3 sm:px-6">Tenant</th>
                            <th class="px-5 py-3">Monto</th>
                            <th class="px-5 py-3">Intento</th>
                            <th class="px-5 py-3">Fallo</th>
                            <th class="px-5 py-3">Código</th>
                            <th class="px-5 py-3 sm:pr-6">Detalle</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($failedPayments as $payment)
                            <tr class="align-top">
                                <td class="px-5 py-4 sm:px-6">
                                    @if ($payment->tenant)
                                        <a href="{{ route('internal.tenants.show', $payment->tenant) }}"
                                           class="font-medium text-indigo-700 hover:text-indigo-900 hover:underline">
                                            {{ $payment->tenant->name }}
                                        </a>
                                        <div class="mt-1 text-xs text-slate-500">{{ $payment->tenant->slug }}</div>
                                    @else
                                        <span class="font-medium text-slate-500">Tenant no disponible</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 font-medium text-slate-900">
                                    {{ $payment->currency ?? 'MXN' }}
                                    {{ number_format(($payment->amount ?? 0) / 100, 2) }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-600">
                                    {{ $payment->attempted_at?->format('d/m/Y H:i') ?? '—' }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-600">
                                    {{ $payment->failed_at?->format('d/m/Y H:i') ?? '—' }}
                                </td>
                                <td class="px-5 py-4">
                                    @if ($payment->failure_code)
                                        <span class="inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-200">
                                            {{ $payment->failure_code }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="max-w-md px-5 py-4 text-slate-600 sm:pr-6">
                                    {{ $payment->failure_message ?: 'Sin mensaje de error registrado.' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">
                                    No hay pagos fallidos registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($failedPayments->hasPages())
                <div class="border-t border-slate-200 px-5 py-4 sm:px-6">
                    {{ $failedPayments->withQueryString()->links() }}
                </div>
            @endif
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-slate-950">Suscripciones past due</h2>
                        <p class="text-sm text-slate-500">Suscripciones que requieren seguimiento de recuperación de pago.</p>
                    </div>
                    <span class="text-sm font-medium text-slate-500">
                        {{ number_format($pastDueSubscriptions->total()) }} registros
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-5 py-3 sm:px-6">Tenant</th>
                            <th class="px-5 py-3">Billing</th>
                            <th class="px-5 py-3">Past due desde</th>
                            <th class="px-5 py-3">Gracia</th>
                            <th class="px-5 py-3">Próximo reintento</th>
                            <th class="px-5 py-3 sm:pr-6">Reintentos</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($pastDueSubscriptions as $subscription)
                            <tr class="align-top">
                                <td class="px-5 py-4 sm:px-6">
                                    @if ($subscription->tenant)
                                        <a href="{{ route('internal.tenants.show', $subscription->tenant) }}"
                                           class="font-medium text-indigo-700 hover:text-indigo-900 hover:underline">
                                            {{ $subscription->tenant->name }}
                                        </a>
                                        <div class="mt-1 text-xs text-slate-500">{{ $subscription->tenant->slug }}</div>
                                    @else
                                        <span class="font-medium text-slate-500">Tenant no disponible</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-700">
                                    <div class="font-medium text-slate-900">
                                        {{ $subscription->billing_currency ?? 'MXN' }}
                                        {{ number_format(($subscription->billing_amount ?? 0) / 100, 2) }}
                                    </div>
                                    <div class="mt-1 text-xs text-slate-500">{{ $subscription->billing_cycle }}</div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-600">
                                    {{ $subscription->past_due_since?->format('d/m/Y H:i') ?? '—' }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    @if ($subscription->isInGracePeriod())
                                        <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-200">
                                            Hasta {{ $subscription->grace_ends_at->format('d/m/Y H:i') }}
                                        </span>
                                    @elseif ($subscription->gracePeriodHasExpired())
                                        <span class="inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-200">
                                            Vencida {{ $subscription->grace_ends_at->format('d/m/Y H:i') }}
                                        </span>
                                    @elseif ($subscription->grace_ends_at)
                                        <span class="text-slate-600">{{ $subscription->grace_ends_at->format('d/m/Y H:i') }}</span>
                                    @else
                                        <span class="text-slate-400">Sin fecha</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    @if ($subscription->next_retry_at)
                                        @if ($subscription->retryIsDue())
                                            <span class="font-medium text-rose-700">
                                                Vencido · {{ $subscription->next_retry_at->format('d/m/Y H:i') }}
                                            </span>
                                        @else
                                            <span class="text-slate-600">{{ $subscription->next_retry_at->format('d/m/Y H:i') }}</span>
                                        @endif
                                    @else
                                        <span class="text-slate-400">Sin reintento</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-700 sm:pr-6">
                                    {{ $subscription->retry_count ?? 0 }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">
                                    No hay suscripciones past due registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($pastDueSubscriptions->hasPages())
                <div class="border-t border-slate-200 px-5 py-4 sm:px-6">
                    {{ $pastDueSubscriptions->withQueryString()->links() }}
                </div>
            @endif
        </section>
    </div>
</x-layouts.internal>
