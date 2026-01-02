<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('imports', function () {
        return Inertia::render('Imports');
    })->name('imports');

    Route::get('imports/{id}', function (Request $request) {
        return Inertia::render('imports/View', [
            'importId' => $request->route('id'),
        ]);
    })->name('imports.view');

    Route::get('upload', function () {
        return Inertia::render('Upload');
    })->name('upload');
});

require __DIR__ . '/settings.php';
