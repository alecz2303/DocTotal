<?php

namespace App\Services\Billing;

use App\Models\Payment;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;

class TenantCommercialStatusPresenter
{
    /**
     * Build a presentation-only commercial notice from existing
     * tenant, subscription and payment sources of truth.
     *
     * @return array{
     *     type: string,
     *     title: string,
     *     message: string,
     *     action_label: string,
     *     action_route: string
     * }|null
     */
    public function noticeFor(Tenant $tenant): ?array
    {
        $subscription = $tenant->currentSubscription();

        if ($subscription?->isPastDue()) {
            $message = $subscription->grace_ends_at
                ? sprintf(
                    'Tu suscripción está pendiente de pago. El periodo de gracia termina el %s.',
                    $subscription->grace_ends_at->format('d/m/Y')
                )
                : 'Tu suscripción está pendiente de pago y requiere atención.';

            return $this->notice(
                'warning',
                'Pago pendiente',
                $message,
                'Resolver pago'
            );
        }

        $latestPayment = Payment::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->id)
            ->latest('id')
            ->first();

        if ($latestPayment?->isFailed()) {
            return $this->notice(
                'danger',
                'No pudimos procesar tu último pago',
                'Revisa tu suscripción y método de pago para mantener DocTotal activo.',
                'Revisar pago'
            );
        }

        if ($latestPayment?->isPending() && ! $subscription) {
            return $this->notice(
                'warning',
                'Tienes un pago pendiente',
                'Hay un pago de suscripción pendiente de completar.',
                'Continuar en facturación'
            );
        }

        if ($tenant->isOnTrial()) {
            $daysRemaining = $tenant->trialDaysRemaining();
            $endsAt = $tenant->trial_ends_at?->format('d/m/Y');

            $message = match (true) {
                $daysRemaining === 0 => sprintf(
                    'Tu periodo de prueba termina hoy%s.',
                    $endsAt ? ' (' . $endsAt . ')' : ''
                ),
                $daysRemaining === 1 => sprintf(
                    'Te queda 1 día de prueba%s.',
                    $endsAt ? '. Finaliza el ' . $endsAt : ''
                ),
                default => sprintf(
                    'Te quedan %d días de prueba%s.',
                    $daysRemaining ?? 0,
                    $endsAt ? '. Finaliza el ' . $endsAt : ''
                ),
            };

            return $this->notice(
                ($daysRemaining ?? 0) <= 3 ? 'warning' : 'info',
                'Periodo de prueba',
                $message,
                'Ver suscripción'
            );
        }

        return null;
    }

    /**
     * @return array{
     *     type: string,
     *     title: string,
     *     message: string,
     *     action_label: string,
     *     action_route: string
     * }
     */
    private function notice(
        string $type,
        string $title,
        string $message,
        string $actionLabel
    ): array {
        return [
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'action_label' => $actionLabel,
            'action_route' => 'settings.billing',
        ];
    }
}
