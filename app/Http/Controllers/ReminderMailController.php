<?php

namespace App\Http\Controllers;

use App\Models\ReminderMail;
use App\Models\ReminderMailLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReminderMailController extends Controller
{
    public function index()
    {
        $this->authorize('reminders.view');

        $reminders = ReminderMail::orderBy('nextsend')->get();

        $lastHeartbeat = ReminderMailLog::where('typ', 4)->latest()->first();
        $schedulerActive = $lastHeartbeat && $lastHeartbeat->created_at->diffInMinutes(now()) < 2;

        return view('reminders.index', compact('reminders', 'lastHeartbeat', 'schedulerActive'));
    }

    public function create()
    {
        $this->authorize('reminders.create');

        return view('reminders.create', ['faktoren' => ReminderMail::FAKTOREN]);
    }

    public function store(Request $request)
    {
        $this->authorize('reminders.create');

        $validated = $this->validateReminder($request);
        $validated['user_id'] = Auth::id();
        $validated['status']  = 1;

        ReminderMail::create($validated);

        ReminderMailLog::create([
            'typ'       => 2,
            'nachricht' => 'Erinnerung erstellt: "' . $validated['titel'] . '" von ' . Auth::user()->name,
        ]);

        return redirect()->route('reminders.index')->with('success', 'Erinnerung erfolgreich gespeichert.');
    }

    public function edit(ReminderMail $reminder)
    {
        $this->authorizeReminderAccess($reminder);

        return view('reminders.edit', [
            'reminder' => $reminder,
            'faktoren' => ReminderMail::FAKTOREN,
        ]);
    }

    public function update(Request $request, ReminderMail $reminder)
    {
        $this->authorizeReminderAccess($reminder);

        $validated = $this->validateReminder($request);
        $reminder->update($validated);

        return redirect()->route('reminders.index')->with('success', 'Erinnerung erfolgreich aktualisiert.');
    }

    public function destroy(ReminderMail $reminder)
    {
        $this->authorizeReminderDelete($reminder);

        $reminder->delete();
        return redirect()->route('reminders.index')->with('success', 'Erinnerung gelöscht.');
    }

    public function toggleStatus(ReminderMail $reminder)
    {
        $this->authorizeReminderAccess($reminder);

        $reminder->update(['status' => $reminder->status ? 0 : 1]);
        return back()->with('success', $reminder->status ? 'Erinnerung aktiviert.' : 'Erinnerung deaktiviert.');
    }

    public function log(Request $request)
    {
        $this->authorize('reminders.view');

        $typ = $request->get('typ', '');

        $query = ReminderMailLog::orderBy('created_at', 'desc');
        if ($typ !== '') {
            $query->where('typ', (int) $typ);
        }

        $logs  = $query->limit(200)->get();
        $typen = ReminderMailLog::TYPEN;

        return view('reminders.log', compact('logs', 'typen', 'typ'));
    }

    /**
     * Prüft ob der User die Erinnerung bearbeiten darf:
     * - reminders.edit → alle Erinnerungen
     * - reminders.create + eigene → nur eigene
     */
    private function authorizeReminderAccess(ReminderMail $reminder): void
    {
        $user = Auth::user();

        if ($user->can('reminders.edit')) {
            return;
        }

        if ($user->can('reminders.create') && $reminder->user_id === $user->id) {
            return;
        }

        abort(403);
    }

    /**
     * Prüft ob der User die Erinnerung löschen darf:
     * - reminders.delete → alle Erinnerungen
     * - reminders.create + eigene → nur eigene
     */
    private function authorizeReminderDelete(ReminderMail $reminder): void
    {
        $user = Auth::user();

        if ($user->can('reminders.delete')) {
            return;
        }

        if ($user->can('reminders.create') && $reminder->user_id === $user->id) {
            return;
        }

        abort(403);
    }

    private function validateReminder(Request $request): array
    {
        $validated = $request->validate([
            'titel'            => ['required', 'string', 'max:255'],
            'nachricht'        => ['required', 'string'],
            'mailto'           => ['required', 'email', 'max:255'],
            'datum'            => ['required', 'date_format:d.m.Y'],
            'stunde'           => ['required', 'integer', 'between:0,23'],
            'minute'           => ['required', 'integer', 'in:0,15,30,45'],
            'intervall_nummer' => ['required', 'integer', 'min:1'],
            'intervall_faktor' => ['required', 'integer', 'in:60,3600,86400'],
        ]);

        // datum + stunde + minute → nextsend datetime
        [$tag, $monat, $jahr] = explode('.', $validated['datum']);
        $validated['nextsend'] = \Carbon\Carbon::createFromFormat(
            'Y-m-d H:i',
            "$jahr-$monat-$tag {$validated['stunde']}:{$validated['minute']}"
        );

        unset($validated['datum'], $validated['stunde'], $validated['minute']);

        return $validated;
    }
}
