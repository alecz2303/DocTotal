<x-layouts.internal>
    <div class="space-y-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold text-indigo-600">Adquisición comercial</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Ventas y comisiones</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                    Consulta cuánto vende cada vendedor y código, cuánto se ha pagado y cuánto queda pendiente por liquidar.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <details class="group relative">
                    <summary class="cursor-pointer list-none rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm">+ Nuevo vendedor</summary>
                    <div class="absolute right-0 z-20 mt-2 w-[min(92vw,32rem)] rounded-2xl border border-slate-200 bg-white p-5 shadow-xl">
                        <form method="POST" action="{{ route('internal.sales.partners.store') }}" class="grid gap-4 sm:grid-cols-2">
                            @csrf
                            <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-medium">Nombre</label><input name="name" value="{{ old('name') }}" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"></div>
                            <div><label class="mb-1.5 block text-sm font-medium">Correo</label><input name="email" type="email" value="{{ old('email') }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"></div>
                            <div><label class="mb-1.5 block text-sm font-medium">Teléfono</label><input name="phone" value="{{ old('phone') }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"></div>
                            <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-medium">Notas</label><textarea name="notes" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">{{ old('notes') }}</textarea></div>
                            <div class="sm:col-span-2"><button class="rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white">Crear vendedor</button></div>
                        </form>
                    </div>
                </details>
                <details class="group relative">
                    <summary class="cursor-pointer list-none rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm">+ Nuevo código</summary>
                    <div class="absolute right-0 z-20 mt-2 w-[min(92vw,38rem)] rounded-2xl border border-slate-200 bg-white p-5 shadow-xl">
                        <form method="POST" action="{{ route('internal.sales.promo-codes.store') }}" class="grid gap-4 sm:grid-cols-2">
                            @csrf
                            <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-medium">Vendedor</label><select name="sales_partner_id" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"><option value="">Selecciona un vendedor</option>@foreach ($partners->where('active', true) as $partner)<option value="{{ $partner->id }}">{{ $partner->name }}</option>@endforeach</select></div>
                            <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-medium">Código</label><input name="code" value="{{ old('code') }}" placeholder="VENDE20" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm uppercase"></div>
                            <div><label class="mb-1.5 block text-sm font-medium">Descuento al médico %</label><input name="doctor_discount_percent" type="number" min="0" max="100" step="0.01" value="{{ old('doctor_discount_percent', 0) }}" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"></div>
                            <div><label class="mb-1.5 block text-sm font-medium">Comisión vendedor %</label><input name="commission_percent" type="number" min="0" max="100" step="0.01" value="{{ old('commission_percent', 0) }}" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"></div>
                            <div><label class="mb-1.5 block text-sm font-medium">Vigente desde</label><input name="starts_at" type="datetime-local" value="{{ old('starts_at') }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"></div>
                            <div><label class="mb-1.5 block text-sm font-medium">Vigente hasta</label><input name="ends_at" type="datetime-local" value="{{ old('ends_at') }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"></div>
                            <div class="sm:col-span-2"><p class="mb-4 text-xs text-slate-500">La comisión se limita al primer pago exitoso y conserva el porcentaje histórico.</p><button class="rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white">Crear código</button></div>
                        </form>
                    </div>
                </details>
            </div>
        </div>

        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        @php
            $money = fn (int $amount) => '$'.number_format($amount / 100, 2);
            $cards = [
                ['label' => 'Ventas cobradas', 'value' => $money($metrics['attributed_revenue']), 'help' => $metrics['paid_conversions'].' clientes que pagaron'],
                ['label' => 'Descuentos otorgados', 'value' => $money($metrics['discounts']), 'help' => 'En el primer pago atribuido'],
                ['label' => 'Comisión generada', 'value' => $money($metrics['commission_generated']), 'help' => 'Devengada + pagada'],
                ['label' => 'Comisión pagada', 'value' => $money($metrics['commission_paid']), 'help' => 'Ya liquidada'],
                ['label' => 'Pendiente por pagar', 'value' => $money($metrics['commission_pending']), 'help' => 'Saldo actual a vendedores', 'accent' => true],
            ];
        @endphp

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ($cards as $card)
                <div class="rounded-2xl border {{ !empty($card['accent']) ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-white' }} p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide {{ !empty($card['accent']) ? 'text-amber-700' : 'text-slate-400' }}">{{ $card['label'] }}</p>
                    <p class="mt-2 text-2xl font-bold text-slate-950">{{ $card['value'] }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $card['help'] }}</p>
                </div>
            @endforeach
        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-5">
                <h2 class="text-lg font-semibold text-slate-950">Rendimiento por vendedor</h2>
                <p class="mt-1 text-sm text-slate-500">Ventas = primer pago exitoso de cada registro atribuido. El saldo pendiente proviene de comisiones devengadas no pagadas.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-6 py-3">Vendedor</th><th class="px-6 py-3">Registros</th><th class="px-6 py-3">Pagaron</th><th class="px-6 py-3">Conversión</th><th class="px-6 py-3">Ventas</th><th class="px-6 py-3">Comisión</th><th class="px-6 py-3">Pagado</th><th class="px-6 py-3">Por pagar</th><th class="px-6 py-3"></th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($partners as $partner)
                            @php($stats = $partnerStats[$partner->id])
                            <tr class="align-top">
                                <td class="px-6 py-4"><div class="font-semibold text-slate-900">{{ $partner->name }}</div><div class="mt-1 text-xs text-slate-500">{{ $partner->promo_codes_count }} códigos · {{ $partner->active ? 'Activo' : 'Inactivo' }}</div></td>
                                <td class="px-6 py-4">{{ $stats['registrations'] }}</td>
                                <td class="px-6 py-4">{{ $stats['paid_conversions'] }}</td>
                                <td class="px-6 py-4">{{ number_format($stats['conversion_rate'], 1) }}%</td>
                                <td class="px-6 py-4 font-semibold">{{ $money($stats['revenue']) }}</td>
                                <td class="px-6 py-4">{{ $money($stats['commission_generated']) }}</td>
                                <td class="px-6 py-4 text-emerald-700">{{ $money($stats['commission_paid']) }}</td>
                                <td class="px-6 py-4 font-bold {{ $stats['commission_pending'] > 0 ? 'text-amber-700' : 'text-slate-600' }}">{{ $money($stats['commission_pending']) }}</td>
                                <td class="px-6 py-4 text-right">
                                    <details class="text-left">
                                        <summary class="cursor-pointer text-xs font-semibold text-indigo-700">Ver estado de cuenta</summary>
                                        <div class="mt-3 min-w-[36rem] rounded-xl border border-slate-200 bg-slate-50 p-4">
                                            <div class="grid gap-3 sm:grid-cols-4"><div><p class="text-xs text-slate-500">Ventas</p><p class="font-semibold">{{ $money($stats['revenue']) }}</p></div><div><p class="text-xs text-slate-500">Comisión</p><p class="font-semibold">{{ $money($stats['commission_generated']) }}</p></div><div><p class="text-xs text-slate-500">Pagado</p><p class="font-semibold">{{ $money($stats['commission_paid']) }}</p></div><div><p class="text-xs text-slate-500">Pendiente</p><p class="font-bold text-amber-700">{{ $money($stats['commission_pending']) }}</p></div></div>
                                            @if ($stats['commission_pending'] > 0)
                                                <form method="POST" action="{{ route('internal.sales.partners.commissions.paid', $partner) }}" class="mt-4">@csrf @method('PUT')<button class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white">Marcar todo lo pendiente como pagado</button></form>
                                            @endif
                                            <div class="mt-4 overflow-x-auto"><table class="min-w-full text-xs"><thead class="text-left text-slate-500"><tr><th class="py-2 pr-4">Fecha</th><th class="py-2 pr-4">Consultorio</th><th class="py-2 pr-4">Código</th><th class="py-2 pr-4">Base</th><th class="py-2 pr-4">%</th><th class="py-2 pr-4">Comisión</th><th class="py-2">Estado</th></tr></thead><tbody>@forelse ($stats['commissions'] as $commission)<tr class="border-t border-slate-200"><td class="py-2 pr-4">{{ $commission->accrued_at?->format('d/m/Y') }}</td><td class="py-2 pr-4">{{ $commission->tenant->name }}</td><td class="py-2 pr-4 font-semibold">{{ $commission->promoCode->code }}</td><td class="py-2 pr-4">{{ $money($commission->base_amount) }}</td><td class="py-2 pr-4">{{ number_format((float) $commission->commission_percent, 2) }}%</td><td class="py-2 pr-4 font-semibold">{{ $money($commission->commission_amount) }}</td><td class="py-2">{{ $commission->status === 'accrued' ? 'Pendiente' : ($commission->status === 'paid' ? 'Pagada' : 'Revertida') }}@if($commission->paid_at)<div class="text-[10px] text-slate-400">{{ $commission->paid_at->format('d/m/Y H:i') }}</div>@endif</td></tr>@empty<tr><td colspan="7" class="py-4 text-slate-500">Sin comisiones.</td></tr>@endforelse</tbody></table></div>
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-6 py-8 text-center text-slate-500">Aún no hay vendedores.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-5"><h2 class="text-lg font-semibold text-slate-950">Rendimiento por código</h2><p class="mt-1 text-sm text-slate-500">Compara registros, conversiones, ventas y comisión generada por cada código promocional.</p></div>
            <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-6 py-3">Código</th><th class="px-6 py-3">Vendedor</th><th class="px-6 py-3">Desc.</th><th class="px-6 py-3">Com.</th><th class="px-6 py-3">Registros</th><th class="px-6 py-3">Pagaron</th><th class="px-6 py-3">Conversión</th><th class="px-6 py-3">Ventas</th><th class="px-6 py-3">Comisión</th><th class="px-6 py-3">Estado</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse ($promoCodes as $promoCode) @php($stats = $codeStats[$promoCode->id]) <tr><td class="px-6 py-4 font-bold text-indigo-700">{{ $promoCode->code }}</td><td class="px-6 py-4">{{ $promoCode->salesPartner->name }}</td><td class="px-6 py-4">{{ number_format((float) $promoCode->doctor_discount_percent, 2) }}%</td><td class="px-6 py-4">{{ number_format((float) $promoCode->commission_percent, 2) }}%</td><td class="px-6 py-4">{{ $stats['registrations'] }}</td><td class="px-6 py-4">{{ $stats['paid_conversions'] }}</td><td class="px-6 py-4">{{ number_format($stats['conversion_rate'], 1) }}%</td><td class="px-6 py-4 font-semibold">{{ $money($stats['revenue']) }}</td><td class="px-6 py-4">{{ $money($stats['commission_generated']) }}</td><td class="px-6 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $promoCode->active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $promoCode->active ? 'Activo' : 'Inactivo' }}</span></td></tr>@empty<tr><td colspan="10" class="px-6 py-8 text-center text-slate-500">Aún no hay códigos promocionales.</td></tr>@endforelse</tbody></table></div>
        </section>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-950">Administrar vendedores</h2>
                <div class="mt-5 space-y-4">@forelse ($partners as $partner)<div class="rounded-xl border border-slate-200 p-4"><form method="POST" action="{{ route('internal.sales.partners.update', $partner) }}" class="grid gap-3 sm:grid-cols-2">@csrf @method('PUT')<input name="name" value="{{ $partner->name }}" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><input name="email" type="email" value="{{ $partner->email }}" placeholder="Correo" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><input name="phone" value="{{ $partner->phone }}" placeholder="Teléfono" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><button class="rounded-lg border border-indigo-300 px-3 py-2 text-sm font-semibold text-indigo-700">Guardar cambios</button></form><div class="mt-3 text-right"><form method="POST" action="{{ route('internal.sales.partners.toggle', $partner) }}">@csrf @method('PUT')<button class="text-xs font-semibold {{ $partner->active ? 'text-rose-600' : 'text-emerald-700' }}">{{ $partner->active ? 'Desactivar' : 'Activar' }}</button></form></div></div>@empty<p class="text-sm text-slate-500">Aún no hay vendedores.</p>@endforelse</div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-950">Administrar códigos</h2>
                <div class="mt-5 space-y-4">@forelse ($promoCodes as $promoCode)<div class="rounded-xl border border-slate-200 p-4"><form method="POST" action="{{ route('internal.sales.promo-codes.update', $promoCode) }}" class="grid gap-3 sm:grid-cols-2">@csrf @method('PUT')<select name="sales_partner_id" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm">@foreach ($partners as $partner)<option value="{{ $partner->id }}" @selected($partner->id === $promoCode->sales_partner_id)>{{ $partner->name }}</option>@endforeach</select><input name="code" value="{{ $promoCode->code }}" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm uppercase"><input name="doctor_discount_percent" type="number" min="0" max="100" step="0.01" value="{{ $promoCode->doctor_discount_percent }}" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><input name="commission_percent" type="number" min="0" max="100" step="0.01" value="{{ $promoCode->commission_percent }}" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><input type="hidden" name="starts_at" value="{{ optional($promoCode->starts_at)->format('Y-m-d H:i:s') }}"><input type="hidden" name="ends_at" value="{{ optional($promoCode->ends_at)->format('Y-m-d H:i:s') }}"><button class="rounded-lg border border-indigo-300 px-3 py-2 text-sm font-semibold text-indigo-700 sm:col-span-2">Guardar cambios</button></form><div class="mt-3 text-right"><form method="POST" action="{{ route('internal.sales.promo-codes.toggle', $promoCode) }}">@csrf @method('PUT')<button class="text-xs font-semibold {{ $promoCode->active ? 'text-rose-600' : 'text-emerald-700' }}">{{ $promoCode->active ? 'Desactivar' : 'Activar' }}</button></form></div></div>@empty<p class="text-sm text-slate-500">Aún no hay códigos.</p>@endforelse</div>
            </section>
        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-5"><h2 class="text-lg font-semibold text-slate-950">Actividad reciente de comisiones</h2></div>
            <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-6 py-3">Vendedor</th><th class="px-6 py-3">Código</th><th class="px-6 py-3">Consultorio</th><th class="px-6 py-3">Base</th><th class="px-6 py-3">Comisión</th><th class="px-6 py-3">Estado</th><th class="px-6 py-3"></th></tr></thead><tbody class="divide-y divide-slate-100">@forelse ($commissions as $commission)<tr><td class="px-6 py-4">{{ $commission->salesPartner->name }}</td><td class="px-6 py-4 font-semibold">{{ $commission->promoCode->code }}</td><td class="px-6 py-4">{{ $commission->tenant->name }}</td><td class="px-6 py-4">{{ $money($commission->base_amount) }}</td><td class="px-6 py-4 font-semibold">{{ $money($commission->commission_amount) }}</td><td class="px-6 py-4">{{ $commission->status === 'accrued' ? 'Pendiente' : ($commission->status === 'paid' ? 'Pagada' : 'Revertida') }}</td><td class="px-6 py-4 text-right">@if ($commission->status === \App\Models\SalesCommission::STATUS_ACCRUED)<form method="POST" action="{{ route('internal.sales.commissions.paid', $commission) }}">@csrf @method('PUT')<button class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold">Marcar pagada</button></form>@endif</td></tr>@empty<tr><td colspan="7" class="px-6 py-8 text-center text-slate-500">Aún no hay comisiones devengadas.</td></tr>@endforelse</tbody></table></div>
        </section>
    </div>
</x-layouts.internal>
