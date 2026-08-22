<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])
    ->get('/dashboard', function () {
        return view('dashboard');
    })
    ->name('dashboard');
