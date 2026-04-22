<?php

use App\Modules\Entsorgung\Http\Controllers\EntsorgungController;
use App\Modules\Entsorgung\Http\Controllers\EntsorgungListenController;
use Illuminate\Support\Facades\Route;

// Nur Lesezugriff
Route::middleware(['auth', 'module.permission:entsorgung,view'])->group(function () {
    Route::get('/help', fn() => view('entsorgung::help'))->name('help');
    Route::get('/', [EntsorgungController::class, 'index'])->name('index');
});

// Schreibzugriff (Anlegen, Bearbeiten, Listen-Verwaltung)
Route::middleware(['auth', 'module.permission:entsorgung,edit'])->group(function () {
    Route::get('/create',         [EntsorgungController::class, 'create'])->name('create');
    Route::post('/',              [EntsorgungController::class, 'store'])->name('store');
    Route::get('/{eintrag}/edit', [EntsorgungController::class, 'edit'])->name('edit');
    Route::put('/{eintrag}',      [EntsorgungController::class, 'update'])->name('update');

    // Löschen: Route erfordert mind. edit; Controller prüft ob delete-Recht oder 1-Stunden-Frist
    Route::delete('/{eintrag}', [EntsorgungController::class, 'destroy'])->name('destroy');

    // Listen-Verwaltung
    Route::get('/listen/hersteller',         [EntsorgungListenController::class, 'herstellerIndex'])->name('listen.hersteller');
    Route::post('/listen/hersteller',        [EntsorgungListenController::class, 'herstellerStore'])->name('listen.hersteller.store');
    Route::delete('/listen/hersteller/{h}',  [EntsorgungListenController::class, 'herstellerDestroy'])->name('listen.hersteller.destroy');

    Route::get('/listen/typen',              [EntsorgungListenController::class, 'typenIndex'])->name('listen.typen');
    Route::post('/listen/typen',             [EntsorgungListenController::class, 'typenStore'])->name('listen.typen.store');
    Route::delete('/listen/typen/{typ}',     [EntsorgungListenController::class, 'typenDestroy'])->name('listen.typen.destroy');

    Route::get('/listen/gruende',            [EntsorgungListenController::class, 'gruendeIndex'])->name('listen.gruende');
    Route::post('/listen/gruende',           [EntsorgungListenController::class, 'gruendeStore'])->name('listen.gruende.store');
    Route::delete('/listen/gruende/{grund}', [EntsorgungListenController::class, 'gruendeDestroy'])->name('listen.gruende.destroy');
});
