<?php

use App\Modules\Onboarding\Http\Controllers\OnboardingController;
use App\Modules\Onboarding\Http\Controllers\OnboardingRecordController;
use App\Modules\Onboarding\Http\Controllers\OnboardingSettingsController;
use App\Modules\Onboarding\Http\Controllers\VorlageController;
use Illuminate\Support\Facades\Route;

// Einstellungen
Route::middleware(['auth', 'module.permission:onboarding,config'])->group(function () {
    Route::get('/settings',              [OnboardingSettingsController::class, 'index'])->name('settings');
    Route::put('/settings',              [OnboardingSettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/test-ldap',   [OnboardingSettingsController::class, 'testConnection'])->name('settings.test-ldap');
});

// Vorlagen-CRUD
Route::middleware(['auth', 'module.permission:onboarding,edit'])->group(function () {
    Route::get('/vorlagen',              [VorlageController::class, 'index'])->name('vorlagen.index');
    Route::get('/vorlagen/create',       [VorlageController::class, 'create'])->name('vorlagen.create');
    Route::post('/vorlagen',             [VorlageController::class, 'store'])->name('vorlagen.store');
    Route::get('/vorlagen/{vorlage}/edit', [VorlageController::class, 'edit'])->name('vorlagen.edit');
    Route::put('/vorlagen/{vorlage}',    [VorlageController::class, 'update'])->name('vorlagen.update');
    Route::delete('/vorlagen/{vorlage}', [VorlageController::class, 'destroy'])->name('vorlagen.destroy');
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
