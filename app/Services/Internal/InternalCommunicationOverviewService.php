<?php

namespace App\Services\Internal;

use App\Models\Communication;
use App\Models\Scopes\TenantScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InternalCommunicationOverviewService
{
    /**
     * Frontera explícita para lecturas globales de comunicaciones
     * utilizadas exclusivamente por la consola administrativa interna.
     */
    public function summary(): array
    {
        return [
            'pending' => $this->globalQuery()
                ->where('status', Communication::STATUS_PENDING)
                ->count(),

            'sent' => $this->globalQuery()
                ->where('status', Communication::STATUS_SENT)
                ->count(),

            'failed' => $this->globalQuery()
                ->where('status', Communication::STATUS_FAILED)
                ->count(),

            'cancelled' => $this->globalQuery()
                ->where('status', Communication::STATUS_CANCELLED)
                ->count(),

            'failed_retry_scheduled' => $this->globalQuery()
                ->where('status', Communication::STATUS_FAILED)
                ->whereNotNull('next_attempt_at')
                ->count(),

            'failed_exhausted' => $this->globalQuery()
                ->where('status', Communication::STATUS_FAILED)
                ->where('attempt_count', '>=', 3)
                ->whereNull('next_attempt_at')
                ->count(),
        ];
    }

    public function failedCommunications(
        int $perPage = 20
    ): LengthAwarePaginator {
        return $this->globalQuery()
            ->with([
                'tenant:id,uuid,name,slug,status',
            ])
            ->where('status', Communication::STATUS_FAILED)
            ->orderByRaw('failed_at IS NULL')
            ->orderByDesc('failed_at')
            ->orderByDesc('id')
            ->paginate(
                perPage: $perPage,
                pageName: 'failed_communications_page'
            );
    }

    public function pendingCommunications(
        int $perPage = 20
    ): LengthAwarePaginator {
        return $this->globalQuery()
            ->with([
                'tenant:id,uuid,name,slug,status',
            ])
            ->where('status', Communication::STATUS_PENDING)
            ->orderByRaw('scheduled_for IS NULL')
            ->orderBy('scheduled_for')
            ->orderBy('id')
            ->paginate(
                perPage: $perPage,
                pageName: 'pending_communications_page'
            );
    }

    public function cancelledCommunications(
        int $perPage = 20
    ): LengthAwarePaginator {
        return $this->globalQuery()
            ->with([
                'tenant:id,uuid,name,slug,status',
            ])
            ->where('status', Communication::STATUS_CANCELLED)
            ->orderByRaw('cancelled_at IS NULL')
            ->orderByDesc('cancelled_at')
            ->orderByDesc('id')
            ->paginate(
                perPage: $perPage,
                pageName: 'cancelled_communications_page'
            );
    }

    private function globalQuery()
    {
        return Communication::query()
            ->withoutGlobalScope(TenantScope::class);
    }
}
