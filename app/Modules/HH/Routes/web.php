<?php

use App\Modules\HH\Http\Controllers\AccountController;
use App\Modules\HH\Http\Controllers\AuditController;
use App\Modules\HH\Http\Controllers\BudgetPositionController;
use App\Modules\HH\Http\Controllers\BudgetYearController;
use App\Modules\HH\Http\Controllers\BudgetYearVersionController;
use App\Modules\HH\Http\Controllers\CostCenterController;
use App\Modules\HH\Http\Controllers\DashboardController;
use App\Modules\HH\Http\Controllers\ExportController;
use App\Modules\HH\Http\Controllers\ImportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| HH Module Web Routes
|--------------------------------------------------------------------------
|
| Routes are automatically prefixed with 'hh' by ModuleServiceProvider.
| All routes require authentication.
|
*/

Route::middleware(['auth', 'can:hh.view'])->group(function () {
    // Hilfe
    Route::get('/help', fn() => view('hh::help'))->name('help');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/dashboard/{budgetYear}', [DashboardController::class, 'show'])->name('dashboard.show');
    Route::get('/dashboard/{budgetYear}/search', [DashboardController::class, 'search'])->name('dashboard.search');
    Route::get('/dashboard/{budgetYear}/cost-center/{costCenter}/account/{account}', [DashboardController::class, 'accountPositions'])->name('dashboard.account-positions');

    // Budget Years
    Route::get('/budget-years', [BudgetYearController::class, 'index'])->name('budget-years.index');
    Route::post('/budget-years', [BudgetYearController::class, 'store'])->name('budget-years.store');
    Route::get('/budget-years/{budgetYear}', [BudgetYearController::class, 'show'])->name('budget-years.show');
    Route::put('/budget-years/{budgetYear}', [BudgetYearController::class, 'update'])->name('budget-years.update');
    Route::get('/budget-years/{budgetYear}/confirm-delete', [BudgetYearController::class, 'confirmDelete'])->name('budget-years.confirm-delete');
    Route::delete('/budget-years/{budgetYear}', [BudgetYearController::class, 'destroy'])->name('budget-years.destroy');
    Route::post('/budget-years/{budgetYear}/transition', [BudgetYearController::class, 'transition'])->name('budget-years.transition');
    Route::post('/budget-years/{budgetYear}/carry-over-recurring', [BudgetYearController::class, 'carryOverRecurring'])->name('budget-years.carry-over-recurring');

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

    // Import
    Route::get('/import', [ImportController::class, 'index'])->name('import.index');
    Route::post('/import', [ImportController::class, 'store'])->name('import.store');

    // API: Positionen für Bestellformular
    Route::get('/api/positions-for-order', [DashboardController::class, 'positionsForOrder'])->name('api.positions-for-order');
});
