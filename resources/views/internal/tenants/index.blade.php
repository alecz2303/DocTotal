<x-layouts.internal>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-indigo-600">
                    Administración SaaS
                </p>

                <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-950">
                    Tenants
                </h1>

                <p class="mt-2 text-sm text-slate-600">
                    Estado efectivo de acceso de las cuentas registradas en DocTotal.
                </p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                    Registros
                </p>
                <p class="mt-1 text-xl font-semibold text-slate-950">
                    {{ number_format($tenants->total()) }}
                </p>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            @foreach (['Tenant', 'Acceso efectivo', 'Usuarios', 'Onboarding', 'Periodo de trial', 'Alta'] as $heading)
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    {{ $heading }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($tenants as $tenant)
                            @php
                                $effectiveStatus = $tenant->effectiveServiceStatus();
                                $trialDuration = $tenant->trialDurationInDays();
                                $trialRemaining = $tenant->trialDaysRemaining();
                                $trialExpired = $tenant->trialDaysExpired();

                                $statusClasses = match ($effectiveStatus) {
                                    'active' => 'bg-emerald-50 text-emerald-700',
                                    'trial_active' => 'bg-indigo-50 text-indigo-700',
                                    'grace_period' => 'bg-amber-50 text-amber-700',
                                    'trial_expired' => 'bg-rose-50 text-rose-700',
                                    'suspended' => 'bg-rose-50 text-rose-700',
                                    'cancelled' => 'bg-slate-100 text-slate-600',
                                    default => 'bg-rose-50 text-rose-700',
                                };
                            @endphp

                            <tr class="transition hover:bg-slate-50/80">
                                <td class="px-6 py-5">
                                    <a href="{{ route('internal.tenants.show', $tenant) }}"
                                       class="font-semibold text-slate-950 hover:text-indigo-700">
                                        {{ $tenant->name }}
                                    </a>

                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ $tenant->slug }}
                                    </div>

                                    <div class="mt-1 text-[11px] text-slate-400">
                                        Persistido: {{ $tenant->status }}
                                    </div>
                                </td>

                                <td class="px-6 py-5">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses }}">
                                        {{ $tenant->effectiveServiceStatusLabel() }}
                                    </span>
                                </td>

                                <td class="px-6 py-5 text-sm font-medium text-slate-700">
                                    {{ number_format($tenant->users_count) }}
                                </td>

                                <td class="px-6 py-5 text-sm">
                                    @if ($tenant->onboarding_completed_at)
                                        <span class="inline-flex items-center gap-1.5 font-medium text-emerald-700">
                                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                            Completado
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 font-medium text-amber-700">
                                            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                                            Pendiente
                                        </span>
                                    @endif
                                </td>

                                <td class="min-w-64 px-6 py-5 text-sm">
                                    @if ($tenant->trial_started_at && $tenant->trial_ends_at)
                                        <div class="font-medium text-slate-800">
                                            {{ $tenant->trial_started_at->format('d/m/Y') }}
                                            <span class="text-slate-400">→</span>
                                            {{ $tenant->trial_ends_at->format('d/m/Y') }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ $trialDuration }} días de trial
                                        </div>

                                        @if ($tenant->isOnTrial())
                                            <div class="mt-1 text-xs font-semibold text-indigo-700">
                                                {{ $trialRemaining }} días restantes
                                            </div>
                                        @elseif ($tenant->trialHasExpired())
                                            <div class="mt-1 text-xs font-semibold text-rose-600">
                                                Venció hace {{ $trialExpired }} días
                                            </div>
                                        @endif
                                    @elseif ($tenant->trial_ends_at)
                                        <div class="font-medium text-slate-800">
                                            Finaliza {{ $tenant->trial_ends_at->format('d/m/Y') }}
                                        </div>
                                        <div class="mt-1 text-xs text-slate-500">
                                            Inicio no registrado
                                        </div>
                                    @else
                                        <span class="text-slate-400">
                                            Sin periodo registrado
                                        </span>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-6 py-5 text-sm text-slate-500">
                                    {{ $tenant->created_at?->format('d/m/Y') ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-500">
                                    No hay tenants registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($tenants->hasPages())
                <div class="border-t border-slate-200 px-6 py-4">
                    {{ $tenants->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.internal>
