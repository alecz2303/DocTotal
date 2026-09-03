<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PublicAppointmentController extends Controller
{
    public function show(string $token): View
    {
        $appointment = $this->resolveAppointment($token);

        return view(
            'public.appointments.show',
            compact('appointment', 'token')
        );
    }

    public function confirm(
        string $token,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        return DB::transaction(function () use ($token, $auditLogger) {
            $appointment = $this->resolveAppointment(
                $token,
                lockForUpdate: true,
            );

            if ($appointment->status === Appointment::STATUS_CONFIRMED) {
                return redirect()
                    ->route('public.appointments.show', ['token' => $token])
                    ->with('status', 'Tu cita ya estaba confirmada.');
            }

            abort_unless($appointment->canConfirm(), 409);

            $appointment->confirm();

            $auditLogger->safeLog(
                action: 'appointment.public_confirmed',
                auditable: $appointment,
                description: 'La cita fue confirmada mediante autoservicio del paciente.',
                metadata: [
                    'source' => 'patient_self_service',
                    'status' => Appointment::STATUS_CONFIRMED,
                ],
            );

            return redirect()
                ->route('public.appointments.show', ['token' => $token])
                ->with('status', 'Tu cita quedó confirmada.');
        });
    }

    public function cancel(
        Request $request,
        string $token,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $validated = $request->validate([
            'cancellation_reason' => ['nullable', 'string', 'max:500'],
        ]);

        return DB::transaction(function () use (
            $token,
            $validated,
            $auditLogger,
        ) {
            $appointment = $this->resolveAppointment(
                $token,
                lockForUpdate: true,
            );

            if ($appointment->status === Appointment::STATUS_CANCELLED) {
                return redirect()
                    ->route('public.appointments.show', ['token' => $token])
                    ->with('status', 'Tu cita ya estaba cancelada.');
            }

            abort_unless(
                in_array(
                    $appointment->status,
                    [
                        Appointment::STATUS_SCHEDULED,
                        Appointment::STATUS_CONFIRMED,
                    ],
                    true
                ),
                409
            );

            $appointment->cancel(
                $validated['cancellation_reason'] ?? 'Cancelada por el paciente'
            );

            $auditLogger->safeLog(
                action: 'appointment.public_cancelled',
                auditable: $appointment,
                description: 'La cita fue cancelada mediante autoservicio del paciente.',
                metadata: [
                    'source' => 'patient_self_service',
                    'status' => Appointment::STATUS_CANCELLED,
                ],
            );

            return redirect()
                ->route('public.appointments.show', ['token' => $token])
                ->with('status', 'Tu cita quedó cancelada.');
        });
    }

    private function resolveAppointment(
        string $token,
        bool $lockForUpdate = false,
    ): Appointment {
        abort_if(strlen($token) < 40, 404);

        $query = Appointment::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where(
                'public_access_token_hash',
                hash('sha256', $token)
            );

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $appointment = $query->firstOrFail();

        $tenant = Tenant::query()
            ->findOrFail($appointment->tenant_id);

        app(TenantContext::class)->set($tenant);

        $appointment->loadMissing([
            'patient',
            'doctorProfile',
        ]);

        return $appointment;
    }
}
