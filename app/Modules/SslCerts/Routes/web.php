<?php

use App\Modules\SslCerts\Http\Controllers\SslCertsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'module.permission:sslcerts,view'])->group(function () {
    Route::get('/',                              [SslCertsController::class, 'index'])->name('index');
    Route::get('/create',                        [SslCertsController::class, 'create'])->name('create');
    Route::post('/',                             [SslCertsController::class, 'store'])->name('store');
    Route::get('/{cert}',                        [SslCertsController::class, 'show'])->name('show');
    Route::delete('/{cert}',                     [SslCertsController::class, 'destroy'])->name('destroy');
    Route::get('/{cert}/download/{type}',        [SslCertsController::class, 'download'])->name('download');
});
