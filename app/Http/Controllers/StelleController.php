<?php

namespace App\Http\Controllers;

use App\Models\Arbeitsvorgang;
use App\Models\Gruppe;
use App\Models\Stelle;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class StelleController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function index()
    {
        $this->authorize('base.stellen.view');

        $stellen = Stelle::with(['gruppe', 'stelleninhaber', 'arbeitsvorgaenge'])
            ->orderBy('bezeichnung')
            ->paginate(25)
            ->withQueryString();

        return view('stellen.index', compact('stellen'));
    }

    public function create()
    {
        $this->authorize('base.stellen.create');

        $gruppen = Gruppe::orderBy('name')->get();
        $users   = User::where('is_active', true)->orderBy('name')->get();

        return view('stellen.create', compact('gruppen', 'users'));
    }

    public function store(Request $request)
    {
        $this->authorize('base.stellen.create');

        $validated = $request->validate([
            'bezeichnung'    => 'required|string|max:255',
            'stellennummer'  => 'nullable|string|max:50',
            'gruppe_id'      => 'nullable|exists:gruppen,id',
            'user_id'        => 'nullable|exists:users,id',
            'tvod_bewertung' => 'nullable|string|max:10',
            'stunden'        => 'nullable|numeric|min:0|max:50',
            'arbeitsvorgaenge'              => 'nullable|array',
            'arbeitsvorgaenge.*.betreff'    => 'required|string|max:255',
            'arbeitsvorgaenge.*.beschreibung' => 'nullable|string',
            'arbeitsvorgaenge.*.anteil'     => 'required|integer|min:0|max:100',
        ]);

        $stelle = Stelle::create([
            'bezeichnung'    => $validated['bezeichnung'],
            'stellennummer'  => $validated['stellennummer'] ?? null,
            'gruppe_id'      => $validated['gruppe_id'] ?? null,
            'user_id'        => $validated['user_id'] ?? null,
            'tvod_bewertung' => $validated['tvod_bewertung'] ?? null,
            'stunden'        => $validated['stunden'] ?? null,
        ]);

        foreach (($validated['arbeitsvorgaenge'] ?? []) as $i => $av) {
            Arbeitsvorgang::create([
                'stelle_id'    => $stelle->id,
                'betreff'      => $av['betreff'],
                'beschreibung' => $av['beschreibung'] ?? null,
                'anteil'       => (int) $av['anteil'],
                'sort_order'   => $i,
            ]);
        }

        $this->auditLogger->log('stellen', 'create', ['message' => "Stelle '{$stelle->bezeichnung}' angelegt"]);

        return redirect()->route('stellen.index')
            ->with('success', "Stelle \"{$stelle->bezeichnung}\" wurde angelegt.");
    }

    public function show(Stelle $stelle)
    {
        $this->authorize('base.stellen.view');
        $stelle->load(['gruppe', 'stelleninhaber', 'arbeitsvorgaenge']);
        return view('stellen.show', compact('stelle'));
    }

    public function edit(Stelle $stelle)
    {
        $this->authorize('base.stellen.edit');

        $stelle->load(['gruppe', 'stelleninhaber', 'arbeitsvorgaenge']);
        $gruppen = Gruppe::orderBy('name')->get();
        $users   = User::where('is_active', true)->orderBy('name')->get();

        return view('stellen.edit', compact('stelle', 'gruppen', 'users'));
    }

    public function update(Request $request, Stelle $stelle)
    {
        $this->authorize('base.stellen.edit');

        $validated = $request->validate([
            'bezeichnung'    => 'required|string|max:255',
            'stellennummer'  => 'nullable|string|max:50',
            'gruppe_id'      => 'nullable|exists:gruppen,id',
            'user_id'        => 'nullable|exists:users,id',
            'tvod_bewertung' => 'nullable|string|max:10',
            'stunden'        => 'nullable|numeric|min:0|max:50',
            'arbeitsvorgaenge'              => 'nullable|array',
            'arbeitsvorgaenge.*.id'         => 'nullable|integer',
            'arbeitsvorgaenge.*.betreff'    => 'required|string|max:255',
            'arbeitsvorgaenge.*.beschreibung' => 'nullable|string',
            'arbeitsvorgaenge.*.anteil'     => 'required|integer|min:0|max:100',
        ]);

        $stelle->update([
            'bezeichnung'    => $validated['bezeichnung'],
            'stellennummer'  => $validated['stellennummer'] ?? null,
            'gruppe_id'      => $validated['gruppe_id'] ?? null,
            'user_id'        => $validated['user_id'] ?? null,
            'tvod_bewertung' => $validated['tvod_bewertung'] ?? null,
            'stunden'        => $validated['stunden'] ?? null,
        ]);

        // Sync Arbeitsvorgänge: delete all, re-insert
        $stelle->arbeitsvorgaenge()->delete();
        foreach (($validated['arbeitsvorgaenge'] ?? []) as $i => $av) {
            Arbeitsvorgang::create([
                'stelle_id'    => $stelle->id,
                'betreff'      => $av['betreff'],
                'beschreibung' => $av['beschreibung'] ?? null,
                'anteil'       => (int) $av['anteil'],
                'sort_order'   => $i,
            ]);
        }

        $this->auditLogger->log('stellen', 'update', ['message' => "Stelle '{$stelle->bezeichnung}' aktualisiert"]);

        return redirect()->route('stellen.index')
            ->with('success', "Stelle \"{$stelle->bezeichnung}\" wurde gespeichert.");
    }

    public function destroy(Stelle $stelle)
    {
        $this->authorize('base.stellen.delete');

        $bezeichnung = $stelle->bezeichnung;
        $stelle->arbeitsvorgaenge()->delete();
        $stelle->delete();

        $this->auditLogger->log('stellen', 'delete', ['message' => "Stelle '{$bezeichnung}' gelöscht"]);

        return redirect()->route('stellen.index')
            ->with('success', "Stelle \"{$bezeichnung}\" wurde gelöscht.");
    }
}
