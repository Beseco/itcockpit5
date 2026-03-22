<?php

use App\Modules\HH\Http\Controllers\AccountController;
use App\Modules\HH\Http\Controllers\AuditController;
use App\Modules\HH\Http\Controllers\BudgetPositionController;
use App\Modules\HH\Http\Controllers\BudgetYearController;
use App\Modules\HH\Http\Controllers\BudgetYearVersionController;
use App\Modules\HH\Http\Controllers\CostCenterController;
use App\Modules\HH\Http\Controllers\DashboardController;
use App\Modules\HH\Http\Controllers\ExportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| HH Module API Routes
|--------------------------------------------------------------------------
|
| Routes are prefixed with 'api/hh' and protected with Sanctum auth.
|
*/

Route::middleware('auth:sanctum')->prefix('hh')->name('api.hh.')->group(function () {
    // Dashboard
    Route::get('/dashboard/{budgetYear}', [DashboardController::class, 'show'])->name('dashboard.show');

    // Budget Years
    Route::get('/budget-years', [BudgetYearController::class, 'index'])->name('budget-years.index');
    Route::post('/budget-years', [BudgetYearController::class, 'store'])->name('budget-years.store');
    Route::get('/budget-years/{budgetYear}', [BudgetYearController::class, 'show'])->name('budget-years.show');
    Route::post('/budget-years/{budgetYear}/transition', [BudgetYearController::class, 'transition'])->name('budget-years.transition');

    // Budget Year Versions
    Route::post('/budget-years/{budgetYear}/versions', [BudgetYearVersionController::class, 'store'])->name('budget-years.versions.store');

    // Budget Positions
    Route::get('/versions/{version}/positions', [BudgetPositionController::class, 'index'])->name('versions.positions.index');
    Route::post('/positions', [BudgetPositionController::class, 'store'])->name('positions.store');
    Route::put('/positions/{position}', [BudgetPositionController::class, 'update'])->name('positions.update');
    Route::delete('/positions/{position}', [BudgetPositionController::class, 'destroy'])->name('positions.destroy');

    // Cost Centers
    Route::get('/cost-centers', [CostCenterController::class, 'index'])->name('cost-centers.index');
    Route::post('/cost-centers', [CostCenterController::class, 'store'])->name('cost-centers.store');
    Route::put('/cost-centers/{costCenter}', [CostCenterController::class, 'update'])->name('cost-centers.update');
    Route::delete('/cost-centers/{costCenter}', [CostCenterController::class, 'destroy'])->name('cost-centers.destroy');

    // Accounts (Sachkonten)
    Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
    Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
    Route::put('/accounts/{account}', [AccountController::class, 'update'])->name('accounts.update');
    Route::delete('/accounts/{account}', [AccountController::class, 'destroy'])->name('accounts.destroy');

    // Audit Log
    Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');

    // Export
    Route::get('/budget-years/{budgetYear}/export/excel', [ExportController::class, 'excel'])->name('budget-years.export.excel');
    Route::get('/budget-years/{budgetYear}/export/pdf', [ExportController::class, 'pdf'])->name('budget-years.export.pdf');
});
