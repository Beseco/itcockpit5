<?php

namespace App\Modules\Entsorgung\Http\Controllers;

use App\Modules\Entsorgung\Models\Entsorgung;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class EntsorgungController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');

        $query = Entsorgung::orderBy('datum', 'desc')->orderBy('created_at', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name',       'like', "%{$search}%")
                  ->orWhere('modell',   'like', "%{$search}%")
                  ->orWhere('hersteller','like', "%{$search}%")
                  ->orWhere('inventar', 'like', "%{$search}%")
                  ->orWhere('entsorger','like', "%{$search}%")
                  ->orWhere('user',     'like', "%{$search}%");
            });
        }

        $eintraege = $query->paginate(50)->withQueryString();
        $canDelete = Auth::user()->can('entsorgung.delete');

        return view('entsorgung::index', compact('eintraege', 'search', 'canDelete'));
    }

    public function create()
    {
        return view('entsorgung::create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'modell'           => ['required', 'string', 'max:255'],
            'hersteller'       => ['required', 'string', 'max:255'],
            'typ'              => ['nullable', 'string', 'max:100'],
            'inventar'         => ['required', 'string', 'max:255'],
            'user'             => ['nullable', 'string', 'max:255'],
            'grundschutz'      => ['required', 'in:1,0'],
            'grundschutzgrund' => ['required_if:grundschutz,0', 'nullable', 'string'],
        ]);

        Entsorgung::create([
            'name'             => $request->name,
            'modell'           => $request->modell,
            'hersteller'       => $request->hersteller,
            'typ'              => $request->typ,
            'inventar'         => $request->inventar,
            'entsorger'        => Auth::user()->name,
            'user'             => $request->user,
            'grundschutz'      => (bool) $request->grundschutz,
            'grundschutzgrund' => $request->grundschutzgrund,
            'datum'            => now()->toDateString(),
            'created_by'       => Auth::id(),
        ]);

        return redirect()->route('entsorgung.index')
            ->with('success', 'Entsorgung wurde erfolgreich eingetragen.');
    }

    public function destroy(Entsorgung $eintrag)
    {
        $user = Auth::user();

        if (!$eintrag->kannGeloeschtWerden() && !$user->can('entsorgung.delete')) {
            abort(403, 'Löschen nur innerhalb von 1 Stunde nach Erstellung möglich.');
        }

        $eintrag->delete();

        return redirect()->route('entsorgung.index')
            ->with('success', 'Eintrag wurde gelöscht.');
    }
}
