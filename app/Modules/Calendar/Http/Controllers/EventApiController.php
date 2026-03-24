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

        // Kalender-Termine
        CalendarEvent::with(['attendees.user', 'creator'])
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_at', [$start, $end])
                  ->orWhereBetween('end_at', [$start, $end])
                  ->orWhere(function ($q2) use ($start, $end) {
                      $q2->where('start_at', '<=', $start)->where('end_at', '>=', $end);
                  });
            })
            ->get()
            ->each(function (CalendarEvent $event) use (&$events) {
                $canEdit = Auth::user()->can('module.calendar.edit')
                    || $event->user_id === Auth::id();

                $events[] = [
                    'id'               => 'ev_' . $event->id,
                    'title'            => $event->titel,
                    'start'            => $event->ganztag
                        ? $event->start_at->toDateString()
                        : $event->start_at->toIso8601String(),
                    'end'              => $event->end_at
                        ? ($event->ganztag
                            ? $event->end_at->addDay()->toDateString()
                            : $event->end_at->toIso8601String())
                        : null,
                    'allDay'           => $event->ganztag,
                    'backgroundColor'  => $event->effektive_farbe,
                    'borderColor'      => $event->effektive_farbe,
                    'extendedProps'    => [
                        'type'         => 'event',
                        'dbId'         => $event->id,
                        'beschreibung' => $event->beschreibung,
                        'typ'          => $event->typ,
                        'farbe'        => $event->farbe,
                        'erinnerung'   => $event->erinnerung_minuten,
                        'creator'      => $event->creator?->name,
                        'attendees'    => $event->attendees->map(fn($a) => [
                            'user_id' => $a->user_id,
                            'email'   => $a->email_address,
                            'name'    => $a->user?->name,
                        ]),
                        'canEdit'      => $canEdit,
                    ],
                ];
            });

        // Erinnerungsmails als read-only
        ReminderMail::active()
            ->whereBetween('nextsend', [$start, $end])
            ->get()
            ->each(function (ReminderMail $reminder) use (&$events) {
                $events[] = [
                    'id'              => 'rm_' . $reminder->id,
                    'title'           => '🔔 ' . $reminder->titel,
                    'start'           => $reminder->nextsend->toIso8601String(),
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
            });

        return response()->json($events);
    }
}
