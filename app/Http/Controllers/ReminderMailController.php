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
        $reminders = ReminderMail::orderBy('nextsend')->get();

        $lastHeartbeat = ReminderMailLog::where('typ', 4)->latest()->first();
        $schedulerActive = $lastHeartbeat && $lastHeartbeat->created_at->diffInMinutes(now()) < 2;

        return view('reminders.index', compact('reminders', 'lastHeartbeat', 'schedulerActive'));
    }

    public function create()
    {
        return view('reminders.create', ['faktoren' => ReminderMail::FAKTOREN]);
    }

    public function store(Request $request)
    {
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
        return view('reminders.edit', [
            'reminder' => $reminder,
            'faktoren' => ReminderMail::FAKTOREN,
        ]);
    }

    public function update(Request $request, ReminderMail $reminder)
    {
        $validated = $this->validateReminder($request);
        $reminder->update($validated);

        return redirect()->route('reminders.index')->with('success', 'Erinnerung erfolgreich aktualisiert.');
    }

    public function destroy(ReminderMail $reminder)
    {
        $reminder->delete();
        return redirect()->route('reminders.index')->with('success', 'Erinnerung gelöscht.');
    }

    public function toggleStatus(ReminderMail $reminder)
    {
        $reminder->update(['status' => $reminder->status ? 0 : 1]);
        return back()->with('success', $reminder->status ? 'Erinnerung aktiviert.' : 'Erinnerung deaktiviert.');
    }

    public function log(Request $request)
    {
        $typ = $request->get('typ', '');

        $query = ReminderMailLog::orderBy('created_at', 'desc');
        if ($typ !== '') {
            $query->where('typ', (int) $typ);
        }

        $logs  = $query->limit(200)->get();
        $typen = ReminderMailLog::TYPEN;

        return view('reminders.log', compact('logs', 'typen', 'typ'));
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
