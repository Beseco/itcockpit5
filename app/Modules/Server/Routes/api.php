<?php

use App\Modules\Server\Http\Controllers\Api\ServerApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/',          [ServerApiController::class, 'index']);
    Route::post('/',         [ServerApiController::class, 'store']);
    Route::get('/{server}',  [ServerApiController::class, 'show']);
    Route::put('/{server}',  [ServerApiController::class, 'update']);
    Route::delete('/{server}', [ServerApiController::class, 'destroy']);
});
