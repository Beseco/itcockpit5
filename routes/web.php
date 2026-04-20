<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\ApplikationController;
use App\Http\Controllers\ApplikationExportController;
use App\Http\Controllers\AufgabeController;
use App\Http\Controllers\AufgabenExportController;
use App\Http\Controllers\AufgabeZuweisungController;
use App\Http\Controllers\AbteilungController;
use App\Http\Controllers\AbteilungRevisionController;
use App\Http\Controllers\GruppeController;
use App\Http\Controllers\ReminderMailController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\AccountCodeController;
use App\Http\Controllers\CostCenterController;
use App\Http\Controllers\AnsprechpartnerFunktionController;
use App\Http\Controllers\DienstleisterController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PersonalController;
use App\Http\Controllers\StellenbeschreibungController;
use App\Http\Controllers\StelleController;
use App\Http\Controllers\ApplikationRevisionSettingsController;
use App\Http\Controllers\AbteilungRevisionSettingsController;
use App\Http\Controllers\RevisionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Revisionsformular – kein Login erforderlich (tokenbasiert)
Route::get('/revision/{token}',  [RevisionController::class, 'show'])->name('revision.show');
Route::post('/revision/{token}', [RevisionController::class, 'submit'])->name('revision.submit');

// Abteilungs-Revision – kein Login erforderlich (tokenbasiert)
Route::get('/abteilung-revision/approve/{approvalToken}', [AbteilungRevisionController::class, 'approve'])->name('abteilung-revision.approve');
Route::get('/abteilung-revision/{token}', [AbteilungRevisionController::class, 'show'])->name('abteilung-revision.show');
Route::get('/abteilung-revision/{token}/app/{appId}', [AbteilungRevisionController::class, 'showApp'])->name('abteilung-revision.app');
Route::post('/abteilung-revision/{token}/app/{appId}', [AbteilungRevisionController::class, 'submitApp'])->name('abteilung-revision.app.submit');
Route::get('/abteilung-revision/{token}/neue-app', [AbteilungRevisionController::class, 'showNewApp'])->name('abteilung-revision.neue-app');
Route::post('/abteilung-revision/{token}/neue-app', [AbteilungRevisionController::class, 'submitNewApp'])->name('abteilung-revision.neue-app.submit');
Route::get('/abteilung-revision/{token}/fertig', [AbteilungRevisionController::class, 'done'])->name('abteilung-revision.fertig');

