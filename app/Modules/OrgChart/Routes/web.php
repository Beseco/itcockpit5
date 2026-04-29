<?php

use App\Modules\OrgChart\Http\Controllers\OrgChartController;
use App\Modules\OrgChart\Http\Controllers\OrgInterfaceController;
use App\Modules\OrgChart\Http\Controllers\OrgNodeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'module.permission:orgchart,view'])->group(function () {
    Route::get('/', [OrgChartController::class, 'index'])->name('index');
    Route::get('/help', fn() => view('orgchart::help'))->name('help');
    Route::get('/create', [OrgChartController::class, 'create'])->name('create');
    Route::get('/{version}', [OrgChartController::class, 'show'])->name('show');
    Route::get('/{version}/export-pdf', [OrgChartController::class, 'exportPdf'])->name('export-pdf');
});

Route::middleware(['auth', 'module.permission:orgchart,edit'])->group(function () {
    Route::post('/', [OrgChartController::class, 'store'])->name('store');
    Route::get('/{version}/edit', [OrgChartController::class, 'edit'])->name('edit');
    Route::put('/{version}', [OrgChartController::class, 'update'])->name('update');
    Route::delete('/{version}', [OrgChartController::class, 'destroy'])->name('destroy');
    Route::post('/{version}/duplicate', [OrgChartController::class, 'duplicate'])->name('duplicate');

    Route::post('/{version}/nodes', [OrgNodeController::class, 'store'])->name('nodes.store');
    Route::put('/{version}/nodes/{node}', [OrgNodeController::class, 'update'])->name('nodes.update');
    Route::delete('/{version}/nodes/{node}', [OrgNodeController::class, 'destroy'])->name('nodes.destroy');
    Route::post('/{version}/nodes/{node}/move-up', [OrgNodeController::class, 'moveUp'])->name('nodes.move-up');
    Route::post('/{version}/nodes/{node}/move-down', [OrgNodeController::class, 'moveDown'])->name('nodes.move-down');

    Route::post('/{version}/interfaces', [OrgInterfaceController::class, 'store'])->name('interfaces.store');
    Route::delete('/{version}/interfaces/{iface}', [OrgInterfaceController::class, 'destroy'])->name('interfaces.destroy');
});
