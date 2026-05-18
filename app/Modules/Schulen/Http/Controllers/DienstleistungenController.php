<?php

namespace App\Modules\Schulen\Http\Controllers;

use App\Modules\Schulen\Models\DienstKategorie;
use App\Modules\Schulen\Models\Dienstleistung;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DienstleistungenController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function index()
    {
        $kategorien = DienstKategorie::with(['dienstleistungen' => function ($q) {
            $q->orderBy('sort_order')->orderBy('name');
        }])->orderBy('sort_order')->orderBy('name')->get();

        $ohneKategorie = Dienstleistung::whereNull('dienst_kategorie_id')
            ->orderBy('sort_order')->orderBy('name')->get();

        return view('schulen::dienstleistungen.index', compact('kategorien', 'ohneKategorie'));
    }

    public function show(Dienstleistung $dienstleistung)
    {
        $dienstleistung->load('kategorie');
        $schulenAktiv = $dienstleistung->schulen()->wherePivot('status', 'aktiv')->count();
        return view('schulen::dienstleistungen.show', compact('dienstleistung', 'schulenAktiv'));
    }

    public function create()
    {
        $kategorien = DienstKategorie::orderBy('sort_order')->orderBy('name')->get();
        return view('schulen::dienstleistungen.create', ['dienstleistung' => null, 'kategorien' => $kategorien]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateDienst($request);
        $validated['is_active'] = $request->boolean('is_active');
        $dienst    = Dienstleistung::create($validated);

        $this->auditLogger->logModuleAction('Schulen', 'Dienstleistung erstellt', [
            'id'   => $dienst->id,
            'name' => $dienst->name,
        ]);

        return redirect()->route('schulen.dienste.index')
            ->with('success', 'Dienstleistung "' . $dienst->name . '" erstellt.');
    }

    public function edit(Dienstleistung $dienstleistung)
    {
        $kategorien = DienstKategorie::orderBy('sort_order')->orderBy('name')->get();
        return view('schulen::dienstleistungen.edit', compact('dienstleistung', 'kategorien'));
    }

    public function update(Request $request, Dienstleistung $dienstleistung)
    {
        $validated = $this->validateDienst($request);
        $validated['is_active'] = $request->boolean('is_active');
        $dienstleistung->update($validated);

        $this->auditLogger->logModuleAction('Schulen', 'Dienstleistung aktualisiert', [
            'id'   => $dienstleistung->id,
            'name' => $dienstleistung->name,
        ]);

        return redirect()->route('schulen.dienste.index')
            ->with('success', 'Dienstleistung erfolgreich gespeichert.');
    }

    public function destroy(Dienstleistung $dienstleistung)
    {
        $name = $dienstleistung->name;
        $dienstleistung->delete();

        $this->auditLogger->logModuleAction('Schulen', 'Dienstleistung gelöscht', ['name' => $name]);

        return redirect()->route('schulen.dienste.index')
            ->with('success', 'Dienstleistung "' . $name . '" gelöscht.');
    }

    public function storeKategorie(Request $request)
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
        ]);
        DienstKategorie::create($validated);

        return redirect()->route('schulen.dienste.index')
            ->with('success', 'Kategorie erstellt.');
    }

    public function updateKategorie(Request $request, DienstKategorie $kategorie)
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
        ]);
        $kategorie->update($validated);

        return redirect()->route('schulen.dienste.index')
            ->with('success', 'Kategorie aktualisiert.');
    }

    public function destroyKategorie(DienstKategorie $kategorie)
    {
        $name = $kategorie->name;
        $kategorie->delete();

        return redirect()->route('schulen.dienste.index')
            ->with('success', 'Kategorie "' . $name . '" gelöscht.');
    }

    private function validateDienst(Request $request): array
    {
        return $request->validate([
            'dienst_kategorie_id' => ['nullable', 'integer', 'exists:dienst_kategorien,id'],
            'name'                => ['required', 'string', 'max:255'],
            'beschreibung'        => ['nullable', 'string'],
            'dokumentation_url'   => ['nullable', 'url', 'max:500'],
            'stunden_modus'       => ['required', 'in:jahresstunden,wochenstunden'],
            'stunden_wert'        => ['nullable', 'numeric', 'min:0'],
            'sort_order'          => ['nullable', 'integer'],
            'is_active'           => ['nullable', 'boolean'],
        ]);
    }
}
