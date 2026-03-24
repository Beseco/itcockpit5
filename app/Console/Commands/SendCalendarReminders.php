<?php

namespace App\Console\Commands;

use App\Modules\Calendar\Models\CalendarEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendCalendarReminders extends Command
{
    protected $signature   = 'calendar:send-reminders';
    protected $description = 'Sendet Erinnerungsmails für bevorstehende Kalendertermine';

    public function handle(): int
    {
        CalendarEvent::with(['creator', 'attendees.user'])
            ->where('erinnerung_gesendet', false)
            ->whereNotNull('erinnerung_minuten')
            ->where('start_at', '>', now())
            ->get()
            ->each(function (CalendarEvent $event) {
                $sendAt = $event->start_at->copy()->subMinutes($event->erinnerung_minuten);

                if ($sendAt->lte(now())) {
                    $recipients = collect([$event->creator?->email])
                        ->merge($event->attendees->map->email_address)
                        ->filter()
                        ->unique();

                    foreach ($recipients as $email) {
                        try {
                            Mail::send(
                                'calendar::emails.erinnerung',
                                ['event' => $event],
                                fn($m) => $m->to($email)->subject('Erinnerung: ' . $event->titel)
                            );
                        } catch (\Exception $e) {
                            $this->error("Fehler bei Erinnerung [{$event->id}] an {$email}: " . $e->getMessage());
                        }
                    }

                    $event->update(['erinnerung_gesendet' => true]);
                    $this->info("Erinnerung gesendet: [{$event->id}] {$event->titel}");
                }
            });

        return self::SUCCESS;
    }
}
