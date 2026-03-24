<?php

namespace App\Console\Commands;

use App\Mail\ReminderMailable;
use App\Models\ReminderMail;
use App\Models\ReminderMailLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendReminderMails extends Command
{
    protected $signature   = 'reminders:send';
    protected $description = 'Sendet fällige Erinnerungsmails und plant den nächsten Versand';

    public function handle(): int
    {
        ReminderMailLog::create([
            'typ'      => 4,
            'nachricht' => 'Scheduler-Heartbeat',
        ]);

        $due = ReminderMail::active()
            ->where('nextsend', '<=', now())
            ->get();

        if ($due->isEmpty()) {
            return self::SUCCESS;
        }

        foreach ($due as $reminder) {
            try {
                Mail::send(new ReminderMailable($reminder));

                ReminderMailLog::create([
                    'typ'      => 1,
                    'nachricht' => "Mail gesendet: [{$reminder->id}] \"{$reminder->titel}\" → {$reminder->mailto_label}",
                ]);

                // Nächsten Sendezeitpunkt berechnen
                $reminder->update(['nextsend' => $reminder->calculateNextSend()]);

            } catch (\Exception $e) {
                ReminderMailLog::create([
                    'typ'      => 3,
                    'nachricht' => "Fehler bei [{$reminder->id}] \"{$reminder->titel}\": " . $e->getMessage(),
                ]);

                $this->error("Fehler: {$e->getMessage()}");
            }
        }

        $this->info("Versandt: {$due->count()} Erinnerung(en).");
        return self::SUCCESS;
    }
}
