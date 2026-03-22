<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\ApplikationController;
use App\Http\Controllers\AufgabeController;
use App\Http\Controllers\GruppeController;
use App\Http\Controllers\ReminderMailController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\AccountCodeController;
use App\Http\Controllers\CostCenterController;
use App\Http\Controllers\DienstleisterController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PersonalController;
use App\Http\Controllers\StelleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
})->middleware('auth');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // User management routes
    Route::resource('users', UserController::class);
    Route::post('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
    Route::post('/users/{user}/impersonate', [ImpersonationController::class, 'impersonate'])->name('users.impersonate');
    Route::post('/impersonate/stop', [ImpersonationController::class, 'stop'])->name('impersonate.stop');

    // Role management routes
    Route::resource('roles', RoleController::class)->except(['show']);

    // Gruppenverwaltung
    Route::resource('gruppen', GruppeController::class)->parameters(['gruppen' => 'gruppe'])->except(['show']);

    // Stellen
    Route::resource('stellen', StelleController::class, [
        'parameters' => ['stellen' => 'stelle'],
    ]);
    Route::get('/stellen/{stelle}/arbeitsvorgaenge/{av}/edit', [StelleController::class, 'editArbeitsvorgang'])->name('stellen.av.edit');
    Route::put('/stellen/{stelle}/arbeitsvorgaenge/{av}', [StelleController::class, 'updateArbeitsvorgang'])->name('stellen.av.update');
    Route::get('/personal', [PersonalController::class, 'index'])->name('personal.index');

    // Aufgaben / Rollen & Aufgaben
    Route::resource('aufgaben', AufgabeController::class, [
        'parameters' => ['aufgaben' => 'aufgabe'],
    ])->except(['show']);

    // Announcement management routes
    Route::resource('announcements', AnnouncementController::class);
    Route::post('/announcements/{announcement}/mark-as-fixed', [AnnouncementController::class, 'markAsFixed'])->name('announcements.mark-as-fixed');

    // Bestellverwaltung
    Route::resource('orders', OrderController::class);

    // Dienstleister / Lieferanten
    Route::resource('dienstleister', DienstleisterController::class);

    // Applikationen
    Route::resource('applikationen', ApplikationController::class, [
        'parameters' => ['applikationen' => 'applikation'],
    ])->except(['show']);

    // Erinnerungsmails
    Route::resource('reminders', ReminderMailController::class)->except(['show']);
    Route::post('/reminders/{reminder}/toggle', [ReminderMailController::class, 'toggleStatus'])->name('reminders.toggle');
    Route::get('/reminders-log', [ReminderMailController::class, 'log'])->name('reminders.log');

    // Kostenstellen & Sachkonten
    Route::resource('cost-centers', CostCenterController::class);
    Route::resource('account-codes', AccountCodeController::class);

    // Audit log routes
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit-logs.show');
    
    // Module management routes (requires base.modules.manage permission)
    Route::middleware('can:base.modules.manage')->group(function () {
        Route::get('/modules', [ModuleController::class, 'index'])->name('modules.index');
        Route::get('/modules/{module}/edit', [ModuleController::class, 'edit'])->name('modules.edit');
        Route::patch('/modules/{module}', [ModuleController::class, 'update'])->name('modules.update');
        Route::post('/modules/{module}/activate', [ModuleController::class, 'activate'])->name('modules.activate');
        Route::post('/modules/{module}/deactivate', [ModuleController::class, 'deactivate'])->name('modules.deactivate');
    });
});

require __DIR__.'/auth.php';
