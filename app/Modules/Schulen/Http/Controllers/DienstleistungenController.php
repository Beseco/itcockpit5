<?php

namespace App\Modules\Schulen\Http\Controllers;

use App\Models\Dienstleister;
use App\Modules\Schulen\Models\DienstKategorie;
use App\Modules\Schulen\Models\Dienstleistung;
use App\Modules\Schulen\Models\DienstleistungZustaendigkeit;
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
        $dienstleistung->load(['kategorie', 'dienstleister', 'zustaendigkeiten']);

        $schulenGesamt = \App\Modules\Schulen\Models\Schule::count();
        $jahresstunden = $dienstleistung->jahresstunden();

        // Aktive Schulen mit Pivot laden
        $aktivePivots = $dienstleistung->schulen()
            ->wherePivot('status', 'aktiv')
            ->withPivot('stunden_override', 'notizen')
            ->orderBy('name')
            ->get();

        $schulenAktiv    = $aktivePivots->count();
        $istStundenGesamt = $aktivePivots->sum(function ($schule) use ($jahresstunden) {
            return $schule->pivot->stunden_override ?? $jahresstunden ?? 0;
        });

        $vzeJahresstunden = \App\Modules\Schulen\Models\Dienstleistung::VZE_JAHRESSTUNDEN;

        $vze = [
            'pro_schule'    => $jahresstunden !== null ? round($jahresstunden / $vzeJahresstunden, 3) : null,
            'ist_gesamt'    => round($istStundenGesamt / $vzeJahresstunden, 3),
            'alle_schulen'  => $jahresstunden !== null ? round($schulenGesamt * $jahresstunden / $vzeJahresstunden, 3) : null,
            'ist_stunden'   => $istStundenGesamt,
        ];

        return view('schulen::dienstleistungen.show', compact(
            'dienstleistung', 'schulenAktiv', 'schulenGesamt', 'vze', 'aktivePivots'
        ));
    }

    public function create()
    {
        $kategorien   = DienstKategorie::orderBy('sort_order')->orderBy('name')->get();
        $alleDienstleister = Dienstleister::where('status', 'aktiv')->orderBy('firmenname')->get();
        return view('schulen::dienstleistungen.create', [
            'dienstleistung'     => null,
            'kategorien'         => $kategorien,
            'alleDienstleister'  => $alleDienstleister,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateDienst($request);
        $validated['is_active'] = $request->boolean('is_active');
        $dienst = Dienstleistung::create($validated);

        $this->syncDienstleister($dienst, $request);
        $this->syncZustaendigkeiten($dienst, $request);

        $this->auditLogger->logModuleAction('Schulen', 'Dienstleistung erstellt', [
            'id'   => $dienst->id,
            'name' => $dienst->name,
        ]);

        return redirect()->route('schulen.dienste.index')
            ->with('success', 'Dienstleistung "' . $dienst->name . '" erstellt.');
    }

    public function edit(Dienstleistung $dienstleistung)
    {
        $dienstleistung->load(['dienstleister', 'zustaendigkeiten']);
        $kategorien        = DienstKategorie::orderBy('sort_order')->orderBy('name')->get();
        $alleDienstleister = Dienstleister::where('status', 'aktiv')->orderBy('firmenname')->get();
        return view('schulen::dienstleistungen.edit', compact(
            'dienstleistung', 'kategorien', 'alleDienstleister'
        ));
    }

    public function update(Request $request, Dienstleistung $dienstleistung)
    {
        $validated = $this->validateDienst($request);
        $validated['is_active'] = $request->boolean('is_active');
        $dienstleistung->update($validated);

        $this->syncDienstleister($dienstleistung, $request);
        $this->syncZustaendigkeiten($dienstleistung, $request);

        $this->auditLogger->logModuleAction('Schulen', 'Dienstleistung aktualisiert', [
            'id'   => $dienstleistung->id,
            'name' => $dienstleistung->name,
        ]);

        return redirect()->route('schulen.dienste.show', $dienstleistung)
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
            'dienst_kategorie_id'            => ['nullable', 'integer', 'exists:dienst_kategorien,id'],
            'name'                            => ['required', 'string', 'max:255'],
            'beschreibung'                    => ['nullable', 'string'],
            'dokumentation_url'               => ['nullable', 'url', 'max:500'],
            'stunden_modus'                   => ['required', 'in:jahresstunden,wochenstunden'],
            'stunden_wert'                    => ['nullable', 'numeric', 'min:0'],
            'sort_order'                      => ['nullable', 'integer'],
            'is_active'                       => ['nullable', 'boolean'],
            'dienstleister_ids'               => ['nullable', 'array'],
            'dienstleister_ids.*'             => ['integer', 'exists:dienstleister,id'],
            'zustaendigkeiten'                => ['nullable', 'array'],
            'zustaendigkeiten.*.aufgabe'      => ['required', 'string', 'max:200'],
            'zustaendigkeiten.*.lra_it'       => ['nullable', 'string', 'max:200'],
            'zustaendigkeiten.*.schule_sb'    => ['nullable', 'string', 'max:200'],
            'zustaendigkeiten.*.externer_dl'  => ['nullable', 'string', 'max:200'],
        ]);
    }

    private function syncDienstleister(Dienstleistung $dienst, Request $request): void
    {
        $ids = array_filter((array) $request->input('dienstleister_ids', []));
        $dienst->dienstleister()->sync($ids);
    }

    private function syncZustaendigkeiten(Dienstleistung $dienst, Request $request): void
    {
        $dienst->zustaendigkeiten()->delete();

        foreach ((array) $request->input('zustaendigkeiten', []) as $i => $row) {
            if (empty($row['aufgabe'])) {
                continue;
            }
            DienstleistungZustaendigkeit::create([
                'dienstleistung_id' => $dienst->id,
                'aufgabe'           => $row['aufgabe'],
                'lra_it'            => $row['lra_it'] ?? null,
                'schule_sb'         => $row['schule_sb'] ?? null,
                'externer_dl'       => $row['externer_dl'] ?? null,
                'sort_order'        => $i,
            ]);
        }
    }
}
