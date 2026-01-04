<?php

use App\Http\Controllers\Api\ImportController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('imports', [ImportController::class, 'store'])->name('api.imports.store');
    Route::get('imports', [ImportController::class, 'index'])->name('api.imports.index');
    Route::get('imports/{import}', [ImportController::class, 'show'])->name('api.imports.show');
    Route::get('imports/{import}/logs', [ImportController::class, 'showLogs'])->name('api.imports.showLogs');
});
