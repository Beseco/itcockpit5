<?php

use App\Modules\Calendar\Http\Controllers\CalendarController;
use App\Modules\Calendar\Http\Controllers\EventApiController;
use App\Modules\Calendar\Http\Controllers\EventController;
use App\Modules\Calendar\Http\Controllers\IcsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'module.permission:calendar,view'])->group(function () {
    Route::get('/help', fn() => view('calendar::help'))->name('help');
    Route::get('/', [CalendarController::class, 'index'])->name('index');
    Route::get('/events', [EventApiController::class, 'events'])->name('events');
    Route::post('/events', [EventController::class, 'store'])->name('events.store');
    Route::put('/events/{event}', [EventController::class, 'update'])->name('events.update');
    Route::delete('/events/{event}', [EventController::class, 'destroy'])->name('events.destroy');
    Route::post('/ics-token', [CalendarController::class, 'generateIcsToken'])->name('ics.generate');
});

// ICS-Feed: kein Auth, token-basiert
Route::get('/ics/{token}', [IcsController::class, 'feed'])->name('ics');
