<?php

namespace App\Http\Controllers;

use App\Models\AnsprechpartnerFunktion;
use App\Models\Dienstleister;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DienstleisterController extends Controller
{
    protected AuditLogger $auditLogger;

    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    /**
     * Liste mit Suche & Sortierung
     */
    public function index(Request $request)
    {
        $this->authorize('dienstleister.view');
        $allowedSorts = ['firmenname', 'ort', 'dienstleister_typ', 'status', 'bewertung_gesamt'];
        $sort  = in_array($request->get('sort'), $allowedSorts) ? $request->get('sort') : 'firmenname';
        $order = $request->get('order') === 'DESC' ? 'DESC' : 'ASC';
        $search = $request->get('search', '');

        $query = Dienstleister::orderBy($sort, $order);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('firmenname', 'LIKE', "%{$search}%")
                  ->orWhere('ort', 'LIKE', "%{$search}%")
                  ->orWhere('plz', 'LIKE', "%{$search}%")
                  ->orWhere('strasse', 'LIKE', "%{$search}%")
                  ->orWhere('dienstleister_typ', 'LIKE', "%{$search}%")
                  ->orWhere('fachgebiet', 'LIKE', "%{$search}%")
                  ->orWhere('leistungsbeschreibung', 'LIKE', "%{$search}%")
                  ->orWhere('bemerkungen', 'LIKE', "%{$search}%")
                  ->orWhere('bewertungsnotiz', 'LIKE', "%{$search}%")
                  ->orWhere('verantwortliche_stelle', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('website', 'LIKE', "%{$search}%");
            });
        }

        $dienstleister = $query->paginate(20)->withQueryString();

        return view('dienstleister.index', compact('dienstleister', 'sort', 'order', 'search'));
    }

    /**
     * Formular: Neuer Dienstleister
     */
    public function create()
    {
        $this->authorize('dienstleister.create');

        return view('dienstleister.create', [
            'typen'      => Dienstleister::TYPEN,
            'status'     => Dienstleister::STATUS,
            'funktionen' => AnsprechpartnerFunktion::orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    /**
     * Neuen Dienstleister speichern
     */
    public function store(Request $request)
    {
        $this->authorize('dienstleister.create');

        $validated = $this->validateDienstleister($request);
        $validated['angelegt_am']    = now();
        $validated['aktualisiert_am'] = now();

        $d = Dienstleister::create($validated);

        $this->syncAnsprechpartner($d, $request->input('kontakte', []));

        $this->auditLogger->log('Dienstleister', 'Dienstleister erstellt', [
            'id'         => $d->id,
            'firmenname' => $d->firmenname,
        ]);

        return redirect()->route('dienstleister.show', $d)->with('success', 'Dienstleister erfolgreich gespeichert.');
    }

    /**
     * Detailansicht
     */
    public function show(Dienstleister $dienstleister)
    {
        $this->authorize('dienstleister.view');

        return view('dienstleister.show', [
            'dienstleister' => $dienstleister->load('kontakte'),
        ]);
    }

    /**
     * Formular: Dienstleister bearbeiten
     */
    public function edit(Dienstleister $dienstleister)
    {
        $this->authorize('dienstleister.edit');

        return view('dienstleister.edit', [
            'dienstleister' => $dienstleister->load('kontakte'),
            'typen'         => Dienstleister::TYPEN,
            'status'        => Dienstleister::STATUS,
            'funktionen'    => AnsprechpartnerFunktion::orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    /**
     * Dienstleister aktualisieren
     */
    public function update(Request $request, Dienstleister $dienstleister)
    {
        $this->authorize('dienstleister.edit');

        $validated = $this->validateDienstleister($request);
        $validated['aktualisiert_am'] = now();

        $dienstleister->update($validated);

        $this->syncAnsprechpartner($dienstleister, $request->input('kontakte', []));

        $this->auditLogger->log('Dienstleister', 'Dienstleister aktualisiert', [
            'id'         => $dienstleister->id,
            'firmenname' => $dienstleister->firmenname,
        ]);

        return redirect()->route('dienstleister.show', $dienstleister)->with('success', 'Dienstleister erfolgreich aktualisiert.');
    }

    /**
     * Dienstleister löschen
     */
    public function destroy(Dienstleister $dienstleister)
    {
        $this->authorize('dienstleister.delete');

        $data = ['id' => $dienstleister->id, 'firmenname' => $dienstleister->firmenname];

        $dienstleister->delete();

        $this->auditLogger->log('Dienstleister', 'Dienstleister gelöscht', $data);

        return redirect()->route('dienstleister.index')->with('success', 'Dienstleister erfolgreich gelöscht.');
    }

    private function syncAnsprechpartner(Dienstleister $dienstleister, array $items): void
    {
        $dienstleister->kontakte()->delete();

        foreach ($items as $i => $ap) {
            $nachname = trim($ap['nachname'] ?? '');
            if ($nachname === '') {
                continue;
            }

            $dienstleister->ansprechpartner()->create([
                'anrede'     => $ap['anrede']   ?? '',
                'vorname'    => $ap['vorname']  ?? '',
                'nachname'   => $nachname,
                'funktion'   => $ap['funktion'] ?? '',
                'telefon'    => $ap['telefon']  ?? '',
                'handy'      => $ap['handy']    ?? '',
                'email'      => $ap['email']    ?? '',
                'notiz'      => $ap['notiz']    ?? '',
                'sort_order' => $i,
            ]);
        }
    }

    private function validateDienstleister(Request $request): array
    {
        return $request->validate([
            'firmenname'                         => ['required', 'string', 'max:255'],
            'strasse'                            => ['nullable', 'string', 'max:255'],
            'plz'                                => ['nullable', 'string', 'max:20'],
            'ort'                                => ['nullable', 'string', 'max:255'],
            'land'                               => ['nullable', 'string', 'max:100'],
            'website'                            => ['nullable', 'string', 'max:255'],
            'email'                              => ['nullable', 'email', 'max:255'],
            'telefon'                            => ['nullable', 'string', 'max:100'],
            'bemerkungen'                        => ['nullable', 'string'],
            'dienstleister_typ'                  => ['nullable', Rule::in(array_keys(Dienstleister::TYPEN))],
            'fachgebiet'                         => ['nullable', 'string', 'max:255'],
            'leistungsbeschreibung'              => ['nullable', 'string'],
            'kritischer_dienstleister'           => ['boolean'],
            'verarbeitet_personenbezogene_daten' => ['boolean'],
            'av_vertrag_vorhanden'               => ['boolean'],
            'av_vertrag_datum'                   => ['nullable', 'date'],
            'av_bemerkungen'                     => ['nullable', 'string', 'max:255'],
            'status'                             => ['required', Rule::in(array_keys(Dienstleister::STATUS))],
            'bewertung_gesamt'                   => ['nullable', 'integer', 'between:1,5'],
            'bewertung_fachlich'                 => ['nullable', 'integer', 'between:1,5'],
            'bewertung_zuverlaessigkeit'         => ['nullable', 'integer', 'between:1,5'],
            'empfehlung'                         => ['boolean'],
            'bewertungsnotiz'                    => ['nullable', 'string'],
            'verantwortliche_stelle'             => ['nullable', 'string', 'max:255'],
        ], [
            // Checkboxen: wenn nicht gesetzt, auf false setzen
        ]);
    }
}
