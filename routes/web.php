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
    });
});
