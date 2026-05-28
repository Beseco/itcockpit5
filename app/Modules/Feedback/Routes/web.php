<?php

use App\Modules\Feedback\Http\Controllers\FeedbackAdminController;
use App\Modules\Feedback\Http\Controllers\FeedbackController;
use Illuminate\Support\Facades\Route;

// Öffentliche Routen – kein Login erforderlich
Route::get('/', [FeedbackController::class, 'show'])->name('form');
Route::post('/', [FeedbackController::class, 'store'])->name('store')->middleware('throttle:5,60');
Route::get('/danke', [FeedbackController::class, 'thankYou'])->name('thank-you');

// Admin-Bereich – Authentifizierung + Berechtigung erforderlich
Route::middleware(['auth', 'module.permission:feedback,view'])->group(function () {
    Route::get('/admin',                          [FeedbackAdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/admin/bewertungen',              [FeedbackAdminController::class, 'index'])->name('admin.index');
    Route::delete('/admin/bewertungen/{feedback}',[FeedbackAdminController::class, 'destroy'])->name('admin.destroy');
    Route::get('/admin/kommentare',               [FeedbackAdminController::class, 'comments'])->name('admin.comments');
    Route::get('/admin/adusers',                  [FeedbackAdminController::class, 'adUserSearch'])->name('admin.adusers');
    Route::post('/admin/einladen',                [FeedbackAdminController::class, 'sendInvite'])->name('admin.invite');
});
