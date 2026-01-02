<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ImportController;
use App\Http\Controllers\Api\ImportLogController;

Route::middleware('auth:sanctum')->group(function () {
// Route::middleware('api')->group(function () {
    Route::post('imports', [ImportController::class, 'store'])->name('api.imports.store'); 
    Route::get('imports', [ImportController::class, 'index'])->name('api.imports.index');  
    Route::get('imports/{import}', [ImportController::class, 'show'])->name('api.imports.show'); 
    Route::get('imports/{import}/logs', [ImportLogController::class, 'index'])->name('api.imports.logs.index');
    Route::get('imports/{import}/logs/download', [ImportLogController::class, 'download'])->name('api.imports.logs.download');
});