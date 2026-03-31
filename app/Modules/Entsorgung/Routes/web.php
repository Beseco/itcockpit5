<?php

use App\Modules\Entsorgung\Http\Controllers\EntsorgungController;
use App\Modules\Entsorgung\Http\Controllers\EntsorgungListenController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'module.permission:entsorgung,view'])->group(function () {
    Route::get('/',             [EntsorgungController::class, 'index'])->name('index');
    Route::get('/create',       [EntsorgungController::class, 'create'])->name('create');
    Route::post('/',            [EntsorgungController::class, 'store'])->name('store');
    Route::delete('/{eintrag}', [EntsorgungController::class, 'destroy'])->name('destroy');

    // Listen-Verwaltung (Gerätetypen & Entsorgungsgründe)
    Route::get('/listen/typen',             [EntsorgungListenController::class, 'typenIndex'])->name('listen.typen');
    Route::post('/listen/typen',            [EntsorgungListenController::class, 'typenStore'])->name('listen.typen.store');
    Route::delete('/listen/typen/{typ}',    [EntsorgungListenController::class, 'typenDestroy'])->name('listen.typen.destroy');

    Route::get('/listen/gruende',            [EntsorgungListenController::class, 'gruendeIndex'])->name('listen.gruende');
    Route::post('/listen/gruende',           [EntsorgungListenController::class, 'gruendeStore'])->name('listen.gruende.store');
    Route::delete('/listen/gruende/{grund}', [EntsorgungListenController::class, 'gruendeDestroy'])->name('listen.gruende.destroy');
});
