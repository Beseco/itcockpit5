<?php

namespace App\Modules\Calendar\Services;

use App\Models\ReminderMail;
use App\Models\User;
use App\Modules\Calendar\Models\CalendarEvent;
use Carbon\Carbon;

class IcsService
{
    public function generate(User $user): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//IT Cockpit//Kalender//DE',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:IT Cockpit – ' . $this->escape($user->name),
            'X-WR-TIMEZONE:Europe/Berlin',
        ];

        // Kalender-Events (Ersteller oder Teilnehmer)
        $eventIds = CalendarEvent::whereHas('attendees', fn($q) => $q->where('user_id', $user->id))
            ->orWhere('user_id', $user->id)
            ->pluck('id');

        CalendarEvent::whereIn('id', $eventIds)->get()->each(function (CalendarEvent $event) use (&$lines) {
            $dtstart = $event->ganztag
                ? 'DTSTART;VALUE=DATE:' . $event->start_at->format('Ymd')
                : 'DTSTART;TZID=Europe/Berlin:' . $event->start_at->format('Ymd\THis');

            $dtend = $event->end_at
                ? ($event->ganztag
                    ? 'DTEND;VALUE=DATE:' . $event->end_at->addDay()->format('Ymd')
                    : 'DTEND;TZID=Europe/Berlin:' . $event->end_at->format('Ymd\THis'))
                : $dtstart; // Fallback: gleiche Zeit

            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:calendar-event-' . $event->id . '@itcockpit';
            $lines[] = 'DTSTAMP:' . now()->utc()->format('Ymd\THis\Z');
            $lines[] = $dtstart;
            $lines[] = $dtend;
            $lines[] = 'SUMMARY:' . $this->escape($event->titel);
            if ($event->beschreibung) {
                $lines[] = 'DESCRIPTION:' . $this->escape(strip_tags($event->beschreibung));
            }
            $lines[] = 'CATEGORIES:' . strtoupper($event->typ);
            $lines[] = 'END:VEVENT';
        });

        // Erinnerungsmails
        ReminderMail::active()->get()->each(function (ReminderMail $reminder) use (&$lines) {
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:reminder-' . $reminder->id . '@itcockpit';
            $lines[] = 'DTSTAMP:' . now()->utc()->format('Ymd\THis\Z');
            $lines[] = 'DTSTART;TZID=Europe/Berlin:' . $reminder->nextsend->format('Ymd\THis');
            $lines[] = 'DTEND;TZID=Europe/Berlin:' . $reminder->nextsend->addMinutes(30)->format('Ymd\THis');
            $lines[] = 'SUMMARY:🔔 ' . $this->escape($reminder->titel);
            $lines[] = 'CATEGORIES:ERINNERUNG';
            $lines[] = 'END:VEVENT';
        });

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines) . "\r\n";
    }

    private function escape(string $value): string
    {
        return str_replace(
            ['\\', ';', ',', "\n"],
            ['\\\\', '\;', '\,', '\n'],
            $value
        );
    }
}
