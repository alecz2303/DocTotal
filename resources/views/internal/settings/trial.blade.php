<x-layouts.internal>
    <div class="mx-auto max-w-4xl space-y-6">
        <div>
            <p class="text-sm font-semibold text-indigo-600">Configuración comercial</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Periodo de prueba</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                Define cuántos días de prueba recibirán los nuevos médicos al crear su cuenta. Los tenants existentes conservan sus fechas actuales.
            </p>
        </div>

        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <form method="POST" action="{{ route('internal.settings.trial.update') }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <label for="trial_days" class="block text-sm font-semibold text-slate-800">
                        Días de prueba para nuevos registros
                    </label>
                    <p class="mt-1 text-xs leading-5 text-slate-500">
                        Valor permitido: {{ $minDays }} a {{ $maxDays }} días.
                    </p>

                    <div class="mt-3 max-w-xs">
                        <input
                            id="trial_days"
                            name="trial_days"
                            type="number"
                            min="{{ $minDays }}"
                            max="{{ $maxDays }}"
                            value="{{ old('trial_days', $trialDays) }}"
                            required
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                    </div>

                    @error('trial_days')
                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                    <p><span class="font-semibold text-slate-800">Valor efectivo actual:</span> {{ $trialDays }} días.</p>
                    <p class="mt-1 text-xs leading-5 text-slate-500">
                        Si no existe un valor persistido, DocTotal utiliza el fallback configurado en el entorno.
                    </p>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                        Guardar configuración
                    </button>
                </div>
            </form>
        </section>
    </div>
</x-layouts.internal>
