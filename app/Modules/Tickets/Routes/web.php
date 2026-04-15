<?php

use App\Modules\Tickets\Http\Controllers\TicketsController;
use App\Modules\Tickets\Http\Controllers\TicketsSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'module.permission:tickets,view'])->group(function () {
    Route::get('/', [TicketsController::class, 'index'])->name('index');
});

Route::middleware(['auth', 'module.permission:tickets,config'])->group(function () {
    Route::get('/settings',                  [TicketsSettingsController::class, 'index'])->name('settings');
    Route::post('/settings',                 [TicketsSettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/test-connection', [TicketsSettingsController::class, 'testConnection'])->name('settings.test-connection');
});
