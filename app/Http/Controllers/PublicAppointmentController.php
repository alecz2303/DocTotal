<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\DoctorProfile;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Services\AppointmentAvailabilityService;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PublicAppointmentController extends Controller
{
    public function show(Request $request, string $token, AppointmentAvailabilityService $availability): View
    {
        $appointment = $this->resolveAppointment($token);

        if (! $request->boolean('reschedule')) {
            return view('public.appointments.show', compact('appointment', 'token'));
        }

        abort_unless($appointment->canReschedule(), 409);
        $date = (string) $request->query('date', $appointment->starts_at->format('Y-m-d'));

        try {
            $selectedDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
        } catch (\Throwable) {
            throw ValidationException::withMessages(['date' => 'Selecciona una fecha válida.']);
        }

        if ($selectedDate->lt(now()->startOfDay())) {
            throw ValidationException::withMessages(['date' => 'Selecciona una fecha de hoy en adelante.']);
        }

        $duration = max(1, (int) $appointment->starts_at->diffInMinutes($appointment->ends_at));
        $availableSlots = $availability
            ->slotsForDate($appointment->doctorProfile, $selectedDate, $duration, $appointment)
            ->map(fn ($slot) => $slot->format('H:i'))
            ->values()
            ->all();

        return view('public.appointments.reschedule', compact('appointment', 'token', 'date', 'availableSlots', 'duration'));
    }

    public function confirm(string $token, AuditLogger $auditLogger): RedirectResponse
    {
        return DB::transaction(function () use ($token, $auditLogger) {
            $appointment = $this->resolveAppointment($token, lockForUpdate: true);

            if ($appointment->status === Appointment::STATUS_CONFIRMED) {
                return redirect()->route('public.appointments.show', ['token' => $token])->with('status', 'Tu cita ya estaba confirmada.');
            }

            abort_unless($appointment->canConfirm(), 409);
            $appointment->confirm();

            $auditLogger->safeLog(
                action: 'appointment.public_confirmed',
                auditable: $appointment,
                description: 'La cita fue confirmada mediante autoservicio del paciente.',
                metadata: ['source' => 'patient_self_service', 'status' => Appointment::STATUS_CONFIRMED],
            );

            return redirect()->route('public.appointments.show', ['token' => $token])->with('status', 'Tu cita quedó confirmada.');
        });
    }

    public function cancel(Request $request, string $token, AuditLogger $auditLogger, AppointmentAvailabilityService $availability): RedirectResponse
    {
        if ($request->input('_action') === 'reschedule') {
            return $this->storeReschedule($request, $token, $availability, $auditLogger);
        }

        $validated = $request->validate(['cancellation_reason' => ['nullable', 'string', 'max:500']]);

        return DB::transaction(function () use ($token, $validated, $auditLogger) {
            $appointment = $this->resolveAppointment($token, lockForUpdate: true);

            if ($appointment->status === Appointment::STATUS_CANCELLED) {
                return redirect()->route('public.appointments.show', ['token' => $token])->with('status', 'Tu cita ya estaba cancelada.');
            }

            abort_unless(in_array($appointment->status, [Appointment::STATUS_SCHEDULED, Appointment::STATUS_CONFIRMED], true), 409);
            $appointment->cancel($validated['cancellation_reason'] ?? 'Cancelada por el paciente');

            $auditLogger->safeLog(
                action: 'appointment.public_cancelled',
                auditable: $appointment,
                description: 'La cita fue cancelada mediante autoservicio del paciente.',
                metadata: ['source' => 'patient_self_service', 'status' => Appointment::STATUS_CANCELLED],
            );

            return redirect()->route('public.appointments.show', ['token' => $token])->with('status', 'Tu cita quedó cancelada.');
        });
    }

    private function storeReschedule(Request $request, string $token, AppointmentAvailabilityService $availability, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'time' => ['required', 'date_format:H:i'],
        ]);

        return DB::transaction(function () use ($token, $validated, $availability, $auditLogger) {
            $appointment = $this->resolveAppointment($token, lockForUpdate: true);
            abort_unless($appointment->canReschedule(), 409);

            $doctor = DoctorProfile::query()
                ->whereKey($appointment->doctor_profile_id)
                ->lockForUpdate()
                ->firstOrFail();

            $startsAt = Carbon::createFromFormat('Y-m-d H:i', $validated['date'].' '.$validated['time']);

            if (! $startsAt->greaterThan(now()->addMinutes(5))) {
                throw ValidationException::withMessages(['time' => 'Selecciona un horario futuro disponible.']);
            }

            $duration = max(1, (int) $appointment->starts_at->diffInMinutes($appointment->ends_at));

            if (! $availability->isAvailable($doctor, $startsAt, $duration, $appointment)) {
                throw ValidationException::withMessages(['time' => 'Este horario ya no está disponible.']);
            }

            $previousStartsAt = $appointment->starts_at->copy();
            $previousEndsAt = $appointment->ends_at->copy();
            $endsAt = $startsAt->copy()->addMinutes($duration);

            $appointment->reschedule($startsAt, $endsAt);

            $auditLogger->safeLog(
                action: 'appointment.public_rescheduled',
                auditable: $appointment,
                description: 'La cita fue reprogramada mediante autoservicio del paciente.',
                metadata: [
                    'source' => 'patient_self_service',
                    'previous_starts_at' => $previousStartsAt->toISOString(),
                    'previous_ends_at' => $previousEndsAt->toISOString(),
                    'new_starts_at' => $startsAt->toISOString(),
                    'new_ends_at' => $endsAt->toISOString(),
                ],
            );

            $newToken = $appointment->issuePublicAccessToken();

            return redirect()->route('public.appointments.show', ['token' => $newToken])
                ->with('status', 'Tu cita fue reprogramada. Revisa la nueva fecha y hora.');
        });
    }

    private function resolveAppointment(string $token, bool $lockForUpdate = false): Appointment
    {
        if (strlen($token) < 32) {
            $this->abortWithUnavailableLink();
        }

        $query = Appointment::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('public_access_token_hash', hash('sha256', $token));

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $appointment = $query->first();
        if (! $appointment) {
            $this->abortWithUnavailableLink();
        }

        $tenant = Tenant::query()->find($appointment->tenant_id);
        if (! $tenant) {
            $this->abortWithUnavailableLink();
        }

        app(TenantContext::class)->set($tenant);
        $appointment->loadMissing(['patient', 'doctorProfile']);

        return $appointment;
    }

    private function abortWithUnavailableLink(): never
    {
        abort(response()->view('public.appointments.unavailable', status: 404));
    }
}
