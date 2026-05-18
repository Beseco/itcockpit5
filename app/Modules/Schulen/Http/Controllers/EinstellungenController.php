<?php

namespace App\Modules\Schulen\Http\Controllers;

use App\Modules\Schulen\Models\DienstKategorie;
use App\Modules\Schulen\Models\SchulTyp;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class EinstellungenController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function index()
    {
        $schulTypen  = SchulTyp::orderBy('sort_order')->orderBy('name')->get();
        $kategorien  = DienstKategorie::orderBy('sort_order')->orderBy('name')->get();
        $farbenOptionen = SchulTyp::FARBEN;

        return view('schulen::einstellungen.index', compact('schulTypen', 'kategorien', 'farbenOptionen'));
    }

    // ── Schultypen ──────────────────────────────────────────────────────────

    public function storeTyp(Request $request)
    {
        $validated = $this->validateTyp($request);
        $typ = SchulTyp::create($validated);

        $this->auditLogger->logModuleAction('Schulen', 'Schultyp erstellt', ['name' => $typ->name]);

        return redirect()->route('schulen.einstellungen')
            ->with('success', 'Schultyp "' . $typ->name . '" erstellt.');
    }

    public function updateTyp(Request $request, SchulTyp $schulTyp)
    {
        $validated = $this->validateTyp($request);
        $schulTyp->update($validated);

        $this->auditLogger->logModuleAction('Schulen', 'Schultyp aktualisiert', ['name' => $schulTyp->name]);

        return redirect()->route('schulen.einstellungen')
            ->with('success', 'Schultyp "' . $schulTyp->name . '" gespeichert.');
    }

    public function destroyTyp(SchulTyp $schulTyp)
    {
        if ($schulTyp->schulen()->exists()) {
            return redirect()->route('schulen.einstellungen')
                ->with('error', 'Schultyp "' . $schulTyp->name . '" kann nicht gelöscht werden – er wird noch von Schulen verwendet.');
        }

        $name = $schulTyp->name;
        $schulTyp->delete();

        $this->auditLogger->logModuleAction('Schulen', 'Schultyp gelöscht', ['name' => $name]);

        return redirect()->route('schulen.einstellungen')
            ->with('success', 'Schultyp "' . $name . '" gelöscht.');
    }

    // ── Kategorien ──────────────────────────────────────────────────────────

    public function storeKategorie(Request $request)
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
        ]);
        DienstKategorie::create($validated);

        return redirect()->route('schulen.einstellungen')
            ->with('success', 'Kategorie erstellt.');
    }

    public function updateKategorie(Request $request, DienstKategorie $kategorie)
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
        ]);
        $kategorie->update($validated);

        return redirect()->route('schulen.einstellungen')
            ->with('success', 'Kategorie "' . $kategorie->name . '" gespeichert.');
    }

    public function destroyKategorie(DienstKategorie $kategorie)
    {
        if ($kategorie->dienstleistungen()->exists()) {
            return redirect()->route('schulen.einstellungen')
                ->with('error', 'Kategorie "' . $kategorie->name . '" kann nicht gelöscht werden – sie enthält noch Dienstleistungen.');
        }

        $name = $kategorie->name;
        $kategorie->delete();

        return redirect()->route('schulen.einstellungen')
            ->with('success', 'Kategorie "' . $name . '" gelöscht.');
    }

    // ────────────────────────────────────────────────────────────────────────

    private function validateTyp(Request $request): array
    {
        return $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'farbe_klassen' => ['required', 'string', 'max:100'],
            'sort_order'    => ['nullable', 'integer'],
        ]);
    }
}
