<?php

use App\Modules\Wid\Http\Controllers\WidController;
use App\Modules\Wid\Http\Controllers\WidSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'module.permission:wid,view'])->group(function () {
    Route::get('/', [WidController::class, 'index'])->name('index');
});

Route::middleware(['auth', 'module.permission:wid,config'])->group(function () {
    Route::get('/settings', [WidSettingsController::class, 'edit'])->name('settings');
    Route::put('/settings', [WidSettingsController::class, 'update'])->name('settings.update');
    Route::post('/fetch-now', [WidSettingsController::class, 'fetchNow'])->name('fetch-now');
});
