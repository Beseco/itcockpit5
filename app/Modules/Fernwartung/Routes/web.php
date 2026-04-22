<?php

use App\Modules\Fernwartung\Http\Controllers\FernwartungController;
use App\Modules\Fernwartung\Http\Controllers\FernwartungToolController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'module.permission:fernwartung,view'])->group(function () {

    Route::get('/help', fn() => view('fernwartung::help'))->name('help');
    Route::get('/',           [FernwartungController::class, 'index'])->name('index');
    Route::get('/create',     [FernwartungController::class, 'create'])->name('create');
    Route::post('/',          [FernwartungController::class, 'store'])->name('store');
    Route::get('/{fw}/edit',  [FernwartungController::class, 'edit'])->name('edit');
    Route::put('/{fw}',       [FernwartungController::class, 'update'])->name('update');
    Route::delete('/{fw}',    [FernwartungController::class, 'destroy'])->name('destroy');
    Route::post('/{fw}/ende', [FernwartungController::class, 'setEnde'])->name('ende');

    Route::get('/tools',          [FernwartungToolController::class, 'index'])->name('tools.index');
    Route::post('/tools',         [FernwartungToolController::class, 'store'])->name('tools.store');
    Route::delete('/tools/{tool}', [FernwartungToolController::class, 'destroy'])->name('tools.destroy');
});
