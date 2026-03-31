<?php

use App\Modules\Entsorgung\Http\Controllers\EntsorgungController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'module.permission:entsorgung,view'])->group(function () {
    Route::get('/',             [EntsorgungController::class, 'index'])->name('index');
    Route::get('/create',       [EntsorgungController::class, 'create'])->name('create');
    Route::post('/',            [EntsorgungController::class, 'store'])->name('store');
    Route::delete('/{eintrag}', [EntsorgungController::class, 'destroy'])->name('destroy');
});
