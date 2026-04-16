<?php

use App\Modules\SslCerts\Http\Controllers\SslCertsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'module.permission:sslcerts,view'])->group(function () {
    Route::get('/',         [SslCertsController::class, 'index'])->name('index');
    Route::get('/{cert}',   [SslCertsController::class, 'show'])->name('show');
    Route::get('/{cert}/download/{type}', [SslCertsController::class, 'download'])->name('download');
});

Route::middleware(['auth', 'module.permission:sslcerts,edit'])->group(function () {
    Route::get('/create',       [SslCertsController::class, 'create'])->name('create');
    Route::post('/',            [SslCertsController::class, 'store'])->name('store');
    Route::get('/{cert}/edit',  [SslCertsController::class, 'edit'])->name('edit');
    Route::put('/{cert}',       [SslCertsController::class, 'update'])->name('update');
});

Route::middleware(['auth', 'module.permission:sslcerts,delete'])->group(function () {
    Route::delete('/{cert}', [SslCertsController::class, 'destroy'])->name('destroy');
});
