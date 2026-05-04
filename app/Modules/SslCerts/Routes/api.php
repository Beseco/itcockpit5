<?php

use App\Modules\SslCerts\Http\Controllers\Api\SslCertApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/',                        [SslCertApiController::class, 'index']);
    Route::get('/{sslCertificate}',        [SslCertApiController::class, 'show']);
});
