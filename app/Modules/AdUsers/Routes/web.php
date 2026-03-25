<?php

use App\Modules\AdUsers\Http\Controllers\AdUserController;
use App\Modules\AdUsers\Http\Controllers\AdUserSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'module.permission:adusers,view'])->group(function () {
    Route::get('/',               [AdUserController::class, 'index'])->name('index');
    Route::get('/show/{user}',    [AdUserController::class, 'show'])->name('show');
    Route::delete('/{user}',      [AdUserController::class, 'destroy'])->name('destroy');
    Route::post('/bulk-delete',   [AdUserController::class, 'bulkDestroy'])->name('bulk-delete');
});

Route::middleware(['auth', 'module.permission:adusers,config'])->group(function () {
    Route::get('/settings',                      [AdUserSettingsController::class, 'index'])->name('settings');
    Route::post('/settings',                     [AdUserSettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/test-connection',     [AdUserSettingsController::class, 'testConnection'])->name('settings.test-connection');
    Route::post('/settings/test-query',          [AdUserSettingsController::class, 'testQuery'])->name('settings.test-query');
});

Route::middleware(['auth', 'module.permission:adusers,sync'])->group(function () {
    Route::post('/sync', [AdUserSettingsController::class, 'runSync'])->name('sync');
});
