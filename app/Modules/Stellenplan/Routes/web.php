<?php

use App\Modules\Stellenplan\Http\Controllers\ExportController;
use App\Modules\Stellenplan\Http\Controllers\StellenplanController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/help', fn() => view('stellenplan::help'))->name('help');
    Route::get('/',             [StellenplanController::class, 'index'])->name('index');
    Route::get('/export/xlsx',  [ExportController::class, 'xlsx'])->name('export.xlsx');
    Route::get('/export/pdf',   [ExportController::class, 'pdf'])->name('export.pdf');
});
