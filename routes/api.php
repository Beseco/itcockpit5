<?php

use App\Http\Controllers\Api\ApplikationApiController;
use App\Http\Controllers\Api\UserApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('/users',              [UserApiController::class, 'index']);
    Route::get('/users/{user}',       [UserApiController::class, 'show']);

    Route::get('/applikationen',      [ApplikationApiController::class, 'index']);
    Route::post('/applikationen',     [ApplikationApiController::class, 'store']);
    Route::get('/applikationen/{applikation}', [ApplikationApiController::class, 'show']);
    Route::put('/applikationen/{applikation}', [ApplikationApiController::class, 'update']);
    Route::delete('/applikationen/{applikation}', [ApplikationApiController::class, 'destroy']);
});
