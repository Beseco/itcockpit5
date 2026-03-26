<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Erinnerungsmails jede Minute prüfen und versenden
Schedule::command('reminders:send')->everyMinute();

// Kalender-Erinnerungen jede Minute prüfen
Schedule::command('calendar:send-reminders')->everyMinute();

// Applikationen-Revisionen täglich um 07:00 Uhr prüfen und E-Mails versenden
Schedule::command('applikationen:send-revision-reminders')->dailyAt('07:00');
