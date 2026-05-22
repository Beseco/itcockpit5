<?php

use App\Modules\Schulen\Http\Controllers\DienstleistungenController;
use App\Modules\Schulen\Http\Controllers\EinstellungenController;
use App\Modules\Schulen\Http\Controllers\ExportController;
use App\Modules\Schulen\Http\Controllers\KontaktController;
use App\Modules\Schulen\Http\Controllers\MatrixController;
use App\Modules\Schulen\Http\Controllers\SchulenController;
use Illuminate\Support\Facades\Route;

// Spezifische Routen vor parametrisierten Routen

Route::middleware(['auth', 'module.permission:schulen,config'])->group(function () {
    Route::get('/einstellungen',                          [EinstellungenController::class, 'index'])->name('einstellungen');
    // Schultypen
    Route::post('/einstellungen/typen',                  [EinstellungenController::class, 'storeTyp'])->name('einstellungen.typen.store');
    Route::put('/einstellungen/typen/{schulTyp}',        [EinstellungenController::class, 'updateTyp'])->name('einstellungen.typen.update');
    Route::delete('/einstellungen/typen/{schulTyp}',     [EinstellungenController::class, 'destroyTyp'])->name('einstellungen.typen.destroy');
    // Kategorien (über Einstellungen)
    Route::post('/einstellungen/kategorien',             [EinstellungenController::class, 'storeKategorie'])->name('einstellungen.kategorien.store');
    Route::put('/einstellungen/kategorien/{kategorie}',  [EinstellungenController::class, 'updateKategorie'])->name('einstellungen.kategorien.update');
    Route::delete('/einstellungen/kategorien/{kategorie}',[EinstellungenController::class, 'destroyKategorie'])->name('einstellungen.kategorien.destroy');
});

Route::middleware(['auth', 'module.permission:schulen,edit'])->group(function () {
    // Dienstleistungen CRUD (vor /{schule} platziert)
    Route::get('/dienste/create',               [DienstleistungenController::class, 'create'])->name('dienste.create');
    Route::post('/dienste',                     [DienstleistungenController::class, 'store'])->name('dienste.store');
    Route::get('/dienste/{dienstleistung}/edit',[DienstleistungenController::class, 'edit'])->name('dienste.edit');
    Route::put('/dienste/{dienstleistung}',     [DienstleistungenController::class, 'update'])->name('dienste.update');
    Route::delete('/dienste/{dienstleistung}',  [DienstleistungenController::class, 'destroy'])->name('dienste.destroy');

    // Kategorien
    Route::post('/kategorien',                  [DienstleistungenController::class, 'storeKategorie'])->name('kategorien.store');
    Route::put('/kategorien/{kategorie}',       [DienstleistungenController::class, 'updateKategorie'])->name('kategorien.update');
    Route::delete('/kategorien/{kategorie}',    [DienstleistungenController::class, 'destroyKategorie'])->name('kategorien.destroy');

    // Schulen CRUD
    Route::get('/create',      [SchulenController::class, 'create'])->name('create');
    Route::post('/',           [SchulenController::class, 'store'])->name('store');
    Route::get('/{schule}/edit', [SchulenController::class, 'edit'])->name('edit');
    Route::put('/{schule}',    [SchulenController::class, 'update'])->name('update');

    // Kontakte (nested)
    Route::post('/{schule}/kontakte',                    [KontaktController::class, 'store'])->name('kontakte.store');
    Route::put('/{schule}/kontakte/{kontakt}',           [KontaktController::class, 'update'])->name('kontakte.update');
    Route::delete('/{schule}/kontakte/{kontakt}',        [KontaktController::class, 'destroy'])->name('kontakte.destroy');

    // Matrix-Zell-Update
    Route::put('/{schule}/dienste/{dienstleistung}',     [MatrixController::class, 'updateCell'])->name('matrix.update');
});

Route::middleware(['auth', 'module.permission:schulen,delete'])->group(function () {
    Route::delete('/{schule}', [SchulenController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth', 'module.permission:schulen,view'])->group(function () {
    Route::get('/',            [MatrixController::class, 'index'])->name('matrix');
    Route::get('/protokoll',   [MatrixController::class, 'protokoll'])->name('protokoll');
    Route::get('/liste',       [SchulenController::class, 'index'])->name('index');
    Route::get('/vze',         [SchulenController::class, 'vze'])->name('vze');
    Route::get('/dienste',     [DienstleistungenController::class, 'index'])->name('dienste.index');
    Route::get('/dienste/{dienstleistung}', [DienstleistungenController::class, 'show'])->name('dienste.show');
    Route::get('/export/{type}/{format}',   [ExportController::class, 'download'])->name('export')
        ->where('type',   'matrix|dienstleistungen|schulen-liste')
        ->where('format', 'pdf|xlsx|docx');
    Route::get('/{schule}',    [SchulenController::class, 'show'])->name('show');
});
