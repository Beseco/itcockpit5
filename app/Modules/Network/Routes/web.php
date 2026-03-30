<?php

use App\Modules\Network\Http\Controllers\VlanController;
use App\Modules\Network\Http\Controllers\IpAddressController;
use App\Modules\Network\Http\Controllers\VlanCommentController;
use App\Modules\Network\Http\Controllers\SearchController;
use App\Modules\Network\Http\Controllers\ExportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Network Module Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for the Network module.
| These routes are loaded by the NetworkServiceProvider.
|
| Note: Routes are automatically prefixed with 'network' by ModuleServiceProvider
|
*/

// VLAN routes - protected with module.network.view permission
Route::middleware(['auth', 'module.permission:network,view'])->group(function () {
    Route::get('/', [VlanController::class, 'index'])->name('index');
    Route::get('/export', [ExportController::class, 'export'])->name('export');

    // Global search routes
    Route::get('/search', [SearchController::class, 'index'])->name('search');
    Route::get('/search/ajax', [SearchController::class, 'search'])->name('search.ajax');
    
    // VLAN comment routes - view permission required, ownership checked in controller
    Route::post('/vlans/{vlan}/comments', [VlanCommentController::class, 'store'])->name('vlans.comments.store');
    Route::delete('/comments/{comment}', [VlanCommentController::class, 'destroy'])->name('comments.destroy');
});

// VLAN management routes - protected with module.network.edit permission
Route::middleware(['auth', 'module.permission:network,edit'])->group(function () {
    // IMPORTANT: Define specific routes BEFORE parameterized routes to avoid conflicts
    Route::get('/vlans/create', [VlanController::class, 'create'])->name('vlans.create');
    Route::post('/vlans', [VlanController::class, 'store'])->name('vlans.store');
    
    // IP address update route
    Route::put('/ip-addresses/{ipAddress}', [IpAddressController::class, 'update'])->name('ip-addresses.update');
});

// VLAN detail and edit routes - must come AFTER /vlans/create to avoid route conflicts
Route::middleware(['auth', 'module.permission:network,view'])->group(function () {
    Route::get('/vlans/{vlan}', [VlanController::class, 'show'])->name('vlans.show');
    Route::get('/vlans/{vlan}/ips', [VlanController::class, 'ips'])->name('vlans.ips');
    Route::get('/vlans/{vlan}/ips/search', [VlanController::class, 'ipsSearch'])->name('vlans.ips.search');
    
    // IP address detail route
    Route::get('/ip-addresses/{ipAddress}', [IpAddressController::class, 'show'])->name('ip-addresses.show');
});

Route::middleware(['auth', 'module.permission:network,edit'])->group(function () {
    Route::get('/vlans/{vlan}/edit', [VlanController::class, 'edit'])->name('vlans.edit');
    Route::put('/vlans/{vlan}', [VlanController::class, 'update'])->name('vlans.update');
    Route::delete('/vlans/{vlan}', [VlanController::class, 'destroy'])->name('vlans.destroy');
});