// Offboarding – öffentliche Token-Routen (kein Login)
Route::get('/offboarding/bestaetigung/{token}',           [\App\Modules\AdUsers\Http\Controllers\OffboardingController::class, 'confirmShow'])->name('offboarding.confirm');
Route::post('/offboarding/bestaetigung/{token}',          [\App\Modules\AdUsers\Http\Controllers\OffboardingController::class, 'confirmSubmit'])->name('offboarding.confirm.submit');
Route::get('/offboarding/admin/deaktivierung/{token}',    [\App\Modules\AdUsers\Http\Controllers\OffboardingController::class, 'adminDeaktivierungShow'])->name('offboarding.admin.deaktivierung');
Route::post('/offboarding/admin/deaktivierung/{token}',   [\App\Modules\AdUsers\Http\Controllers\OffboardingController::class, 'adminDeaktivierungSubmit'])->name('offboarding.admin.deaktivierung.submit');
Route::get('/offboarding/admin/loeschung/{token}',        [\App\Modules\AdUsers\Http\Controllers\OffboardingController::class, 'adminLoeschungShow'])->name('offboarding.admin.loeschung');
Route::post('/offboarding/admin/loeschung/{token}',       [\App\Modules\AdUsers\Http\Controllers\OffboardingController::class, 'adminLoeschungSubmit'])->name('offboarding.admin.loeschung.submit');

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

    // Stellenbeschreibungen
    Route::resource('stellenbeschreibungen', StellenbeschreibungController::class, [
        'parameters' => ['stellenbeschreibungen' => 'stellenbeschreibung'],
    ]);
    Route::get('/stellenbeschreibungen/{stellenbeschreibung}/arbeitsvorgaenge/create', [StellenbeschreibungController::class, 'createAv'])->name('sb.av.create');
    Route::post('/stellenbeschreibungen/{stellenbeschreibung}/arbeitsvorgaenge', [StellenbeschreibungController::class, 'storeAv'])->name('sb.av.store');
    Route::get('/stellenbeschreibungen/{stellenbeschreibung}/arbeitsvorgaenge/{av}/edit', [StellenbeschreibungController::class, 'editAv'])->name('sb.av.edit');
    Route::put('/stellenbeschreibungen/{stellenbeschreibung}/arbeitsvorgaenge/{av}', [StellenbeschreibungController::class, 'updateAv'])->name('sb.av.update');
    Route::delete('/stellenbeschreibungen/{stellenbeschreibung}/arbeitsvorgaenge/{av}', [StellenbeschreibungController::class, 'destroyAv'])->name('sb.av.destroy');

    Route::get('/personal', [PersonalController::class, 'index'])->name('personal.index');
    Route::post('/personal/avatar', [PersonalController::class, 'uploadAvatar'])->name('personal.avatar');

    // Aufgaben / Rollen & Aufgaben
    Route::get('/aufgaben/export/xlsx', [AufgabenExportController::class, 'xlsx'])->name('aufgaben.export.xlsx');
    Route::get('/aufgaben/export/pdf',  [AufgabenExportController::class, 'pdf'])->name('aufgaben.export.pdf');
    Route::resource('aufgaben', AufgabeController::class, [
        'parameters' => ['aufgaben' => 'aufgabe'],
    ]);
    Route::patch('/aufgaben-zuweisungen/{zuweisung}', [AufgabeZuweisungController::class, 'update'])
        ->name('aufgaben-zuweisungen.update');

    // Announcement management routes
    Route::resource('announcements', AnnouncementController::class);
    Route::post('/announcements/{announcement}/mark-as-fixed', [AnnouncementController::class, 'markAsFixed'])->name('announcements.mark-as-fixed');

    // Bestellverwaltung
    Route::resource('orders', OrderController::class);

    // Dienstleister / Lieferanten
    Route::resource('dienstleister', DienstleisterController::class);
    Route::post('/dienstleister-funktionen', [AnsprechpartnerFunktionController::class, 'store'])->name('dienstleister-funktionen.store');
    Route::delete('/dienstleister-funktionen/{funktion}', [AnsprechpartnerFunktionController::class, 'destroy'])->name('dienstleister-funktionen.destroy');

    // Abteilungen / Sachgebiete
    Route::post('/abteilungen/{abteilung}/revision-mail-test', [AbteilungController::class, 'sendRevisionMailTest'])
        ->middleware('can:abteilungen.edit')
        ->name('abteilungen.revision-mail-test');
    Route::get('/abteilungen/revision-settings', [AbteilungRevisionSettingsController::class, 'index'])
        ->middleware('can:abteilungen.edit')
        ->name('abteilungen.revision-settings');
    Route::put('/abteilungen/revision-settings', [AbteilungRevisionSettingsController::class, 'update'])
        ->middleware('can:abteilungen.edit')
        ->name('abteilungen.revision-settings.update');
    Route::resource('abteilungen', AbteilungController::class, [
        'parameters' => ['abteilungen' => 'abteilung'],
    ])->except(['show']);

    // Applikationen
    Route::get('/applikationen/export/xlsx', [ApplikationExportController::class, 'xlsx'])->name('applikationen.export.xlsx');
    Route::get('/applikationen/export/pdf',  [ApplikationExportController::class, 'pdf'])->name('applikationen.export.pdf');

    Route::get('/applikationen/revision-settings', [ApplikationRevisionSettingsController::class, 'index'])
        ->middleware('can:applikationen.edit')
        ->name('applikationen.revision-settings');
    Route::put('/applikationen/revision-settings', [ApplikationRevisionSettingsController::class, 'update'])
        ->middleware('can:applikationen.edit')
        ->name('applikationen.revision-settings.update');

    Route::resource('applikationen', ApplikationController::class, [
        'parameters' => ['applikationen' => 'applikation'],
    ])->except(['show']);

    // Erinnerungsmails
    Route::resource('reminders', ReminderMailController::class)->except(['show']);
    Route::post('/reminders/{reminder}/toggle', [ReminderMailController::class, 'toggleStatus'])->name('reminders.toggle');
    Route::post('/reminders/{reminder}/test', [ReminderMailController::class, 'sendTest'])->name('reminders.test');
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
