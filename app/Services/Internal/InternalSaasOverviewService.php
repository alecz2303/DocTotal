<?php

namespace App\Services\Internal;

use App\Models\AuditEvent;
use App\Models\Communication;
use App\Models\Payment;
use App\Models\Scopes\TenantScope;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InternalSaasOverviewService
{
    /**
     * Frontera explícita para consultas globales utilizadas
     * exclusivamente por la consola administrativa interna.
     */
    public function overview(): array
    {
        $now = now();

        $tenantsTotal = Tenant::query()->count();

        $activeSubscriptions = Subscription::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->count();

        $pastDueSubscriptions = Subscription::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('status', Subscription::STATUS_PAST_DUE)
            ->count();

        $failedPayments = Payment::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('status', Payment::STATUS_FAILED)
            ->count();

        $failedCommunications = Communication::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('status', Communication::STATUS_FAILED)
            ->count();

        $pendingCommunications = Communication::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('status', Communication::STATUS_PENDING)
            ->count();

        $exhaustedCommunications = Communication::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('status', Communication::STATUS_FAILED)
            ->where('attempt_count', '>=', 3)
            ->whereNull('next_attempt_at')
            ->count();

        $auditToday = AuditEvent::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('created_at', '>=', $now->copy()->startOfDay())
            ->count();

        $trialTenants = Tenant::query()
            ->where('status', 'trial')
            ->count();

        $suspendedTenants = Tenant::query()
            ->whereNotNull('suspended_at')
            ->count();

        $onboardingPending = Tenant::query()
            ->whereNull('onboarding_completed_at')
            ->count();

        $operationalIncidents =
            $pastDueSubscriptions
            + $failedPayments
            + $failedCommunications
            + $exhaustedCommunications;

        return [
            'health' => [
                'status' => $operationalIncidents === 0
                    ? 'healthy'
                    : 'attention',
                'incidents' => $operationalIncidents,
            ],

            'tenants' => [
                'total' => $tenantsTotal,
                'trial' => $trialTenants,
                'suspended' => $suspendedTenants,
                'onboarding_pending' => $onboardingPending,
            ],

            'users' => [
                'total' => User::query()
                    ->whereNotNull('tenant_id')
                    ->count(),
            ],

            'subscriptions' => [
                'active' => $activeSubscriptions,
                'past_due' => $pastDueSubscriptions,
            ],

            'payments' => [
                'failed' => $failedPayments,
            ],

            'communications' => [
                'failed' => $failedCommunications,
                'pending' => $pendingCommunications,
                'exhausted' => $exhaustedCommunications,
            ],

            'audit' => [
                'today' => $auditToday,
            ],

            'recent_tenants' => Tenant::query()
                ->select([
                    'id',
                    'uuid',
                    'name',
                    'slug',
                    'status',
                    'trial_ends_at',
                    'onboarding_completed_at',
                    'suspended_at',
                    'created_at',
                ])
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(5)
                ->get(),

            'recent_failed_payments' => Payment::query()
                ->withoutGlobalScope(TenantScope::class)
                ->with([
                    'tenant:id,uuid,name,slug,status',
                ])
                ->where('status', Payment::STATUS_FAILED)
                ->orderByRaw('failed_at IS NULL')
                ->orderByDesc('failed_at')
                ->orderByDesc('attempted_at')
                ->limit(5)
                ->get(),

            'recent_failed_communications' => Communication::query()
                ->withoutGlobalScope(TenantScope::class)
                ->with([
                    'tenant:id,uuid,name,slug,status',
                ])
                ->where('status', Communication::STATUS_FAILED)
                ->orderByRaw('failed_at IS NULL')
                ->orderByDesc('failed_at')
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
        ];
    }

    public function tenants(int $perPage = 20): LengthAwarePaginator
    {
        return Tenant::query()
            ->select([
                'id',
                'uuid',
                'name',
                'slug',
                'status',
                'trial_started_at',
                'trial_ends_at',
                'onboarding_completed_at',
                'suspended_at',
                'created_at',
            ])
            ->withCount('users')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function tenantDetail(Tenant $tenant): array
    {
        return [
            'tenant' => $tenant,

            'users' => User::query()
                ->where('tenant_id', $tenant->id)
                ->orderBy('name')
                ->get([
                    'id',
                    'uuid',
                    'name',
                    'email',
                    'role',
                    'last_login_at',
                    'created_at',
                ]),

            'subscription' => Subscription::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenant->id)
                ->latest('current_period_ends_at')
                ->first(),

            'payments' => Payment::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenant->id)
                ->latest('attempted_at')
                ->limit(10)
                ->get(),
        ];
    }
}
