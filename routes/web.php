<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware('auth')->group(function () {

    Route::livewire('/onboarding', 'pages::onboarding.wizard')
        ->middleware('auth')
        ->name('onboarding');

    Route::middleware('onboarding')->group(function () {

        Route::get('/dashboard', function () {
            return view('dashboard');
        })->name('dashboard');

        Route::livewire('/patients', 'pages::patients.index')
            ->name('patients.index');

        Route::livewire('/patients/{uuid}', 'pages::patients.show')
            ->name('patients.show');

        Route::livewire('/patients/{uuid}/edit', 'pages::patients.edit')
            ->name('patients.edit');

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
    });
});
