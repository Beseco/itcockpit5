<?php

namespace App\Modules\Entsorgung\Http\Controllers;

use App\Models\Dienstleister;
use App\Modules\AdUsers\Models\AdUser;
use App\Modules\Entsorgung\Models\Entsorgung;
use App\Modules\Entsorgung\Models\EntsorgungGrund;
use App\Modules\Entsorgung\Models\EntsorgungTyp;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class EntsorgungController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');

        $query = Entsorgung::with(['nutzer', 'dienstleister'])
            ->orderBy('datum', 'desc')
            ->orderBy('created_at', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name',            'like', "%{$search}%")
                  ->orWhere('modell',         'like', "%{$search}%")
                  ->orWhere('hersteller',     'like', "%{$search}%")
                  ->orWhere('inventar',       'like', "%{$search}%")
                  ->orWhere('entsorger',      'like', "%{$search}%")
                  ->orWhere('user',           'like', "%{$search}%")
                  ->orWhere('entsorgungsgrund','like', "%{$search}%")
                  ->orWhereHas('nutzer', fn($u) => $u->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('dienstleister', fn($d) => $d->where('firmenname', 'like', "%{$search}%"));
            });
        }

        $eintraege = $query->paginate(50)->withQueryString();
        $canDelete  = Auth::user()->hasModulePermission('entsorgung', 'delete');

        return view('entsorgung::index', compact('eintraege', 'search', 'canDelete'));
    }

    public function create()
    {
        $typen        = EntsorgungTyp::orderBy('name')->pluck('name');
        $gruende      = EntsorgungGrund::orderBy('name')->pluck('name');
        $dienstleister = Dienstleister::where('status', 'aktiv')
            ->orderBy('firmenname')
            ->get(['id', 'firmenname']);
        $adUsers = AdUser::aktiv()->orderBy('anzeigename')->get(['id', 'anzeigename', 'vorname', 'nachname', 'samaccountname']);

        return view('entsorgung::create', compact('typen', 'gruende', 'dienstleister', 'adUsers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'                    => ['required', 'string', 'max:255'],
            'modell'                  => ['required', 'string', 'max:255'],
            'hersteller_select'       => ['required', 'string'],
            'hersteller_custom'       => ['required_if:hersteller_select,__other__', 'nullable', 'string', 'max:255'],
            'typ_select'              => ['nullable', 'string'],
            'typ_custom'              => ['required_if:typ_select,__other__', 'nullable', 'string', 'max:100'],
            'inventar'                => ['required', 'digits_between:1,10'],
            'ad_user_id'              => ['nullable', 'exists:adusers,id'],
            'grundschutz'             => ['required', 'in:1,0'],
            'grundschutzgrund'        => ['required_if:grundschutz,0', 'nullable', 'string'],
            'entsorgungsgrund_select'  => ['required', 'string'],
            'entsorgungsgrund_custom'  => ['required_if:entsorgungsgrund_select,__other__', 'nullable', 'string', 'max:255'],
        ]);

        // Gerätetyp auflösen
        $typ = null;
        if ($request->typ_select === '__other__') {
            $typ = trim($request->typ_custom);
            EntsorgungTyp::firstOrCreate(['name' => $typ]);
        } elseif ($request->typ_select) {
            $typ = $request->typ_select;
        }

        // Hersteller / Dienstleister auflösen
        $hersteller      = null;
        $dienstleisterId = null;
        if ($request->hersteller_select === '__other__') {
            $hersteller = trim($request->hersteller_custom);
        } else {
            $dl              = Dienstleister::find((int) $request->hersteller_select);
            $hersteller      = $dl?->firmenname;
            $dienstleisterId = $dl?->id;
        }

        // Entsorgungsgrund auflösen
        $entsorgungsgrund = null;
        if ($request->entsorgungsgrund_select === '__other__') {
            $entsorgungsgrund = trim($request->entsorgungsgrund_custom);
            EntsorgungGrund::firstOrCreate(['name' => $entsorgungsgrund]);
        } else {
            $entsorgungsgrund = $request->entsorgungsgrund_select;
        }

        // Bisheriger Nutzer (AD)
        $adUserId  = $request->ad_user_id ?: null;
        $adUser    = $adUserId ? AdUser::find($adUserId) : null;
        $userName  = $adUser?->anzeigenameOrName;

        Entsorgung::create([
            'name'             => $request->name,
            'modell'           => $request->modell,
            'hersteller'       => $hersteller,
            'dienstleister_id' => $dienstleisterId,
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

    public function destroy(Entsorgung $eintrag)
    {
        $user = Auth::user();

        if (!$eintrag->kannGeloeschtWerden() && !$user->hasModulePermission('entsorgung', 'delete')) {
            abort(403, 'Löschen nur innerhalb von 1 Stunde nach Erstellung möglich.');
        }

        $eintrag->delete();

        return redirect()->route('entsorgung.index')
            ->with('success', 'Eintrag wurde gelöscht.');
    }
}
