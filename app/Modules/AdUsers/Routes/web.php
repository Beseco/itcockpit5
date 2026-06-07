<?php

use App\Modules\AdUsers\Http\Controllers\AdUserController;
use App\Modules\AdUsers\Http\Controllers\AdUserManageController;
use App\Modules\AdUsers\Http\Controllers\AdUserSettingsController;
use App\Modules\AdUsers\Http\Controllers\OffboardingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'module.permission:adusers,view'])->group(function () {
    Route::get('/help', fn() => view('adusers::help'))->name('help');
    Route::get('/',               [AdUserController::class, 'index'])->name('index');
    Route::get('/search-json',                         [AdUserController::class, 'searchJson'])->name('search-json');
    Route::get('/show/{user}',                        [AdUserController::class, 'show'])->name('show');
    Route::get('/show/{user}/compare/{target}',       [AdUserController::class, 'compareUser'])->name('compare-user');
    Route::get('/show/{user}/compare-ou',             [AdUserController::class, 'compareOu'])->name('compare-ou');
    Route::delete('/{user}',      [AdUserController::class, 'destroy'])->name('destroy');
    Route::post('/bulk-delete',   [AdUserController::class, 'bulkDestroy'])->name('bulk-delete');

    // Offboarding – spezifische Routen vor parametrisierten
    Route::get('/offboarding',                              [OffboardingController::class, 'index'])->name('offboarding.index');
    Route::get('/offboarding/create',                       [OffboardingController::class, 'create'])->name('offboarding.create');
    Route::post('/offboarding',                             [OffboardingController::class, 'store'])->name('offboarding.store');
    Route::get('/offboarding/{record}',                     [OffboardingController::class, 'show'])->name('offboarding.show');
    Route::post('/offboarding/{record}/send-email',         [OffboardingController::class, 'sendEmail'])->name('offboarding.send-email');
    Route::post('/offboarding/{record}/mark-deleted',       [OffboardingController::class, 'markDeleted'])->name('offboarding.mark-deleted');
    Route::post('/offboarding/{record}/upload',             [OffboardingController::class, 'upload'])->name('offboarding.upload');
    Route::get('/offboarding/{record}/download/{type}',     [OffboardingController::class, 'download'])->name('offboarding.download');
    Route::delete('/offboarding/{record}',                  [OffboardingController::class, 'destroy'])->name('offboarding.destroy');
});

Route::middleware(['auth', 'module.permission:adusers,config'])->group(function () {
    // Gruppenmanagement (schreibende LDAP-Operationen)
    Route::get('/groups/search',                                [AdUserManageController::class, 'searchGroups'])->name('groups.search');
    Route::post('/show/{user}/groups/add',                      [AdUserManageController::class, 'addGroup'])->name('groups.add');
    Route::post('/show/{user}/groups/remove',                   [AdUserManageController::class, 'removeGroup'])->name('groups.remove');
    Route::post('/show/{user}/groups/revert/{log}',             [AdUserManageController::class, 'revertChange'])->name('groups.revert');
});

Route::middleware(['auth', 'module.permission:adusers,config'])->group(function () {
    Route::get('/settings',                      [AdUserSettingsController::class, 'index'])->name('settings');
    Route::post('/settings',                     [AdUserSettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/test-connection',     [AdUserSettingsController::class, 'testConnection'])->name('settings.test-connection');
    Route::post('/settings/test-query',          [AdUserSettingsController::class, 'testQuery'])->name('settings.test-query');
});

Route::middleware(['auth', 'module.permission:adusers,sync'])->group(function () {
    Route::post('/sync', [AdUserSettingsController::class, 'runSync'])->name('sync');
});
