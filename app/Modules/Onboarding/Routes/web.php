<?php

use App\Modules\Onboarding\Http\Controllers\OnboardingController;
use App\Modules\Onboarding\Http\Controllers\OnboardingRecordController;
use App\Modules\Onboarding\Http\Controllers\OnboardingSettingsController;
use App\Modules\Onboarding\Http\Controllers\OnboardingTodoController;
use App\Modules\Onboarding\Http\Controllers\VorlageController;
use Illuminate\Support\Facades\Route;

// Einstellungen
Route::middleware(['auth', 'module.permission:onboarding,config'])->group(function () {
    Route::get('/settings',              [OnboardingSettingsController::class, 'index'])->name('settings');
    Route::put('/settings',              [OnboardingSettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/test-ldap',     [OnboardingSettingsController::class, 'testConnection'])->name('settings.test-ldap');
    Route::post('/settings/test-groups',   [OnboardingSettingsController::class, 'testGroupSearch'])->name('settings.test-groups');
    Route::post('/settings/test-exchange', [OnboardingSettingsController::class, 'testExchange'])->name('settings.test-exchange');
    Route::post('/settings/test-smb',      [OnboardingSettingsController::class, 'testSmb'])->name('settings.test-smb');
});

// Vorlagen-CRUD
Route::middleware(['auth', 'module.permission:onboarding,edit'])->group(function () {
    Route::get('/vorlagen',              [VorlageController::class, 'index'])->name('vorlagen.index');
    Route::get('/vorlagen/create',       [VorlageController::class, 'create'])->name('vorlagen.create');
    Route::post('/vorlagen',             [VorlageController::class, 'store'])->name('vorlagen.store');
    Route::get('/vorlagen/{vorlage}/edit', [VorlageController::class, 'edit'])->name('vorlagen.edit');
    Route::put('/vorlagen/{vorlage}',    [VorlageController::class, 'update'])->name('vorlagen.update');
    Route::delete('/vorlagen/{vorlage}', [VorlageController::class, 'destroy'])->name('vorlagen.destroy');
    Route::post('/vorlagen/{vorlage}/clone', [VorlageController::class, 'clone'])->name('vorlagen.clone');
    Route::get('/vorlagen/search-groups', [VorlageController::class, 'searchGroups'])->name('vorlagen.search-groups');
});

// Onboarding-Workflow
Route::middleware(['auth', 'module.permission:onboarding,edit'])->group(function () {
    Route::get('/neu',                   [OnboardingController::class, 'create'])->name('create');
    Route::post('/neu',                  [OnboardingController::class, 'store'])->name('store');
    Route::post('/preview',              [OnboardingController::class, 'preview'])->name('preview');
});

// History
Route::middleware(['auth', 'module.permission:onboarding,view'])->group(function () {
    Route::get('/',                      [OnboardingController::class, 'index'])->name('index');
    Route::get('/history',               [OnboardingRecordController::class, 'index'])->name('records.index');
    Route::get('/records/{record}',      [OnboardingRecordController::class, 'show'])->name('records.show');
});

// Todo-Workflow (auth erforderlich)
Route::middleware(['auth', 'module.permission:onboarding,edit'])->group(function () {
    Route::get('/todo/{token}',                 [OnboardingTodoController::class, 'show'])->name('todo.show');
    Route::post('/todo/{token}/check',          [OnboardingTodoController::class, 'checkItem'])->name('todo.check');
    Route::post('/todo/{token}/mail-test',      [OnboardingTodoController::class, 'sendMailTest'])->name('todo.mail-test');
    Route::get('/todo/{token}/status',          [OnboardingTodoController::class, 'status'])->name('todo.status');
    Route::post('/todo/{token}/complete',       [OnboardingTodoController::class, 'complete'])->name('todo.complete');
    Route::get('/todo/{token}/completed',       [OnboardingTodoController::class, 'completed'])->name('todo.completed');
});

// Mail-Verifikationslink (kein Login – neuer Benutzer klickt den Link)
Route::get('/todo/{token}/verify-mail/{mailToken}', [OnboardingTodoController::class, 'verifyMail'])
    ->name('todo.verify-mail');
