<?php

namespace App\Http\Controllers;

use App\Models\Arbeitsvorgang;
use App\Models\Stellenbeschreibung;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class StellenbeschreibungController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function index()
    {
        $this->authorize('base.stellenbeschreibungen.view');

        $stellenbeschreibungen = Stellenbeschreibung::withCount('stellen')
            ->with('arbeitsvorgaenge')
            ->orderBy('bezeichnung')
            ->get();

        return view('stellenbeschreibungen.index', compact('stellenbeschreibungen'));
    }

    public function create()
    {
        $this->authorize('base.stellenbeschreibungen.edit');
        return view('stellenbeschreibungen.create');
    }

    public function store(Request $request)
    {
        $this->authorize('base.stellenbeschreibungen.edit');

        $validated = $request->validate([
            'bezeichnung' => 'required|string|max:255|unique:stellenbeschreibungen,bezeichnung',
        ]);

        $sb = Stellenbeschreibung::create($validated);

        $this->auditLogger->log('stellenbeschreibungen', 'create', [
            'message' => "Stellenbeschreibung '{$sb->bezeichnung}' angelegt",
        ]);

        return redirect()->route('stellenbeschreibungen.edit', $sb)
            ->with('success', "Stellenbeschreibung \"{$sb->bezeichnung}\" wurde angelegt.");
    }

    public function edit(Stellenbeschreibung $stellenbeschreibung)
    {
        $this->authorize('base.stellenbeschreibungen.edit');
        $stellenbeschreibung->load(['arbeitsvorgaenge', 'stellen.gruppe']);
        return view('stellenbeschreibungen.edit', compact('stellenbeschreibung'));
    }

    public function update(Request $request, Stellenbeschreibung $stellenbeschreibung)
    {
        $this->authorize('base.stellenbeschreibungen.edit');

        $validated = $request->validate([
            'bezeichnung' => 'required|string|max:255|unique:stellenbeschreibungen,bezeichnung,' . $stellenbeschreibung->id,
        ]);

        $stellenbeschreibung->update($validated);

        $this->auditLogger->log('stellenbeschreibungen', 'update', [
            'message' => "Stellenbeschreibung '{$stellenbeschreibung->bezeichnung}' aktualisiert",
        ]);

        return redirect()->route('stellenbeschreibungen.edit', $stellenbeschreibung)
            ->with('success', "Stellenbeschreibung \"{$stellenbeschreibung->bezeichnung}\" wurde gespeichert.");
    }

    public function destroy(Stellenbeschreibung $stellenbeschreibung)
    {
        $this->authorize('base.stellenbeschreibungen.edit');

        if ($stellenbeschreibung->stellen()->count() > 0) {
            return redirect()->route('stellenbeschreibungen.index')
                ->with('error', "Stellenbeschreibung \"{$stellenbeschreibung->bezeichnung}\" kann nicht gelöscht werden – sie ist noch {$stellenbeschreibung->stellen()->count()} Stelle(n) zugewiesen.");
        }

        $bezeichnung = $stellenbeschreibung->bezeichnung;
        $stellenbeschreibung->arbeitsvorgaenge()->delete();
        $stellenbeschreibung->delete();

        $this->auditLogger->log('stellenbeschreibungen', 'delete', [
            'message' => "Stellenbeschreibung '{$bezeichnung}' gelöscht",
        ]);

        return redirect()->route('stellenbeschreibungen.index')
            ->with('success', "Stellenbeschreibung \"{$bezeichnung}\" wurde gelöscht.");
    }

    // ── Arbeitsvorgänge ──────────────────────────────────────────────────────

    public function createAv(Stellenbeschreibung $stellenbeschreibung)
    {
        $this->authorize('base.stellenbeschreibungen.edit');
        $nextNumber = $stellenbeschreibung->arbeitsvorgaenge()->count() + 1;
        $avLabel    = 'AV' . $nextNumber;
        $av         = new Arbeitsvorgang();
        return view('stellenbeschreibungen.arbeitsvorgang_edit', compact('stellenbeschreibung', 'av', 'avLabel'));
    }

    public function storeAv(Request $request, Stellenbeschreibung $stellenbeschreibung)
    {
        $this->authorize('base.stellenbeschreibungen.edit');

        $validated = $request->validate([
            'betreff'      => 'required|string|max:255',
            'beschreibung' => 'nullable|string',
            'anteil'       => 'required|integer|min:0|max:100',
        ]);

        $stellenbeschreibung->arbeitsvorgaenge()->create([
            'betreff'      => $validated['betreff'],
            'beschreibung' => $validated['beschreibung'] ?? null,
            'anteil'       => (int) $validated['anteil'],
            'sort_order'   => $stellenbeschreibung->arbeitsvorgaenge()->count(),
        ]);

        $this->auditLogger->log('stellenbeschreibungen', 'update', [
            'message' => "AV '{$validated['betreff']}' zu Stellenbeschreibung '{$stellenbeschreibung->bezeichnung}' hinzugefügt",
        ]);

        return redirect()->route('stellenbeschreibungen.edit', $stellenbeschreibung)
            ->with('success', "Arbeitsvorgang \"{$validated['betreff']}\" wurde hinzugefügt.");
    }

    public function editAv(Stellenbeschreibung $stellenbeschreibung, Arbeitsvorgang $av)
    {
        $this->authorize('base.stellenbeschreibungen.edit');
        $stellenbeschreibung->load('arbeitsvorgaenge');
        $avIndex = $stellenbeschreibung->arbeitsvorgaenge->sortBy('sort_order')->search(fn($a) => $a->id === $av->id);
        $avLabel = 'AV' . ($avIndex + 1);
        return view('stellenbeschreibungen.arbeitsvorgang_edit', compact('stellenbeschreibung', 'av', 'avLabel'));
    }

    public function updateAv(Request $request, Stellenbeschreibung $stellenbeschreibung, Arbeitsvorgang $av)
    {
        $this->authorize('base.stellenbeschreibungen.edit');

        $validated = $request->validate([
            'betreff'      => 'required|string|max:255',
            'beschreibung' => 'nullable|string',
            'anteil'       => 'required|integer|min:0|max:100',
        ]);

        $av->update($validated);

        $this->auditLogger->log('stellenbeschreibungen', 'update', [
            'message' => "AV '{$av->betreff}' in Stellenbeschreibung '{$stellenbeschreibung->bezeichnung}' aktualisiert",
        ]);

        return redirect()->route('stellenbeschreibungen.edit', $stellenbeschreibung)
            ->with('success', "Arbeitsvorgang \"{$av->betreff}\" wurde gespeichert.");
    }

    public function destroyAv(Stellenbeschreibung $stellenbeschreibung, Arbeitsvorgang $av)
    {
        $this->authorize('base.stellenbeschreibungen.edit');
        $betreff = $av->betreff;
        $av->delete();

        $this->auditLogger->log('stellenbeschreibungen', 'update', [
            'message' => "AV '{$betreff}' aus Stellenbeschreibung '{$stellenbeschreibung->bezeichnung}' gelöscht",
        ]);

        return redirect()->route('stellenbeschreibungen.edit', $stellenbeschreibung)
            ->with('success', "Arbeitsvorgang \"{$betreff}\" wurde gelöscht.");
    }
}
