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

Route::get('dashboard', function (Request $request) {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('imports', function (Request $request) {


    $user = $request->user();
    $token = $user->currentAccessToken() ?? $user->createToken('imports')->plainTextToken;

    return Inertia::render('Imports', [
        'token' => $token,
    ]);
})->middleware(['auth', 'verified'])->name('imports');

Route::get('imports/{id}', function (Request $request) {


    $user = $request->user();
    $token = $user->currentAccessToken() ?? $user->createToken('imports')->plainTextToken;

    return Inertia::render('imports/View', [
        'token' => $token,
        'importId' => $request->route('id'),
    ]);
})->middleware(['auth', 'verified'])->name('imports.view');

Route::get('upload', function (Request $request) {


    $user = $request->user();
    $token = $user->currentAccessToken() ?? $user->createToken('imports')->plainTextToken;

    return Inertia::render('Upload', [
        'token' => $token,
    ]);
})->middleware(['auth', 'verified'])->name('upload');

require __DIR__ . '/settings.php';
