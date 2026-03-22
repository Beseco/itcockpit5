<?php

use App\Modules\Example\Http\Controllers\ExampleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Example Module Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'module.permission:example,view'])
    ->group(function () {
        Route::get('/', [ExampleController::class, 'index'])->name('index');
    });
