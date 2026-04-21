<?php

use App\Modules\Server\Http\Controllers\CheckMkController;
use App\Modules\Server\Http\Controllers\ServerController;
use App\Modules\Server\Http\Controllers\ServerSettingsController;
use Illuminate\Support\Facades\Route;

// Spezifische Routen VOR parametrisierten Routen (verhindert Konflikte)

Route::middleware(['auth', 'module.permission:server,config'])->group(function () {
    Route::get('/settings',                              [ServerSettingsController::class, 'index'])->name('settings');
    Route::post('/settings/options',                     [ServerSettingsController::class, 'storeOption'])->name('settings.options.store');
    Route::delete('/settings/options/{option}',          [ServerSettingsController::class, 'destroyOption'])->name('settings.options.destroy');
    Route::post('/settings/sync-ous',                    [ServerSettingsController::class, 'storeOu'])->name('settings.sync-ous.store');
    Route::delete('/settings/sync-ous/{ou}',             [ServerSettingsController::class, 'destroyOu'])->name('settings.sync-ous.destroy');
    Route::patch('/settings/sync-ous/{ou}/toggle',       [ServerSettingsController::class, 'toggleOu'])->name('settings.sync-ous.toggle');
    Route::put('/settings/checkmk',                      [CheckMkController::class, 'update'])->name('settings.checkmk.update');
    Route::post('/settings/checkmk/test',                [CheckMkController::class, 'test'])->name('settings.checkmk.test');
    Route::post('/settings/checkmk/test-host',           [CheckMkController::class, 'testHost'])->name('settings.checkmk.test-host');
});

Route::middleware(['auth', 'module.permission:server,view'])->group(function () {
    Route::get('/{server}/checkmk-data', [CheckMkController::class, 'hostData'])->name('checkmk.data');
});

Route::middleware(['auth', 'module.permission:server,sync'])->group(function () {
    Route::post('/sync', [ServerSettingsController::class, 'runSync'])->name('sync');
});

Route::middleware(['auth', 'module.permission:server,edit'])->group(function () {
    Route::post('/set-revision-dates',              [ServerController::class, 'setRevisionDates'])->name('set-revision-dates');
    Route::post('/resolve-ips',                     [ServerController::class, 'resolveIps'])->name('resolve-ips');
    Route::post('/{server}/revision-done',          [ServerController::class, 'markRevisionDone'])->name('revision-done');
});

Route::middleware(['auth', 'module.permission:server,create'])->group(function () {
    Route::get('/create',  [ServerController::class, 'create'])->name('create');
    Route::post('/',       [ServerController::class, 'store'])->name('store');
});

Route::middleware(['auth', 'module.permission:server,view'])->group(function () {
    Route::get('/',        [ServerController::class, 'index'])->name('index');
});

Route::middleware(['auth', 'module.permission:server,edit'])->group(function () {
    Route::get('/{server}/edit', [ServerController::class, 'edit'])->name('edit');
    Route::put('/{server}',      [ServerController::class, 'update'])->name('update');
});

Route::middleware(['auth', 'module.permission:server,delete'])->group(function () {
    Route::delete('/{server}',   [ServerController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth', 'module.permission:server,view'])->group(function () {
    Route::get('/{server}',      [ServerController::class, 'show'])->name('show');
});
