<?php

namespace App\Modules\Entsorgung\Http\Controllers;

use App\Modules\AdUsers\Models\AdUser;
use App\Modules\Entsorgung\Models\Entsorgung;
use App\Modules\Entsorgung\Models\EntsorgungGrund;
use App\Modules\Entsorgung\Models\EntsorgungHersteller;
use App\Modules\Entsorgung\Models\EntsorgungTyp;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class EntsorgungController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');

        $query = Entsorgung::with('nutzer')
            ->orderBy('datum', 'desc')
            ->orderBy('created_at', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name',             'like', "%{$search}%")
                  ->orWhere('modell',          'like', "%{$search}%")
                  ->orWhere('hersteller',      'like', "%{$search}%")
                  ->orWhere('inventar',        'like', "%{$search}%")
                  ->orWhere('entsorger',       'like', "%{$search}%")
                  ->orWhere('user',            'like', "%{$search}%")
                  ->orWhere('entsorgungsgrund','like', "%{$search}%")
                  ->orWhereHas('nutzer', fn($u) => $u->where('anzeigename', 'like', "%{$search}%")
                      ->orWhere('vorname', 'like', "%{$search}%")
                      ->orWhere('nachname', 'like', "%{$search}%"));
            });
        }

        $perPage = in_array((int) $request->get('per_page', 25), [25, 50, 100, 250]) ? (int) $request->get('per_page', 25) : 25;
        $eintraege = $query->paginate($perPage)->withQueryString();
        $canEdit   = Auth::user()->hasModulePermission('entsorgung', 'edit');
        $canDelete = Auth::user()->hasModulePermission('entsorgung', 'delete');

        return view('entsorgung::index', compact('eintraege', 'search', 'canEdit', 'canDelete', 'perPage'));
    }

    public function create()
    {
        return view('entsorgung::create', $this->formData());
    }

    public function store(Request $request)
    {
        $validated = $this->validateForm($request);
        [$hersteller, $typ, $entsorgungsgrund] = $this->resolveListFields($request);

        $adUserId = $request->ad_user_id ?: null;
        $userName = $adUserId ? AdUser::find($adUserId)?->anzeigenameOrName : null;

        Entsorgung::create([
            'name'             => $request->name,
            'modell'           => $request->modell,
            'hersteller'       => $hersteller,
            'typ'              => $typ,
            'inventar'         => str_pad($request->inventar, 10, '0', STR_PAD_LEFT),
            'entsorger'        => Auth::user()->name,
            'user'             => $userName,
            'ad_user_id'       => $adUserId,
            'grundschutz'      => (bool) $request->grundschutz,
            'grundschutzgrund' => $request->grundschutzgrund,
            'entsorgungsgrund' => $entsorgungsgrund,
            'datum'            => now()->toDateString(),
            'created_by'       => Auth::id(),
        ]);

        return redirect()->route('entsorgung.index')
            ->with('success', 'Entsorgung wurde erfolgreich eingetragen.');
    }

    public function edit(Entsorgung $eintrag)
    {
        return view('entsorgung::edit', array_merge(['eintrag' => $eintrag], $this->formData()));
    }

    public function update(Request $request, Entsorgung $eintrag)
    {
        $this->validateForm($request);
        [$hersteller, $typ, $entsorgungsgrund] = $this->resolveListFields($request);

        $adUserId = $request->ad_user_id ?: null;
        $userName = $adUserId ? AdUser::find($adUserId)?->anzeigenameOrName : null;

        $eintrag->update([
            'name'             => $request->name,
            'modell'           => $request->modell,
            'hersteller'       => $hersteller,
            'typ'              => $typ,
            'inventar'         => str_pad($request->inventar, 10, '0', STR_PAD_LEFT),
            'user'             => $userName,
            'ad_user_id'       => $adUserId,
            'grundschutz'      => (bool) $request->grundschutz,
            'grundschutzgrund' => $request->grundschutzgrund,
            'entsorgungsgrund' => $entsorgungsgrund,
        ]);

        return redirect()->route('entsorgung.index')
            ->with('success', 'Eintrag wurde gespeichert.');
    }

    public function destroy(Entsorgung $eintrag)
    {
        $user = Auth::user();

        // Unbegrenzt löschen darf nur, wer das delete-Recht hat.
        // Alle anderen (mind. edit durch Route) dürfen nur innerhalb der 1-Stunden-Frist löschen.
        if (!$user->hasModulePermission('entsorgung', 'delete') && !$eintrag->kannGeloeschtWerden()) {
            abort(403, 'Löschen nur innerhalb von 1 Stunde nach Erstellung möglich.');
        }

        $eintrag->delete();

        return redirect()->route('entsorgung.index')
            ->with('success', 'Eintrag wurde gelöscht.');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function formData(): array
    {
        return [
            'typen'       => EntsorgungTyp::orderBy('name')->pluck('name'),
            'gruende'     => EntsorgungGrund::orderBy('name')->pluck('name'),
            'hersteller'  => EntsorgungHersteller::orderBy('name')->pluck('name'),
            'adUsers'     => AdUser::aktiv()->orderBy('anzeigename')
                                ->get(['id', 'anzeigename', 'vorname', 'nachname', 'samaccountname']),
        ];
    }

    private function validateForm(Request $request): array
    {
        return $request->validate([
            'name'                   => ['required', 'string', 'max:255'],
            'modell'                 => ['required', 'string', 'max:255'],
            'hersteller_select'      => ['required', 'string'],
            'hersteller_custom'      => ['required_if:hersteller_select,__other__', 'nullable', 'string', 'max:255'],
            'typ_select'             => ['nullable', 'string'],
            'typ_custom'             => ['required_if:typ_select,__other__', 'nullable', 'string', 'max:100'],
            'inventar'               => ['required', 'digits_between:1,10'],
            'ad_user_id'             => ['nullable', 'exists:adusers,id'],
            'grundschutz'            => ['required', 'in:1,0'],
            'grundschutzgrund'       => ['required_if:grundschutz,0', 'nullable', 'string'],
            'entsorgungsgrund_select' => ['required', 'string'],
            'entsorgungsgrund_custom' => ['required_if:entsorgungsgrund_select,__other__', 'nullable', 'string', 'max:255'],
        ]);
    }

    private function resolveListFields(Request $request): array
    {
        // Hersteller
        if ($request->hersteller_select === '__other__') {
            $hersteller = trim($request->hersteller_custom);
            EntsorgungHersteller::firstOrCreate(['name' => $hersteller]);
        } else {
            $hersteller = $request->hersteller_select;
        }

        // Gerätetyp
        $typ = null;
        if ($request->typ_select === '__other__') {
            $typ = trim($request->typ_custom);
            EntsorgungTyp::firstOrCreate(['name' => $typ]);
        } elseif ($request->typ_select) {
            $typ = $request->typ_select;
        }

        // Entsorgungsgrund
        if ($request->entsorgungsgrund_select === '__other__') {
            $entsorgungsgrund = trim($request->entsorgungsgrund_custom);
            EntsorgungGrund::firstOrCreate(['name' => $entsorgungsgrund]);
        } else {
            $entsorgungsgrund = $request->entsorgungsgrund_select;
        }

        return [$hersteller, $typ, $entsorgungsgrund];
    }
}
