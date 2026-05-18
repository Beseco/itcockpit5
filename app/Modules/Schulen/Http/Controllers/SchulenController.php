<?php

namespace App\Modules\Schulen\Http\Controllers;

use App\Modules\Schulen\Models\Dienstleistung;
use App\Modules\Schulen\Models\DienstKategorie;
use App\Modules\Schulen\Models\Schule;
use App\Modules\Schulen\Models\SchuleDienstleistung;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SchulenController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function index(Request $request)
    {
        $search      = $request->get('search', '');
        $filterTyp   = $request->get('filter_typ', '');

        $query = Schule::withCount(['dienstleistungen as aktive_dienste_count' => function ($q) {
            $q->where('schule_dienstleistung.status', 'aktiv');
        }])->orderBy('schultyp')->orderBy('sort_order')->orderBy('name');

        if (filled($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('ort', 'like', "%{$search}%");
            });
        }
        if (filled($filterTyp)) {
            $query->where('schultyp', $filterTyp);
        }

        $schulen = $query->paginate(25)->withQueryString();

        return view('schulen::schulen.index', compact('schulen', 'search', 'filterTyp'));
    }

    public function show(Schule $schule)
    {
        $schule->load(['kontakte', 'dienstleistungen.kategorie']);

        $dienstleistungen = Dienstleistung::with('kategorie')
            ->aktiv()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $pivotMap = $schule->dienstleistungen->keyBy('id');

        return view('schulen::schulen.show', compact('schule', 'dienstleistungen', 'pivotMap'));
    }

    public function create()
    {
        return view('schulen::schulen.create', ['schule' => null]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateSchule($request);
        $schule    = Schule::create($validated);

        $this->auditLogger->logModuleAction('Schulen', 'Schule erstellt', [
            'id'   => $schule->id,
            'name' => $schule->name,
        ]);

        return redirect()->route('schulen.show', $schule)
            ->with('success', 'Schule "' . $schule->name . '" erfolgreich angelegt.');
    }

    public function edit(Schule $schule)
    {
        return view('schulen::schulen.edit', compact('schule'));
    }

    public function update(Request $request, Schule $schule)
    {
        $validated = $this->validateSchule($request);
        $schule->update($validated);

        $this->auditLogger->logModuleAction('Schulen', 'Schule aktualisiert', [
            'id'   => $schule->id,
            'name' => $schule->name,
        ]);

        return redirect()->route('schulen.show', $schule)
            ->with('success', 'Schule erfolgreich gespeichert.');
    }

    public function destroy(Schule $schule)
    {
        $name = $schule->name;
        $schule->delete();

        $this->auditLogger->logModuleAction('Schulen', 'Schule gelöscht', ['name' => $name]);

        return redirect()->route('schulen.index')
            ->with('success', 'Schule "' . $name . '" wurde gelöscht.');
    }

    public function vze()
    {
        $schulen = Schule::with(['dienstleistungen' => function ($q) {
            $q->aktiv();
        }])->orderBy('schultyp')->orderBy('sort_order')->orderBy('name')->get();

        $dienstleistungen = Dienstleistung::aktiv()->get();

        $vzeJahresstunden = Dienstleistung::VZE_JAHRESSTUNDEN;

        // IST: nur aktive Pivot-Einträge
        $istStundenGesamt = 0;
        $schulenVze = [];

        foreach ($schulen as $schule) {
            $istStunden  = 0;
            $sollStunden = 0;

            foreach ($dienstleistungen as $dienst) {
                $pivot = $schule->dienstleistungen->firstWhere('id', $dienst->id)?->pivot;
                $stunden = $pivot?->stunden_override ?? $dienst->jahresstunden() ?? 0;

                if ($pivot && $pivot->status === 'aktiv') {
                    $istStunden += $stunden;
                }
                $sollStunden += $stunden;
            }

            $istStundenGesamt += $istStunden;

            $schulenVze[] = [
                'schule'       => $schule,
                'ist_stunden'  => $istStunden,
                'ist_vze'      => round($istStunden / $vzeJahresstunden, 2),
                'soll_stunden' => $sollStunden,
                'soll_vze'     => round($sollStunden / $vzeJahresstunden, 2),
            ];
        }

        // SOLL: alle Schulen × alle aktiven Dienste
        $sollStundenGesamt = 0;
        foreach ($schulen as $schule) {
            foreach ($dienstleistungen as $dienst) {
                $pivot = $schule->dienstleistungen->firstWhere('id', $dienst->id)?->pivot;
                $sollStundenGesamt += $pivot?->stunden_override ?? $dienst->jahresstunden() ?? 0;
            }
        }

        $istVzeGesamt  = round($istStundenGesamt / $vzeJahresstunden, 2);
        $sollVzeGesamt = round($sollStundenGesamt / $vzeJahresstunden, 2);

        // Pro Dienstleistung: Anzahl aktiver Schulen + Stunden IST
        $dienstStats = [];
        foreach ($dienstleistungen as $dienst) {
            $aktivCount   = 0;
            $istStDienst  = 0;
            foreach ($schulen as $schule) {
                $pivot = $schule->dienstleistungen->firstWhere('id', $dienst->id)?->pivot;
                if ($pivot && $pivot->status === 'aktiv') {
                    $aktivCount++;
                    $istStDienst += $pivot->stunden_override ?? $dienst->jahresstunden() ?? 0;
                }
            }
            $dienstStats[] = [
                'dienst'       => $dienst,
                'aktiv_count'  => $aktivCount,
                'ist_stunden'  => $istStDienst,
                'ist_vze'      => round($istStDienst / $vzeJahresstunden, 2),
            ];
        }

        return view('schulen::vze.index', compact(
            'schulenVze', 'dienstStats',
            'istVzeGesamt', 'sollVzeGesamt',
            'istStundenGesamt', 'sollStundenGesamt'
        ));
    }

    private function validateSchule(Request $request): array
    {
        return $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'schultyp'   => ['required', 'in:realschule,gymnasium,sonstige'],
            'strasse'    => ['nullable', 'string', 'max:255'],
            'plz'        => ['nullable', 'string', 'max:10'],
            'ort'        => ['nullable', 'string', 'max:255'],
            'telefon'    => ['nullable', 'string', 'max:100'],
            'email'      => ['nullable', 'email', 'max:255'],
            'website'    => ['nullable', 'url', 'max:500'],
            'notizen'    => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
        ]);
    }
}
