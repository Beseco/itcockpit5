<?php

namespace App\Modules\Schulen\Http\Controllers;

use App\Modules\Schulen\Models\DienstKategorie;
use App\Modules\Schulen\Models\Dienstleistung;
use App\Modules\Schulen\Models\Schule;
use App\Modules\Schulen\Models\SchuleDienstleistung;
use App\Modules\Schulen\Models\SchulTyp;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class MatrixController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function index(Request $request)
    {
        $filterTyp       = $request->get('filter_typ', '');
        $filterKategorie = $request->get('filter_kategorie', '');

        $schulenQuery = Schule::with('schulTyp')->orderBy('schul_typ_id')->orderBy('sort_order')->orderBy('name');
        if (filled($filterTyp)) {
            $schulenQuery->where('schul_typ_id', (int) $filterTyp);
        }
        $schulen = $schulenQuery->get();

        $diensteQuery = Dienstleistung::with('kategorie')->aktiv()
            ->orderBy('sort_order')->orderBy('name');
        if (filled($filterKategorie)) {
            $diensteQuery->where('dienst_kategorie_id', (int) $filterKategorie);
        }
        $dienstleistungen = $diensteQuery->get();

        // Pivot-Daten einlesen: [schule_id][dienst_id] => pivot
        $pivots = SchuleDienstleistung::whereIn('schule_id', $schulen->pluck('id'))
            ->whereIn('dienstleistung_id', $dienstleistungen->pluck('id'))
            ->get()
            ->groupBy('schule_id');

        $kategorien  = DienstKategorie::orderBy('sort_order')->orderBy('name')->get();
        $schulTypen  = SchulTyp::orderBy('sort_order')->orderBy('name')->get();

        // Schulen nach Typ gruppieren
        $schulenGruppen = $schulen->groupBy('schul_typ_id');

        // Dienstleistungen nach Kategorie gruppieren
        $diensteGruppen = $dienstleistungen->groupBy('dienst_kategorie_id');

        return view('schulen::matrix.index', compact(
            'schulen', 'dienstleistungen', 'pivots',
            'kategorien', 'schulTypen', 'schulenGruppen', 'diensteGruppen',
            'filterTyp', 'filterKategorie'
        ));
    }

    public function updateCell(Request $request, Schule $schule, Dienstleistung $dienstleistung)
    {
        $validated = $request->validate([
            'status'          => ['required', 'in:aktiv,geplant,nicht_vorhanden,nicht_gewuenscht,nicht_moeglich'],
            'stunden_override'=> ['nullable', 'numeric', 'min:0'],
            'notizen'         => ['nullable', 'string', 'max:500'],
        ]);

        $schule->dienstleistungen()->syncWithoutDetaching([
            $dienstleistung->id => $validated,
        ]);

        $this->auditLogger->logModuleAction('Schulen', 'Matrix-Status geändert', [
            'schule'   => $schule->name,
            'dienst'   => $dienstleistung->name,
            'status'   => $validated['status'],
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('schulen.matrix')
            ->with('success', 'Status aktualisiert.');
    }
}
