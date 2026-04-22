<?php

use App\Modules\Backup\Http\Controllers\BackupController;
use App\Modules\Backup\Http\Controllers\BackupSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'module.permission:backup,view'])->group(function () {
    Route::get('/help', fn() => view('backup::help'))->name('help');
    Route::get('/',                                  [BackupController::class, 'index'])->name('index');
    Route::post('/',                                 [BackupController::class, 'store'])->name('store');
    Route::get('/download/{name}/{type}',            [BackupController::class, 'download'])->name('download');
    Route::delete('/{name}',                         [BackupController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth', 'module.permission:backup,config'])->group(function () {
    Route::get('/settings',  [BackupSettingsController::class, 'index'])->name('settings');
    Route::post('/settings', [BackupSettingsController::class, 'update'])->name('settings.update');
});
