<?php

namespace App\Modules\Calendar\Http\Controllers;

use App\Models\User;
use App\Modules\Calendar\Models\CalendarEvent;
use App\Modules\Calendar\Models\CalendarEventAttendee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class EventController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validate($request);
        $attendees = $validated['attendees'] ?? [];
        unset($validated['attendees']);

        $validated['user_id'] = Auth::id();
        $event = CalendarEvent::create($validated);
        $this->syncAttendees($event, $attendees);

        return response()->json(['id' => $event->id], 201);
    }

    public function update(Request $request, CalendarEvent $event): JsonResponse
    {
        $this->authorizeAccess($event);

        $validated = $this->validate($request);
        $attendees = $validated['attendees'] ?? [];
        unset($validated['attendees']);

        $event->update($validated);
        $this->syncAttendees($event, $attendees);

        return response()->json(['ok' => true]);
    }

    public function destroy(CalendarEvent $event): JsonResponse
    {
        $this->authorizeAccess($event);
        $event->delete();

        return response()->json(['ok' => true]);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function validate(Request $request): array
    {
        return $request->validate([
            'titel'               => ['required', 'string', 'max:255'],
            'beschreibung'        => ['nullable', 'string'],
            'start_at'            => ['required', 'date'],
            'end_at'              => ['nullable', 'date', 'after_or_equal:start_at'],
            'ganztag'             => ['boolean'],
            'typ'                 => ['required', 'string', 'in:termin,wartung,sonstiges'],
            'farbe'               => ['nullable', 'string', 'max:20'],
            'erinnerung_minuten'    => ['nullable', 'integer'],
            'wiederholung_typ'      => ['nullable', 'string', 'in:,daily,weekly,monthly,yearly'],
            'wiederholung_config'   => ['nullable', 'array'],
            'wiederholung_bis'      => ['nullable', 'date'],
            'attendees'             => ['nullable', 'array'],
            'attendees.*'           => ['string'],
        ]);
    }

    private function authorizeAccess(CalendarEvent $event): void
    {
        $user = Auth::user();
        if ($user->can('module.calendar.edit') || $event->user_id === $user->id) return;
        abort(403);
    }

    private function syncAttendees(CalendarEvent $event, array $attendees): void
    {
        $event->attendees()->delete();

        foreach ($attendees as $value) {
            $value = trim($value);
            if (!$value) continue;

            // Prüfen ob interne User-ID oder E-Mail-Adresse
            $user = User::where('email', $value)->first();

            $event->attendees()->create([
                'user_id'      => $user?->id,
                'email'        => $user ? null : $value,
                'eingeladen_at' => now(),
            ]);

            // Einladungs-Mail senden
            $emailAddress = $user?->email ?? $value;
            try {
                Mail::send(
                    'calendar::emails.einladung',
                    ['event' => $event, 'eingeladenVon' => Auth::user()],
                    fn($m) => $m->to($emailAddress)->subject('Einladung: ' . $event->titel)
                );
            } catch (\Exception) {
                // Fehler beim Senden ignorieren – Teilnehmer wird trotzdem gespeichert
            }
        }
    }
}
