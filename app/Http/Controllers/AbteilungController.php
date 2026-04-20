<?php

namespace App\Http\Controllers;

use App\Mail\AbteilungRevisionMail;
use App\Models\Abteilung;
use App\Modules\AdUsers\Models\AdUser;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class AbteilungController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function index()
    {
        $this->authorize('abteilungen.view');

        $abteilungen = Abteilung::with([
            'children.children.children.vorgesetzter',
            'children.children.children.stellvertreter',
            'children.children.vorgesetzter',
            'children.children.stellvertreter',
            'children.vorgesetzter',
            'children.stellvertreter',
            'vorgesetzter',
            'stellvertreter',
            'parent',
        ])->roots()->get();

        return view('abteilungen.index', compact('abteilungen'));
    }

    public function create()
    {
        $this->authorize('abteilungen.create');

        $allAbteilungen = Abteilung::orderBy('sort_order')->orderBy('name')->get();
        $adUsers        = AdUser::aktiv()->orderBy('anzeigename')->get();

        return view('abteilungen.create', compact('allAbteilungen', 'adUsers'));
    }

    public function store(Request $request)
    {
        $this->authorize('abteilungen.create');

        $validated = $this->validateAbteilung($request);

        $abteilung = Abteilung::create($validated);

        $this->auditLogger->log('Abteilung', 'Abteilung erstellt', [
            'id'   => $abteilung->id,
            'name' => $abteilung->name,
        ]);

        return redirect()->route('abteilungen.index')
                         ->with('success', 'Abteilung erfolgreich gespeichert.');
    }

    public function edit(Abteilung $abteilung)
    {
        $this->authorize('abteilungen.edit');

        $excludeIds     = $abteilung->allChildren()->pluck('id')->push($abteilung->id)->toArray();
        $allAbteilungen = Abteilung::whereNotIn('id', $excludeIds)
                                   ->orderBy('sort_order')
                                   ->orderBy('name')
                                   ->get();
        $adUsers        = AdUser::aktiv()->orderBy('anzeigename')->get();

        return view('abteilungen.edit', compact('abteilung', 'allAbteilungen', 'adUsers'));
    }

    public function update(Request $request, Abteilung $abteilung)
    {
        $this->authorize('abteilungen.edit');

        $validated = $this->validateAbteilung($request);

        $abteilung->update($validated);

        $this->auditLogger->log('Abteilung', 'Abteilung aktualisiert', [
            'id'   => $abteilung->id,
            'name' => $abteilung->name,
        ]);

        return redirect()->route('abteilungen.index')
                         ->with('success', 'Abteilung erfolgreich aktualisiert.');
    }

    public function destroy(Abteilung $abteilung)
    {
        $this->authorize('abteilungen.delete');

        $data = ['id' => $abteilung->id, 'name' => $abteilung->name];
        $abteilung->delete();

        $this->auditLogger->log('Abteilung', 'Abteilung gelöscht', $data);

        return redirect()->route('abteilungen.index')
                         ->with('success', 'Abteilung gelöscht.');
    }

    public function sendRevisionMailTest(Abteilung $abteilung)
    {
        $this->authorize('abteilungen.edit');

        $token = $abteilung->ensureRevisionToken();
        $abteilung->revision_notified_at = now();
        $abteilung->save();

        $recipient = Auth::user()->email;

        try {
            Mail::to($recipient)->send(new AbteilungRevisionMail($abteilung, Auth::user()->name));
        } catch (\Exception $e) {
            return redirect()->route('abteilungen.edit', $abteilung)
                ->with('error', 'Mailversand fehlgeschlagen: ' . $e->getMessage());
        }

        return redirect()->route('abteilungen.edit', $abteilung)
            ->with('success', "Test-Revisionsmail an {$recipient} gesendet.");
    }

    private function validateAbteilung(Request $request): array
    {
        return $request->validate([
            'name'                       => ['required', 'string', 'max:255'],
            'kurzzeichen'                => ['nullable', 'string', 'max:20'],
            'parent_id'                  => ['nullable', 'integer', 'exists:abteilungen,id'],
            'sort_order'                 => ['nullable', 'integer', 'min:0'],
            'vorgesetzter_ad_user_id'    => ['nullable', 'integer', 'exists:adusers,id'],
            'stellvertreter_ad_user_id'  => ['nullable', 'integer', 'exists:adusers,id'],
            'revision_date'              => ['nullable', 'date'],
        ]);
    }
}
