<?php

namespace App\Modules\Calendar\Http\Controllers;

use App\Models\ReminderMail;
use App\Modules\Calendar\Models\CalendarEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class EventApiController extends Controller
{
    public function events(Request $request): JsonResponse
    {
        $start = $request->get('start') ? \Carbon\Carbon::parse($request->get('start')) : now()->startOfMonth();
        $end   = $request->get('end')   ? \Carbon\Carbon::parse($request->get('end'))   : now()->endOfMonth();

        $events = [];

        // Kalender-Termine (inkl. wiederkehrende)
        // Alle Events laden, die im Zeitraum beginnen ODER wiederkehrend sind
        CalendarEvent::with(['attendees.user', 'creator'])
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_at', [$start, $end])
                  ->orWhereNotNull('wiederholung_typ');
            })
            ->get()
            ->each(function (CalendarEvent $event) use (&$events, $start, $end) {
                $canEdit   = Auth::user()->can('calendar.edit') || $event->user_id === Auth::id();
                $duration  = $event->end_at ? $event->start_at->diffInSeconds($event->end_at) : 0;
                $attendees = $event->attendees->map(fn($a) => [
                    'user_id' => $a->user_id,
                    'email'   => $a->email_address,
                    'name'    => $a->user?->name,
                ]);

                foreach ($event->getOccurrences($start, $end) as $occStart) {
                    $occEnd = $duration ? $occStart->copy()->addSeconds($duration) : null;

                    $events[] = [
                        'id'              => 'ev_' . $event->id . '_' . $occStart->timestamp,
                        'title'           => $event->titel,
                        'start'           => $event->ganztag ? $occStart->toDateString() : $occStart->toIso8601String(),
                        'end'             => $occEnd
                            ? ($event->ganztag ? $occEnd->addDay()->toDateString() : $occEnd->toIso8601String())
                            : null,
                        'allDay'          => $event->ganztag,
                        'backgroundColor' => $event->effektive_farbe,
                        'borderColor'     => $event->effektive_farbe,
                        'extendedProps'   => [
                            'type'              => 'event',
                            'dbId'              => $event->id,
                            'beschreibung'      => $event->beschreibung,
                            'typ'               => $event->typ,
                            'farbe'             => $event->farbe,
                            'erinnerung'        => $event->erinnerung_minuten,
                            'wiederholung_typ'  => $event->wiederholung_typ,
                            'wiederholung_config' => $event->wiederholung_config,
                            'wiederholung_bis'  => $event->wiederholung_bis?->toDateString(),
                            'creator'           => $event->creator?->name,
                            'attendees'         => $attendees,
                            'canEdit'           => $canEdit,
                        ],
                    ];
                }
            });

        // Erinnerungsmails als read-only – alle Vorkommen im Zeitraum
        ReminderMail::active()->get()->each(function (ReminderMail $reminder) use (&$events, $start, $end) {
            $cursor = $reminder->nextsend->copy();
            $max    = 200;

            while ($cursor->lte($end) && $max-- > 0) {
                if ($cursor->gte($start)) {
                    $events[] = [
                        'id'              => 'rm_' . $reminder->id . '_' . $cursor->timestamp,
                        'title'           => '🔔 ' . $reminder->titel,
                        'start'           => $cursor->toIso8601String(),
                        'allDay'          => false,
                        'backgroundColor' => '#6b7280',
                        'borderColor'     => '#6b7280',
                        'extendedProps'   => [
                            'type'         => 'reminder',
                            'beschreibung' => $reminder->nachricht,
                            'intervall'    => $reminder->intervall_label,
                            'canEdit'      => false,
                        ],
                    ];
                }
                $next = $reminder->calculateNextSend($cursor);
                if ($next->lte($cursor)) break; // Endlosschleife verhindern
                $cursor = $next;
            }
        });

        return response()->json($events);
    }
}
