<?php

namespace App\Services\Internal;

use App\Models\AuditEvent;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class InternalAuditOverviewService
{
    public function summary(): array
    {
        $now = now();

        return [
            'total' => $this->globalQuery()->count(),

            'today' => $this->globalQuery()
                ->where('created_at', '>=', $now->copy()->startOfDay())
                ->count(),

            'last_7_days' => $this->globalQuery()
                ->where('created_at', '>=', $now->copy()->subDays(7))
                ->count(),

            'distinct_actions' => $this->globalQuery()
                ->distinct()
                ->count('action'),
        ];
    }

    public function events(
        array $filters = [],
        int $perPage = 30
    ): LengthAwarePaginator {
        return $this->globalQuery()
            ->with([
                'tenant:id,uuid,name,slug,status',
                'user:id,uuid,name,email,role',
            ])
            ->when(
                filled($filters['action'] ?? null),
                fn (Builder $query) =>
                    $query->where('action', $filters['action'])
            )
            ->when(
                filled($filters['tenant_id'] ?? null),
                fn (Builder $query) =>
                    $query->where('tenant_id', $filters['tenant_id'])
            )
            ->when(
                filled($filters['user_id'] ?? null),
                fn (Builder $query) =>
                    $query->where('user_id', $filters['user_id'])
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(
                perPage: $perPage,
                pageName: 'audit_page'
            );
    }

    public function actions(): Collection
    {
        return $this->globalQuery()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');
    }

    public function tenants(): Collection
    {
        return Tenant::query()
            ->select([
                'id',
                'name',
                'slug',
            ])
            ->orderBy('name')
            ->get();
    }

    private function globalQuery(): Builder
    {
        return AuditEvent::query()
            ->withoutGlobalScope(TenantScope::class);
    }
}
