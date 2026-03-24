<?php

namespace App\Http\Controllers;

use App\Mail\ReminderMailable;
use App\Models\ReminderMail;
use App\Models\ReminderMailLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class ReminderMailController extends Controller
{
    public function index()
    {
        $this->authorize('reminders.view');

        $reminders = ReminderMail::orderBy('nextsend')->get();

        $lastHeartbeat   = ReminderMailLog::where('typ', 4)->latest()->first();
        $schedulerActive = $lastHeartbeat && $lastHeartbeat->created_at->diffInMinutes(now()) < 15;

        return view('reminders.index', compact('reminders', 'lastHeartbeat', 'schedulerActive'));
    }

    public function create()
    {
        $this->authorize('reminders.create');

        return view('reminders.create', ['emailSuggestions' => $this->emailSuggestions()]);
    }

    public function store(Request $request)
    {
        $this->authorize('reminders.create');

        $data = $this->buildFromRequest($request);
        $data['user_id'] = Auth::id();
        $data['status']  = 1;

        $reminder = ReminderMail::create($data);

        ReminderMailLog::create([
            'typ'       => 2,
            'nachricht' => 'Erinnerung erstellt: "' . $reminder->titel . '" von ' . Auth::user()->name,
        ]);

        return redirect()->route('reminders.index')->with('success', 'Erinnerung erfolgreich gespeichert.');
    }

    public function edit(ReminderMail $reminder)
    {
        $this->authorizeReminderAccess($reminder);

        return view('reminders.edit', ['reminder' => $reminder, 'emailSuggestions' => $this->emailSuggestions()]);
    }

    public function update(Request $request, ReminderMail $reminder)
    {
        $this->authorizeReminderAccess($reminder);

        $reminder->update($this->buildFromRequest($request));

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

    public function sendTest(ReminderMail $reminder)
    {
        $this->authorizeReminderAccess($reminder);

        try {
            Mail::send(new ReminderMailable($reminder));
            ReminderMailLog::create([
                'typ'       => 2,
                'nachricht' => "Testnachricht gesendet: [{$reminder->id}] \"{$reminder->titel}\" → {$reminder->mailto_label}",
            ]);
            return back()->with('success', "Testnachricht wurde an {$reminder->mailto_label} gesendet.");
        } catch (\Exception $e) {
            ReminderMailLog::create([
                'typ'       => 3,
                'nachricht' => "Fehler Testnachricht [{$reminder->id}]: " . $e->getMessage(),
            ]);
            return back()->with('error', 'Fehler beim Senden: ' . $e->getMessage());
        }
    }

    public function log(Request $request)
    {
        $this->authorize('reminders.view');

        // Default: alle außer Heartbeat (typ=4); 'all' = wirklich alle
        $typ   = $request->get('typ', 'no_heartbeat');
        $query = ReminderMailLog::orderBy('created_at', 'desc');

        if ($typ === 'no_heartbeat') {
            $query->where('typ', '!=', 4);
        } elseif ($typ !== '' && $typ !== 'all') {
            $query->where('typ', (int) $typ);
        }

        $logs  = $query->limit(200)->get();
        $typen = ReminderMailLog::TYPEN;

        return view('reminders.log', compact('logs', 'typen', 'typ'));
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function buildFromRequest(Request $request): array
    {
        $typ = $request->input('intervall_typ', 'days');

        $baseRules = [
            'titel'         => ['required', 'string', 'max:255'],
            'nachricht'     => ['required', 'string'],
            'mailto'        => ['required', 'array', 'min:1'],
            'mailto.*'      => ['required', 'email', 'max:255'],
            'intervall_typ' => ['required', 'string', 'in:minutes,hours,days,weekly,monthly,yearly'],
            'start_datum'   => ['required', 'date_format:d.m.Y'],
        ];

        $typRules = match ($typ) {
            'minutes', 'hours', 'days' => [
                'start_time'   => ['required', 'regex:/^\d{2}:\d{2}$/'],
                'config_every' => ['required', 'integer', 'min:1'],
            ],
            'weekly' => [
                'config_days'  => ['required', 'array', 'min:1'],
                'config_days.*'=> ['string', 'in:Mo,Di,Mi,Do,Fr,Sa,So'],
                'config_time'  => ['required', 'regex:/^\d{2}:\d{2}$/'],
            ],
            'monthly' => [
                'config_nth'     => ['required'],
                'config_weekday' => ['required', 'string', 'in:Mo,Di,Mi,Do,Fr,Sa,So'],
                'config_time'    => ['required', 'regex:/^\d{2}:\d{2}$/'],
            ],
            'yearly' => [
                'config_day'   => ['required', 'integer', 'between:1,31'],
                'config_month' => ['required', 'integer', 'between:1,12'],
                'config_time'  => ['required', 'regex:/^\d{2}:\d{2}$/'],
            ],
            default => [],
        };

        $request->validate(array_merge($baseRules, $typRules));

        // Build config array
        $config = match ($typ) {
            'minutes', 'hours', 'days' => ['every' => (int)$request->config_every],
            'weekly'  => ['days' => $request->config_days ?? [], 'time' => $request->config_time],
            'monthly' => ['nth' => $request->config_nth, 'weekday' => $request->config_weekday, 'time' => $request->config_time],
            'yearly'  => ['day' => (int)$request->config_day, 'month' => (int)$request->config_month, 'time' => $request->config_time],
            default   => [],
        };

        // Calculate nextsend
        $start = Carbon::createFromFormat('d.m.Y', $request->start_datum)->startOfDay();

        if (in_array($typ, ['minutes', 'hours', 'days'])) {
            [$h, $m] = explode(':', $request->start_time);
            $nextsend = $start->setHour((int)$h)->setMinute((int)$m)->setSecond(0);
        } else {
            $dummy    = new ReminderMail(['intervall_typ' => $typ, 'intervall_config' => $config]);
            $nextsend = $dummy->calculateNextSend($start);
        }

        return [
            'titel'            => $request->titel,
            'nachricht'        => $request->nachricht,
            'mailto'           => array_values(array_filter($request->input('mailto', []))),
            'intervall_typ'    => $typ,
            'intervall_config' => $config,
            'nextsend'         => $nextsend,
        ];
    }

    private function emailSuggestions(): array
    {
        $fromReminders = ReminderMail::pluck('mailto')
            ->filter()
            ->flatMap(fn($m) => (array)$m)
            ->filter();

        $fromUsers = User::whereNotNull('email')->pluck('email');

        return $fromReminders->merge($fromUsers)->unique()->sort()->values()->toArray();
    }

    private function authorizeReminderAccess(ReminderMail $reminder): void
    {
        $user = Auth::user();
        if ($user->can('reminders.edit')) return;
        if ($user->can('reminders.create') && $reminder->user_id === $user->id) return;
        abort(403);
    }

    private function authorizeReminderDelete(ReminderMail $reminder): void
    {
        $user = Auth::user();
        if ($user->can('reminders.delete')) return;
        if ($user->can('reminders.create') && $reminder->user_id === $user->id) return;
        abort(403);
    }
}
