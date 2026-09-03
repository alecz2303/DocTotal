<x-layouts.internal>
    @php
        $tenant = $detail['tenant'];
        $subscription = $detail['subscription'];
        $users = $detail['users'];
        $payments = $detail['payments'];

        $trialDuration = $tenant->trialDurationInDays();
        $trialRemaining = $tenant->trialDaysRemaining();
        $trialExpired = $tenant->trialDaysExpired();
    @endphp

    <div class="space-y-8">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <a href="{{ route('internal.tenants.index') }}"
                   class="text-sm font-medium text-indigo-600 hover:text-indigo-700">
                    ← Volver a tenants
                </a>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <h1 class="text-3xl font-semibold tracking-tight text-slate-950">
                        {{ $tenant->name }}
                    </h1>

                    @if ($tenant->suspended_at || $tenant->status === 'suspended')
                        <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">
                            Suspendido
                        </span>
                    @elseif ($tenant->trialHasExpired())
                        <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
                            Trial vencido
                        </span>
                    @elseif ($tenant->isOnTrial())
                        <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                            Trial activo
                        </span>
                    @else
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                            {{ ucfirst($tenant->status) }}
                        </span>
                    @endif
                </div>

                <p class="mt-2 text-sm text-slate-500">
                    {{ $tenant->slug }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                    Alta del tenant
                </p>
                <p class="mt-1 font-semibold text-slate-900">
                    {{ $tenant->created_at?->format('d/m/Y H:i') ?? '—' }}
                </p>
            </div>
        </div>

        @if ($tenant->status === 'trial' || $tenant->trial_started_at || $tenant->trial_ends_at)
            <section class="rounded-2xl border p-5 shadow-sm
                {{ $tenant->trialHasExpired()
                    ? 'border-rose-200 bg-rose-50'
                    : 'border-indigo-200 bg-indigo-50' }}">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider
                            {{ $tenant->trialHasExpired() ? 'text-rose-700' : 'text-indigo-700' }}">
                            Periodo de prueba
                        </p>

                        <h2 class="mt-1 text-xl font-semibold
                            {{ $tenant->trialHasExpired() ? 'text-rose-950' : 'text-indigo-950' }}">
                            @if ($tenant->trialHasExpired())
                                Trial vencido
                            @elseif ($tenant->isOnTrial())
                                Trial activo
                            @else
                                Información del trial
                            @endif
                        </h2>

                        <div class="mt-3 flex flex-wrap gap-x-6 gap-y-2 text-sm
                            {{ $tenant->trialHasExpired() ? 'text-rose-900' : 'text-indigo-900' }}">
                            <span>
                                <strong>Inicio:</strong>
                                {{ $tenant->trial_started_at?->format('d/m/Y') ?? 'No registrado' }}
                            </span>

                            <span>
                                <strong>Fin:</strong>
                                {{ $tenant->trial_ends_at?->format('d/m/Y') ?? 'No registrado' }}
                            </span>

                            <span>
                                <strong>Duración:</strong>
                                {{ $trialDuration !== null ? $trialDuration.' días' : 'No disponible' }}
                            </span>
                        </div>
                    </div>

                    @if ($tenant->isOnTrial())
                        <div class="rounded-xl bg-white/70 px-5 py-4 text-center shadow-sm">
                            <p class="text-3xl font-semibold text-indigo-800">
                                {{ $trialRemaining }}
                            </p>
                            <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-indigo-600">
                                días restantes
                            </p>
                        </div>
                    @elseif ($tenant->trialHasExpired())
                        <div class="rounded-xl bg-white/70 px-5 py-4 text-center shadow-sm">
                            <p class="text-3xl font-semibold text-rose-800">
                                {{ $trialExpired }}
                            </p>
                            <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-rose-600">
                                días desde el vencimiento
                            </p>
                        </div>
                    @endif
                </div>
            </section>
        @endif

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Usuarios</p>
                <p class="mt-2 text-3xl font-semibold text-slate-950">
                    {{ number_format($users->count()) }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Onboarding</p>

                @if ($tenant->onboarding_completed_at)
                    <p class="mt-2 font-semibold text-emerald-700">
                        Completado
                    </p>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ $tenant->onboarding_completed_at->format('d/m/Y H:i') }}
                    </p>
                @else
                    <p class="mt-2 font-semibold text-amber-700">
                        Pendiente
                    </p>
                @endif
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Suscripción</p>

                <p class="mt-2 font-semibold text-slate-950">
                    {{ $subscription?->status ?? 'Sin suscripción' }}
                </p>

                @if ($subscription?->current_period_ends_at)
                    <p class="mt-1 text-xs text-slate-500">
                        Periodo hasta {{ $subscription->current_period_ends_at->format('d/m/Y') }}
                    </p>
                @endif
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Acceso al servicio</p>

                @if ($tenant->hasAccessToService())
                    <p class="mt-2 font-semibold text-emerald-700">
                        Disponible
                    </p>
                @else
                    <p class="mt-2 font-semibold text-rose-700">
                        Sin acceso
                    </p>
                @endif
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h2 class="font-semibold text-slate-950">Usuarios</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Usuarios asociados a esta cuenta.
                    </p>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <div class="flex flex-col gap-2 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="font-medium text-slate-950">
                                    {{ $user->name }}
                                </div>

                                <div class="mt-1 text-sm text-slate-500">
                                    {{ $user->email }}
                                </div>
                            </div>

                            <div class="sm:text-right">
                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                    {{ $user->role }}
                                </span>

                                <p class="mt-1 text-xs text-slate-400">
                                    Último acceso:
                                    {{ $user->last_login_at?->format('d/m/Y H:i') ?? 'Sin registro' }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-10 text-center text-sm text-slate-500">
                            No hay usuarios registrados.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h2 class="font-semibold text-slate-950">Últimos pagos</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Actividad de cobro más reciente del tenant.
                    </p>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse ($payments as $payment)
                        <div class="flex items-center justify-between gap-4 px-6 py-4">
                            <div>
                                <div class="font-semibold text-slate-950">
                                    {{ number_format($payment->amount / 100, 2) }}
                                    {{ $payment->currency }}
                                </div>

                                <div class="mt-1 text-xs text-slate-500">
                                    {{ $payment->attempted_at?->format('d/m/Y H:i') ?? 'Sin fecha' }}
                                </div>

                                @if ($payment->failure_code)
                                    <div class="mt-1 text-xs text-rose-600">
                                        {{ $payment->failure_code }}
                                    </div>
                                @endif
                            </div>

                            @if ($payment->status === 'succeeded')
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                    Exitoso
                                </span>
                            @elseif ($payment->status === 'failed')
                                <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">
                                    Fallido
                                </span>
                            @elseif ($payment->status === 'pending')
                                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                    Pendiente
                                </span>
                            @else
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                    {{ $payment->status }}
                                </span>
                            @endif
                        </div>
                    @empty
                        <div class="px-6 py-10 text-center text-sm text-slate-500">
                            No hay pagos registrados.
                        </div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
</x-layouts.internal>
