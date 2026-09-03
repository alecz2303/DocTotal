<?php

namespace App\Services\Internal;

use App\Models\Payment;
use App\Models\Scopes\TenantScope;
use App\Models\Subscription;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InternalBillingIncidentService
{
    /**
     * Resumen operativo global de incidencias de billing.
     *
     * Frontera explícita para lecturas cross-tenant utilizadas
     * exclusivamente por la consola administrativa interna.
     */
    public function summary(): array
    {
        return [
            'failed_payments' => Payment::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where('status', Payment::STATUS_FAILED)
                ->count(),

            'past_due_subscriptions' => Subscription::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where('status', Subscription::STATUS_PAST_DUE)
                ->count(),

            'past_due_in_grace' => Subscription::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where('status', Subscription::STATUS_PAST_DUE)
                ->whereNotNull('grace_ends_at')
                ->where('grace_ends_at', '>', now())
                ->count(),

            'past_due_grace_expired' => Subscription::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where('status', Subscription::STATUS_PAST_DUE)
                ->whereNotNull('grace_ends_at')
                ->where('grace_ends_at', '<=', now())
                ->count(),
        ];
    }

    public function failedPayments(int $perPage = 20): LengthAwarePaginator
    {
        return Payment::query()
            ->withoutGlobalScope(TenantScope::class)
            ->with([
                'tenant:id,uuid,name,slug,status',
            ])
            ->where('status', Payment::STATUS_FAILED)
            ->orderByRaw('failed_at IS NULL')
            ->orderByDesc('failed_at')
            ->orderByDesc('attempted_at')
            ->orderByDesc('id')
            ->paginate(
                perPage: $perPage,
                pageName: 'failed_payments_page'
            );
    }

    public function pastDueSubscriptions(int $perPage = 20): LengthAwarePaginator
    {
        return Subscription::query()
            ->withoutGlobalScope(TenantScope::class)
            ->with([
                'tenant:id,uuid,name,slug,status',
            ])
            ->where('status', Subscription::STATUS_PAST_DUE)
            ->orderByRaw('grace_ends_at IS NULL')
            ->orderBy('grace_ends_at')
            ->orderByRaw('past_due_since IS NULL')
            ->orderBy('past_due_since')
            ->orderByDesc('id')
            ->paginate(
                perPage: $perPage,
                pageName: 'past_due_subscriptions_page'
            );
    }
}
