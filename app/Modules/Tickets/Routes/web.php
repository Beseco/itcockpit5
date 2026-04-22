<?php

use App\Modules\Tickets\Http\Controllers\TicketsController;
use App\Modules\Tickets\Http\Controllers\TicketsSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'module.permission:tickets,view'])->group(function () {
    Route::get('/help', fn() => view('tickets::help'))->name('help');
    Route::get('/', [TicketsController::class, 'index'])->name('index');
    Route::get('/debug', [TicketsController::class, 'debug'])->name('debug');
});

Route::middleware(['auth', 'module.permission:tickets,config'])->group(function () {
    Route::get('/settings',                  [TicketsSettingsController::class, 'index'])->name('settings');
    Route::post('/settings',                 [TicketsSettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/scoring',         [TicketsSettingsController::class, 'updateScoring'])->name('settings.update-scoring');
    Route::post('/settings/test-connection', [TicketsSettingsController::class, 'testConnection'])->name('settings.test-connection');
    Route::post('/settings/test-mail',       [TicketsSettingsController::class, 'sendTestMail'])->name('settings.send-test-mail');
});
