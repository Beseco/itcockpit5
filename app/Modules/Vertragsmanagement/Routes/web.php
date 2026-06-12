<?php

use App\Modules\Vertragsmanagement\Http\Controllers\VertragController;
use App\Modules\Vertragsmanagement\Http\Controllers\VertragSettingsController;
use Illuminate\Support\Facades\Route;

// Einstellungen (vor /{vertrag} platzieren)
Route::middleware(['auth', 'module.permission:vertragsmanagement,config'])->group(function () {
    Route::get('/einstellungen',  [VertragSettingsController::class, 'index'])->name('settings');
    Route::put('/einstellungen',  [VertragSettingsController::class, 'update'])->name('settings.update');
});

// Hilfe
Route::middleware('auth')->group(function () {
    Route::get('/help', fn() => view('vertragsmanagement::help'))->name('help');
});

// CRUD + Dokumente
Route::middleware(['auth', 'module.permission:vertragsmanagement,edit'])->group(function () {
    Route::get('/create',                 [VertragController::class, 'create'])->name('create');
    Route::post('/',                      [VertragController::class, 'store'])->name('store');
    Route::get('/{vertrag}/edit',         [VertragController::class, 'edit'])->name('edit');
    Route::put('/{vertrag}',              [VertragController::class, 'update'])->name('update');
    Route::delete('/{vertrag}',           [VertragController::class, 'destroy'])->name('destroy');
    Route::post('/{vertrag}/dokumente',   [VertragController::class, 'storeDokument'])->name('dokumente.store');
    Route::delete('/dokumente/{dokument}', [VertragController::class, 'destroyDokument'])->name('dokumente.destroy');
});

// Lesen
Route::middleware(['auth', 'module.permission:vertragsmanagement,view'])->group(function () {
    Route::get('/',                            [VertragController::class, 'index'])->name('index');
    Route::get('/{vertrag}',                   [VertragController::class, 'show'])->name('show');
    Route::get('/dokumente/{dokument}/download', [VertragController::class, 'downloadDokument'])->name('dokumente.download');
});
