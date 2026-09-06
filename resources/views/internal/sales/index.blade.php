<x-layouts.internal>
    <div class="space-y-8">
        <div>
            <p class="text-sm font-semibold text-indigo-600">Adquisición comercial</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Vendedores, códigos y comisiones</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                Administra vendedores, códigos promocionales, atribuciones y comisiones generadas por pagos efectivamente cobrados.
            </p>
        </div>

        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        @php
            $cards = [
                ['label' => 'Registros atribuidos', 'value' => number_format($metrics['registrations'])],
                ['label' => 'Conversiones a pago', 'value' => number_format($metrics['paid_conversions'])],
                ['label' => 'Ingreso atribuido', 'value' => '$'.number_format($metrics['attributed_revenue'] / 100, 2)],
                ['label' => 'Comisión devengada', 'value' => '$'.number_format($metrics['commission_accrued'] / 100, 2)],
                ['label' => 'Comisión pagada', 'value' => '$'.number_format($metrics['commission_paid'] / 100, 2)],
            ];
        @endphp
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ($cards as $card)
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $card['label'] }}</p>
                    <p class="mt-2 text-2xl font-bold text-slate-950">{{ $card['value'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-950">Nuevo vendedor o promotor</h2>
                <form method="POST" action="{{ route('internal.sales.partners.store') }}" class="mt-5 grid gap-4 sm:grid-cols-2">
                    @csrf
                    <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-medium">Nombre</label><input name="name" value="{{ old('name') }}" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"></div>
                    <div><label class="mb-1.5 block text-sm font-medium">Correo</label><input name="email" type="email" value="{{ old('email') }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"></div>
                    <div><label class="mb-1.5 block text-sm font-medium">Teléfono</label><input name="phone" value="{{ old('phone') }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"></div>
                    <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-medium">Notas</label><textarea name="notes" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">{{ old('notes') }}</textarea></div>
                    <div class="sm:col-span-2"><button class="rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700">Crear vendedor</button></div>
                </form>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-950">Nuevo código promocional</h2>
                <form method="POST" action="{{ route('internal.sales.promo-codes.store') }}" class="mt-5 grid gap-4 sm:grid-cols-2">
                    @csrf
                    <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-medium">Vendedor</label><select name="sales_partner_id" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"><option value="">Selecciona un vendedor</option>@foreach ($partners->where('active', true) as $partner)<option value="{{ $partner->id }}">{{ $partner->name }}</option>@endforeach</select></div>
                    <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-medium">Código</label><input name="code" value="{{ old('code') }}" placeholder="VENDE20" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm uppercase"></div>
                    <div><label class="mb-1.5 block text-sm font-medium">Descuento al médico %</label><input name="doctor_discount_percent" type="number" min="0" max="100" step="0.01" value="{{ old('doctor_discount_percent', 0) }}" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"></div>
                    <div><label class="mb-1.5 block text-sm font-medium">Comisión vendedor %</label><input name="commission_percent" type="number" min="0" max="100" step="0.01" value="{{ old('commission_percent', 0) }}" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"></div>
                    <div><label class="mb-1.5 block text-sm font-medium">Vigente desde</label><input name="starts_at" type="datetime-local" value="{{ old('starts_at') }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"></div>
                    <div><label class="mb-1.5 block text-sm font-medium">Vigente hasta</label><input name="ends_at" type="datetime-local" value="{{ old('ends_at') }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"></div>
                    <div class="sm:col-span-2"><p class="mb-4 text-xs leading-5 text-slate-500">La comisión se limita al primer pago exitoso. Los snapshots históricos no cambian al editar el código.</p><button class="rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700">Crear código</button></div>
                </form>
            </section>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-950">Vendedores</h2>
            <div class="mt-5 space-y-4">
                @forelse ($partners as $partner)
                    <div class="rounded-xl border border-slate-200 p-4">
                        <form method="POST" action="{{ route('internal.sales.partners.update', $partner) }}" class="grid gap-3 md:grid-cols-4">
                            @csrf @method('PUT')
                            <input name="name" value="{{ $partner->name }}" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <input name="email" type="email" value="{{ $partner->email }}" placeholder="Correo" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <input name="phone" value="{{ $partner->phone }}" placeholder="Teléfono" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <button class="rounded-lg border border-indigo-300 px-3 py-2 text-sm font-semibold text-indigo-700">Guardar cambios</button>
                        </form>
                        <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                            <span>{{ $partner->promo_codes_count }} códigos · {{ $partner->commissions_count }} comisiones</span>
                            <form method="POST" action="{{ route('internal.sales.partners.toggle', $partner) }}">@csrf @method('PUT')<button class="font-semibold {{ $partner->active ? 'text-rose-600' : 'text-emerald-700' }}">{{ $partner->active ? 'Desactivar' : 'Activar' }}</button></form>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Aún no hay vendedores.</p>
                @endforelse
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-5"><h2 class="text-lg font-semibold text-slate-950">Códigos promocionales</h2></div>
            <div class="space-y-4 p-6">
                @forelse ($promoCodes as $promoCode)
                    <div class="rounded-xl border border-slate-200 p-4">
                        <form method="POST" action="{{ route('internal.sales.promo-codes.update', $promoCode) }}" class="grid gap-3 md:grid-cols-5">
                            @csrf @method('PUT')
                            <select name="sales_partner_id" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm">@foreach ($partners as $partner)<option value="{{ $partner->id }}" @selected($partner->id === $promoCode->sales_partner_id)>{{ $partner->name }}</option>@endforeach</select>
                            <input name="code" value="{{ $promoCode->code }}" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm uppercase">
                            <input name="doctor_discount_percent" type="number" min="0" max="100" step="0.01" value="{{ $promoCode->doctor_discount_percent }}" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <input name="commission_percent" type="number" min="0" max="100" step="0.01" value="{{ $promoCode->commission_percent }}" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <button class="rounded-lg border border-indigo-300 px-3 py-2 text-sm font-semibold text-indigo-700">Guardar cambios</button>
                            <input type="hidden" name="starts_at" value="{{ optional($promoCode->starts_at)->format('Y-m-d H:i:s') }}">
                            <input type="hidden" name="ends_at" value="{{ optional($promoCode->ends_at)->format('Y-m-d H:i:s') }}">
                        </form>
                        <div class="mt-3 flex items-center justify-between text-xs text-slate-500"><span>{{ $promoCode->attributions_count }} registros · {{ $promoCode->active ? 'Activo' : 'Inactivo' }}</span><form method="POST" action="{{ route('internal.sales.promo-codes.toggle', $promoCode) }}">@csrf @method('PUT')<button class="font-semibold {{ $promoCode->active ? 'text-rose-600' : 'text-emerald-700' }}">{{ $promoCode->active ? 'Desactivar' : 'Activar' }}</button></form></div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Aún no hay códigos promocionales.</p>
                @endforelse
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-5"><h2 class="text-lg font-semibold text-slate-950">Registros atribuidos</h2></div>
            <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-6 py-3">Tenant</th><th class="px-6 py-3">Código</th><th class="px-6 py-3">Vendedor</th><th class="px-6 py-3">Fecha</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse ($attributions as $attribution)<tr><td class="px-6 py-4">{{ $attribution->tenant->name }}</td><td class="px-6 py-4 font-semibold">{{ $attribution->code_snapshot }}</td><td class="px-6 py-4">{{ $attribution->salesPartner->name }}</td><td class="px-6 py-4">{{ $attribution->attributed_at?->format('d/m/Y H:i') }}</td></tr>@empty<tr><td colspan="4" class="px-6 py-8 text-center text-slate-500">Aún no hay registros atribuidos.</td></tr>@endforelse</tbody></table></div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-5"><h2 class="text-lg font-semibold text-slate-950">Comisiones</h2></div>
            <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-6 py-3">Vendedor</th><th class="px-6 py-3">Código</th><th class="px-6 py-3">Tenant</th><th class="px-6 py-3">Base</th><th class="px-6 py-3">%</th><th class="px-6 py-3">Comisión</th><th class="px-6 py-3">Estado</th><th class="px-6 py-3"></th></tr></thead><tbody class="divide-y divide-slate-100">@forelse ($commissions as $commission)<tr><td class="px-6 py-4">{{ $commission->salesPartner->name }}</td><td class="px-6 py-4 font-semibold">{{ $commission->promoCode->code }}</td><td class="px-6 py-4">{{ $commission->tenant->name }}</td><td class="px-6 py-4">${{ number_format($commission->base_amount / 100, 2) }} {{ $commission->currency }}</td><td class="px-6 py-4">{{ number_format((float) $commission->commission_percent, 2) }}%</td><td class="px-6 py-4 font-semibold">${{ number_format($commission->commission_amount / 100, 2) }}</td><td class="px-6 py-4">{{ ucfirst($commission->status) }}</td><td class="px-6 py-4 text-right">@if ($commission->status === \App\Models\SalesCommission::STATUS_ACCRUED)<form method="POST" action="{{ route('internal.sales.commissions.paid', $commission) }}">@csrf @method('PUT')<button class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold">Marcar pagada</button></form>@endif</td></tr>@empty<tr><td colspan="8" class="px-6 py-8 text-center text-slate-500">Aún no hay comisiones devengadas.</td></tr>@endforelse</tbody></table></div>
        </section>
    </div>
</x-layouts.internal>
