<?php

namespace App\Modules\Schulen\Http\Controllers;

use App\Models\AuditLog;
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
        // Filter in Session speichern (leere Werte überschreiben bewusst → Reset funktioniert)
        if ($request->isMethod('GET')) {
            if ($request->has('filter_typ') || $request->has('filter_kategorie') || $request->has('filter_eintrag_typ')) {
                session([
                    'schulen_matrix_filter_typ'         => $request->get('filter_typ', ''),
                    'schulen_matrix_filter_kategorie'   => $request->get('filter_kategorie', ''),
                    'schulen_matrix_filter_eintrag_typ' => $request->get('filter_eintrag_typ', ''),
                ]);
            }
        }

        $filterTyp        = $request->get('filter_typ',         session('schulen_matrix_filter_typ', ''));
        $filterKategorie  = $request->get('filter_kategorie',   session('schulen_matrix_filter_kategorie', ''));
        $filterEintragTyp = $request->get('filter_eintrag_typ', session('schulen_matrix_filter_eintrag_typ', ''));

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
        if ($filterEintragTyp === 'dienstleistung') {
            $diensteQuery->where('betriebsvoraussetzung', false);
        } elseif ($filterEintragTyp === 'voraussetzung') {
            $diensteQuery->where('betriebsvoraussetzung', true);
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
            'filterTyp', 'filterKategorie', 'filterEintragTyp'
        ));
    }

    public function updateCell(Request $request, Schule $schule, Dienstleistung $dienstleistung)
    {
        $validated = $request->validate([
            'status'          => ['required', 'in:aktiv,geplant,nicht_vorhanden,nicht_gewuenscht,nicht_moeglich'],
            'stunden_override'=> ['nullable', 'numeric', 'min:0'],
            'notizen'         => ['nullable', 'string', 'max:500'],
        ]);

        $oldPivot  = SchuleDienstleistung::where('schule_id', $schule->id)
            ->where('dienstleistung_id', $dienstleistung->id)
            ->first();
        $oldStatus = $oldPivot?->status ?? 'nicht_vorhanden';

        $schule->dienstleistungen()->syncWithoutDetaching([
            $dienstleistung->id => $validated,
        ]);

        $this->auditLogger->logModuleAction('Schulen', 'Matrix-Status geändert', [
            'schule_id'  => $schule->id,
            'schule'     => $schule->name,
            'dienst_id'  => $dienstleistung->id,
            'dienst'     => $dienstleistung->name,
            'alt'        => $oldStatus,
            'neu'        => $validated['status'],
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('schulen.matrix')
            ->with('success', 'Status aktualisiert.');
    }

    public function protokoll(Request $request)
    {
        $filterSchule  = $request->get('schule_id', '');
        $filterDienst  = $request->get('dienst_id', '');

        $query = AuditLog::with('user')
            ->where('module', 'Schulen')
            ->where('action', 'Matrix-Status geändert')
            ->orderByDesc('created_at');

        if (filled($filterSchule)) {
            $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(payload, '$.schule_id')) = ?", [$filterSchule]);
        }
        if (filled($filterDienst)) {
            $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(payload, '$.dienst_id')) = ?", [$filterDienst]);
        }

        $eintraege    = $query->paginate(50)->withQueryString();
        $schulen      = Schule::orderBy('name')->get();
        $dienste      = Dienstleistung::orderBy('name')->get();

        return view('schulen::protokoll.index', compact(
            'eintraege', 'schulen', 'dienste', 'filterSchule', 'filterDienst'
        ));
    }
}
