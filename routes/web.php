<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Internal\InternalDashboardController;
use App\Http\Controllers\Internal\InternalTenantController;
use App\Http\Controllers\Internal\InternalBillingController;
use App\Http\Controllers\Internal\InternalCommunicationController;
use App\Http\Controllers\Internal\InternalAuditController;
use App\Http\Controllers\ServiceSuspendedController;
use App\Http\Controllers\PublicAppointmentController;



Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::prefix('a')->name('public.appointments.')->group(function () {
    Route::get('/{token}', [PublicAppointmentController::class, 'show'])
        ->name('show');

    Route::post('/{token}/confirm', [PublicAppointmentController::class, 'confirm'])
        ->name('confirm');

    Route::post('/{token}/cancel', [PublicAppointmentController::class, 'cancel'])
        ->name('cancel');
});

// Compatibilidad con enlaces emitidos antes de DT-25.
Route::prefix('appointment')->group(function () {
    Route::get('/{token}', [PublicAppointmentController::class, 'show']);
    Route::post('/{token}/confirm', [PublicAppointmentController::class, 'confirm']);
    Route::post('/{token}/cancel', [PublicAppointmentController::class, 'cancel']);
});

// Route::get('/', function () {
//     return redirect()->route('dashboard');
// });

Route::middleware('auth')->group(function () {

    Route::livewire('/onboarding', 'pages::onboarding.wizard')
        ->middleware(['auth', 'verified'])
        ->name('onboarding');

    Route::get('/service/suspended', ServiceSuspendedController::class)
        ->name('service.suspended');

    Route::livewire(
        '/settings/billing',
        'pages::settings.billing'
    )->middleware('verified')->name('settings.billing');

    Route::livewire(
        '/settings/security',
        'pages::settings.security'
    )->name('settings.security');

    Route::middleware(['verified', 'internal.admin'])->group(function () {
        Route::get('/internal', InternalDashboardController::class)
            ->name('internal.dashboard');

        Route::get('/internal/tenants', [InternalTenantController::class, 'index'])
            ->name('internal.tenants.index');

        Route::get('/internal/tenants/{tenant}', [InternalTenantController::class, 'show'])
            ->name('internal.tenants.show');

        Route::get('/internal/billing', [InternalBillingController::class, 'index'])
            ->name('internal.billing.index');

        Route::get('/internal/communications', [InternalCommunicationController::class, 'index'])
            ->name('internal.communications.index');

        Route::get('/internal/audit', [InternalAuditController::class, 'index'])
            ->name('internal.audit.index');
    });


    Route::middleware([
        'verified',
        'onboarding',
        'service.access',
    ])->group(function () {

        Route::get('/dashboard', function () {
            $today = now()->startOfDay();

            /*
            |--------------------------------------------------------------------------
            | Citas de hoy
            |--------------------------------------------------------------------------
            */

            $appointmentsToday = \App\Models\Appointment::query()
                ->with([
                    'patient',
                    'doctorProfile',
                ])
                ->whereDate(
                    'starts_at',
                    $today->toDateString()
                )
                ->orderBy('starts_at')
                ->get();

            $appointmentsTodayCount = $appointmentsToday
                ->whereNotIn('status', [
                    'cancelled',
                ])
                ->count();

            $pendingAppointmentsCount = $appointmentsToday
                ->whereIn('status', [
                    'scheduled',
                    'confirmed',
                    'checked_in',
                ])
                ->count();

            $completedAppointmentsCount = $appointmentsToday
                ->where('status', 'completed')
                ->count();

            /*
            |--------------------------------------------------------------------------
            | Pacientes
            |--------------------------------------------------------------------------
            */

            $patientsCount = \App\Models\Patient::query()
                ->count();

            /*
            |--------------------------------------------------------------------------
            | Próxima cita
            |--------------------------------------------------------------------------
            */

            $nextAppointment = \App\Models\Appointment::query()
                ->with([
                    'patient',
                    'doctorProfile',
                ])
                ->where(
                    'starts_at',
                    '>=',
                    now()
                )
                ->whereNotIn('status', [
                    'cancelled',
                    'completed',
                    'no_show',
                ])
                ->orderBy('starts_at')
                ->first();

            /*
            |--------------------------------------------------------------------------
            | Actividad clínica
            |--------------------------------------------------------------------------
            */

            $completedConsultationsTodayCount =
                \App\Models\Consultation::query()
                ->whereDate(
                    'consultation_at',
                    $today->toDateString()
                )
                ->where(
                    'status',
                    \App\Models\Consultation::STATUS_COMPLETED
                )
                ->count();

            $draftConsultationsTodayCount =
                \App\Models\Consultation::query()
                ->whereDate(
                    'consultation_at',
                    $today->toDateString()
                )
                ->where(
                    'status',
                    \App\Models\Consultation::STATUS_DRAFT
                )
                ->count();

            $prescriptionsTodayCount =
                \App\Models\Prescription::query()
                ->whereDate(
                    'prescribed_at',
                    $today->toDateString()
                )
                ->count();

            /*
            |--------------------------------------------------------------------------
            | Estados de citas
            |--------------------------------------------------------------------------
            */

            $appointmentStatusCounts = [
                'scheduled' => $appointmentsToday
                    ->where('status', 'scheduled')
                    ->count(),

                'confirmed' => $appointmentsToday
                    ->where('status', 'confirmed')
                    ->count(),

                'checked_in' => $appointmentsToday
                    ->where('status', 'checked_in')
                    ->count(),

                'in_progress' => $appointmentsToday
                    ->where('status', 'in_progress')
                    ->count(),

                'completed' => $appointmentsToday
                    ->where('status', 'completed')
                    ->count(),

                'cancelled' => $appointmentsToday
                    ->where('status', 'cancelled')
                    ->count(),

                'no_show' => $appointmentsToday
                    ->where('status', 'no_show')
                    ->count(),
            ];

            /*
            |--------------------------------------------------------------------------
            | Próximos 7 días
            |--------------------------------------------------------------------------
            */

            $appointmentsNextDays = collect(
                range(0, 6)
            )->map(function (int $offset) {
                $date = now()
                    ->copy()
                    ->addDays($offset);

                $count = \App\Models\Appointment::query()
                    ->whereDate(
                        'starts_at',
                        $date->toDateString()
                    )
                    ->whereNotIn('status', [
                        'cancelled',
                    ])
                    ->count();

                return [
                    'date' => $date,
                    'count' => $count,
                ];
            });

            $maxAppointmentsPerDay = max(
                1,
                $appointmentsNextDays
                    ->max('count')
            );

            $doctor = \App\Models\DoctorProfile::query()
                ->where('user_id', auth()->id())
                ->first();

            $todaySchedule = null;
            $todayException = null;

            if ($doctor) {
                $todaySchedule = \App\Models\Schedule::query()
                    ->where('doctor_profile_id', $doctor->id)
                    ->where('day_of_week', now()->dayOfWeek)
                    ->where('active', true)
                    ->orderBy('start_time')
                    ->get();

                $todayException = \App\Models\ScheduleException::query()
                    ->where('doctor_profile_id', $doctor->id)
                    ->whereDate('date', now()->toDateString())
                    ->orderBy('start_time')
                    ->get();
            }

            $fullDayBlockedException = $todayException
                ?->first(function ($exception) {
                    return $exception->type === 'blocked'
                        && ! $exception->start_time
                        && ! $exception->end_time;
                });

            $isRegularDayOff =
                $todaySchedule
                && $todaySchedule->isEmpty()
                && ! $todayException?->contains('type', 'available');

            $isDayOff =
                (bool) $fullDayBlockedException
                || $isRegularDayOff;

            $dayOffReason = null;

            if ($fullDayBlockedException) {
                $dayOffReason =
                    $fullDayBlockedException->reason
                    ?: 'Día bloqueado';
            } elseif ($isRegularDayOff) {
                $dayOffReason = 'Día libre';
            }

            return view(
                'dashboard',
                compact(
                    'appointmentsToday',
                    'appointmentsTodayCount',
                    'pendingAppointmentsCount',
                    'completedAppointmentsCount',
                    'patientsCount',
                    'nextAppointment',
                    'completedConsultationsTodayCount',
                    'draftConsultationsTodayCount',
                    'prescriptionsTodayCount',
                    'appointmentStatusCounts',
                    'appointmentsNextDays',
                    'todaySchedule',
                    'todayException',
                    'isDayOff',
                    'dayOffReason',
                    'maxAppointmentsPerDay'
                )
            );
        })->name('dashboard');

        Route::livewire('/clinical-templates', 'pages::clinical-templates.index')
            ->name('clinical-templates.index');

        Route::livewire('/patients', 'pages::patients.index')
            ->name('patients.index');

        Route::livewire('/patients/{uuid}', 'pages::patients.show')
            ->name('patients.show');

        Route::livewire('/patients/{uuid}/edit', 'pages::patients.edit')
            ->name('patients.edit');

        Route::livewire(
            '/patients/{uuid}/laboratories',
            'pages::laboratories.index'
        )->name('patients.laboratories.index');

        Route::livewire(
            '/patients/{uuid}/consultations/create',
            'pages::consultations.create'
        )->name('consultations.create');

        Route::livewire(
            '/consultations/{uuid}',
            'pages::consultations.show'
        )->name('consultations.show');

        Route::livewire(
            '/consultations/{uuid}/prescriptions/create',
            'pages::prescriptions.create'
        )->name('prescriptions.create');

        Route::livewire(
            '/prescriptions/{uuid}',
            'pages::prescriptions.show'
        )->name('prescriptions.show');

        Route::livewire('/prescriptions', 'pages::prescriptions.index')
            ->name('prescriptions.index');

        Route::get(
            '/prescriptions/{uuid}/print',
            function (string $uuid) {
                $prescription = \App\Models\Prescription::query()
                    ->with([
                        'patient',
                        'doctorProfile.specialty',
                        'consultation',
                        'items',
                    ])
                    ->where('uuid', $uuid)
                    ->firstOrFail();

                $practice = \App\Models\PracticeProfile::query()
                    ->firstOrFail();

                return view(
                    'prescriptions.print',
                    compact(
                        'prescription',
                        'practice'
                    )
                );
            }
        )->name('prescriptions.print');

        Route::get(
            '/prescriptions/{uuid}/pdf',
            function (string $uuid) {
                $prescription = \App\Models\Prescription::query()
                    ->with([
                        'patient',
                        'doctorProfile.specialty',
                        'consultation',
                        'items',
                    ])
                    ->where('uuid', $uuid)
                    ->firstOrFail();

                $practice = \App\Models\PracticeProfile::query()
                    ->firstOrFail();

                $patientName = collect([
                    $prescription->patient->first_name,
                    $prescription->patient->last_name,
                    $prescription->patient->second_last_name,
                ])
                    ->filter()
                    ->implode(' ');

                $fileName =
                    'receta-'
                    . \Illuminate\Support\Str::slug($patientName)
                    . '-'
                    . $prescription->prescribed_at->format('Y-m-d')
                    . '.pdf';

                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
                    'prescriptions.pdf',
                    [
                        'prescription' => $prescription,
                        'practice' => $practice,
                    ]
                )->setPaper('letter');

                return $pdf->download($fileName);
            }
        )->name('prescriptions.pdf');

        Route::get(
            '/clinical-documents/{clinicalDocument}/download',
            \App\Http\Controllers\ClinicalDocumentDownloadController::class
        )->name('clinical-documents.download');

        Route::delete(
            '/clinical-documents/{clinicalDocument}',
            \App\Http\Controllers\ClinicalDocumentDeleteController::class
        )->name('clinical-documents.destroy');

        Route::get(
            '/clinical-documents/{clinicalDocument}/view',
            \App\Http\Controllers\ClinicalDocumentViewController::class
        )->name('clinical-documents.view');

        Route::livewire(
            '/consultations',
            'pages::consultations.index'
        )->name('consultations.index');

        Route::livewire(
            '/prescriptions/{uuid}/edit',
            'pages::prescriptions.edit'
        )->name('prescriptions.edit');

        Route::livewire(
            '/settings/profile',
            'pages::settings.profile'
        )->name('settings.profile');

        // Route::livewire(
        //     '/settings/billing',
        //     'pages::settings.billing'
        // )->name('settings.billing');

        Route::livewire(
            '/appointments',
            'pages::appointments.index'
        )->name('appointments.index');

        Route::livewire(
            '/appointments/create',
            'pages::appointments.create'
        )->name('appointments.create');

        Route::livewire(
            '/appointments/{uuid}',
            'pages::appointments.show'
        )->name('appointments.show');

        Route::livewire(
            '/appointments/{uuid}/edit',
            'pages::appointments.edit'
        )->name('appointments.edit');

        Route::livewire(
            '/appointments/{uuid}/reschedule',
            'pages::appointments.reschedule'
        )->name('appointments.reschedule');
    });
});
