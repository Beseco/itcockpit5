<?php

namespace App\Http\Controllers;

use App\Models\Gruppe;
use App\Models\Stelle;
use App\Models\Stellenbeschreibung;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class StelleController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function index()
    {
        $this->authorize('base.stellen.view');

        $stellen = Stelle::with(['stellenbeschreibung', 'gruppe', 'stelleninhaber'])
            ->orderBy('stellennummer')
            ->paginate(25)
            ->withQueryString();

        return view('stellen.index', compact('stellen'));
    }

    public function create()
    {
        $this->authorize('base.stellen.edit');

        $gruppen              = Gruppe::orderBy('name')->get();
        $users                = User::where('is_active', true)->orderBy('name')->get();
        $stellenbeschreibungen = Stellenbeschreibung::orderBy('bezeichnung')->get();

        return view('stellen.create', compact('gruppen', 'users', 'stellenbeschreibungen'));
    }

    public function store(Request $request)
    {
        $this->authorize('base.stellen.edit');

        $validated = $request->validate([
            'stellennummer'          => 'required|string|max:50|unique:stellen,stellennummer',
            'stellenbeschreibung_id' => 'required|exists:stellenbeschreibungen,id',
            'gruppe_id'              => 'nullable|exists:gruppen,id',
            'user_id'                => 'nullable|exists:users,id',
            'haushalt_bewertung'     => 'nullable|string|max:50',
            'bes_gruppe'             => 'nullable|string|max:50',
            'belegung'               => 'nullable|numeric|min:0|max:100',
            'gesamtarbeitszeit'      => 'nullable|numeric|min:0|max:100',
            'anteil_stelle'          => 'nullable|numeric|min:0|max:100',
        ]);

        $stelle = Stelle::create($validated);

        $this->auditLogger->log('stellen', 'create', [
            'message' => "Stelle '{$stelle->stellennummer}' angelegt",
        ]);

        return redirect()->route('stellen.index')
            ->with('success', "Stelle \"{$stelle->stellennummer}\" wurde angelegt.");
    }

    public function show(Stelle $stelle)
    {
        $this->authorize('base.stellen.view');
        $stelle->load(['stellenbeschreibung.arbeitsvorgaenge', 'gruppe', 'stelleninhaber']);
        return view('stellen.show', compact('stelle'));
    }

    public function edit(Stelle $stelle)
    {
        $this->authorize('base.stellen.edit');

        $stelle->load(['stellenbeschreibung', 'gruppe', 'stelleninhaber']);
        $gruppen              = Gruppe::orderBy('name')->get();
        $users                = User::where('is_active', true)->orderBy('name')->get();
        $stellenbeschreibungen = Stellenbeschreibung::orderBy('bezeichnung')->get();

        return view('stellen.edit', compact('stelle', 'gruppen', 'users', 'stellenbeschreibungen'));
    }

    public function update(Request $request, Stelle $stelle)
    {
        $this->authorize('base.stellen.edit');

        $validated = $request->validate([
            'stellennummer'          => 'required|string|max:50|unique:stellen,stellennummer,' . $stelle->id,
            'stellenbeschreibung_id' => 'required|exists:stellenbeschreibungen,id',
            'gruppe_id'              => 'nullable|exists:gruppen,id',
            'user_id'                => 'nullable|exists:users,id',
            'haushalt_bewertung'     => 'nullable|string|max:50',
            'bes_gruppe'             => 'nullable|string|max:50',
            'belegung'               => 'nullable|numeric|min:0|max:100',
            'gesamtarbeitszeit'      => 'nullable|numeric|min:0|max:100',
            'anteil_stelle'          => 'nullable|numeric|min:0|max:100',
        ]);

        $stelle->update($validated);

        $this->auditLogger->log('stellen', 'update', [
            'message' => "Stelle '{$stelle->stellennummer}' aktualisiert",
        ]);

        return redirect()->route('stellen.edit', $stelle)
            ->with('success', "Stelle \"{$stelle->stellennummer}\" wurde gespeichert.");
    }

    public function destroy(Stelle $stelle)
    {
        $this->authorize('base.stellen.edit');

        $nr = $stelle->stellennummer;
        $stelle->delete();

        $this->auditLogger->log('stellen', 'delete', [
            'message' => "Stelle '{$nr}' gelöscht",
        ]);

        return redirect()->route('stellen.index')
            ->with('success', "Stelle \"{$nr}\" wurde gelöscht.");
    }
}
