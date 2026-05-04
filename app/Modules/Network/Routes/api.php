<?php

use App\Modules\Network\Http\Controllers\Api\IpAddressApiController;
use App\Modules\Network\Http\Controllers\Api\VlanApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/vlans',             [VlanApiController::class, 'index']);
    Route::get('/vlans/{vlan}',      [VlanApiController::class, 'show']);

    Route::get('/ips',               [IpAddressApiController::class, 'index']);
    Route::get('/ips/{ipAddress}',   [IpAddressApiController::class, 'show']);
    Route::put('/ips/{ipAddress}',   [IpAddressApiController::class, 'update']);
});
