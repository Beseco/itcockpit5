<?php

use App\Modules\Baramundi\Http\Controllers\BaraEventController;
use App\Modules\Baramundi\Http\Controllers\BaraPackageController;
use App\Modules\Baramundi\Http\Controllers\BaraSettingsController;
use Illuminate\Support\Facades\Route;

// Settings zuerst – vor /{pkg}, sonst wird "settings" als Paket-ID interpretiert
Route::middleware(['auth', 'module.permission:baramundi,config'])->group(function () {
    Route::get('/settings',           [BaraSettingsController::class, 'index'])->name('settings');
    Route::put('/settings',           [BaraSettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/test-smb', [BaraSettingsController::class, 'testSmb'])->name('settings.test-smb');
});

// Events
Route::middleware(['auth', 'module.permission:baramundi,view'])->group(function () {
    Route::get('/events', [BaraEventController::class, 'index'])->name('events');
});

// Paket CRUD
Route::middleware(['auth', 'module.permission:baramundi,edit'])->group(function () {
    Route::get('/packages/create',      [BaraPackageController::class, 'create'])->name('packages.create');
    Route::post('/packages',            [BaraPackageController::class, 'store'])->name('packages.store');
    Route::get('/packages/{pkg}/edit',  [BaraPackageController::class, 'edit'])->name('packages.edit');
    Route::put('/packages/{pkg}',       [BaraPackageController::class, 'update'])->name('packages.update');
    Route::delete('/packages/{pkg}',    [BaraPackageController::class, 'destroy'])->name('packages.destroy');
});

// Dashboard + Detail + Scan
Route::middleware(['auth', 'module.permission:baramundi,view'])->group(function () {
    Route::get('/',                         [BaraPackageController::class, 'index'])->name('index');
    Route::get('/packages/{pkg}',           [BaraPackageController::class, 'show'])->name('packages.show');
    Route::post('/packages/{pkg}/scan',     [BaraPackageController::class, 'scan'])->name('packages.scan');
});
