<?php

use App\Modules\Stellenplan\Http\Controllers\StellenplanController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/', [StellenplanController::class, 'index'])->name('index');
});
