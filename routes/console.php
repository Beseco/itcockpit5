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

// Applikationen-Revisions-Digest stündlich prüfen (Versand nur wenn konfigurierter Wochentag/Stunde/Intervall passt)
Schedule::command('applikationen:send-revision-digest')->hourly();

// Abteilungs-Revisions-Erinnerung stündlich prüfen (Versand nur wenn konfigurierter Wochentag/Stunde/Intervall passt)
Schedule::command('abteilungen:send-revision-digest')->hourly();

// Ticket-Scores berechnen und E-Mails versenden (jeden Freitag um 12:00 Uhr)
Schedule::command('tickets:calculate-scores')->weeklyOn(5, '12:00');

// SSL-Zertifikate auf Ablauf prüfen und Benachrichtigungen versenden (täglich 08:00)
Schedule::command('sslcerts:check-expiry')->dailyAt('08:00');

// Server ohne Administrator – Digest-Benachrichtigung stündlich prüfen
Schedule::command('server:send-admin-missing-notification')->hourly();
