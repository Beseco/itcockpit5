<?php

namespace App\Modules\Entsorgung\Http\Controllers;

use App\Modules\Entsorgung\Models\EntsorgungGrund;
use App\Modules\Entsorgung\Models\EntsorgungTyp;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class EntsorgungListenController extends Controller
{
    private function authorizeManage(): void
    {
        if (!Auth::user()->hasModulePermission('entsorgung', 'edit')) {
            abort(403);
        }
    }

    // ─── Gerätetypen ────────────────────────────────────────────────

    public function typenIndex()
    {
        $this->authorizeManage();
        $typen = EntsorgungTyp::orderBy('name')->get();

        return view('entsorgung::listen.typen', compact('typen'));
    }

    public function typenStore(Request $request)
    {
        $this->authorizeManage();
        $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:entsorgung_typen,name'],
        ]);

        EntsorgungTyp::create(['name' => trim($request->name)]);

        return redirect()->route('entsorgung.listen.typen')
            ->with('success', 'Gerätetyp wurde hinzugefügt.');
    }

    public function typenDestroy(EntsorgungTyp $typ)
    {
        $this->authorizeManage();
        $typ->delete();

        return redirect()->route('entsorgung.listen.typen')
            ->with('success', 'Gerätetyp wurde entfernt.');
    }

    // ─── Entsorgungsgründe ──────────────────────────────────────────

    public function gruendeIndex()
    {
        $this->authorizeManage();
        $gruende = EntsorgungGrund::orderBy('name')->get();

        return view('entsorgung::listen.gruende', compact('gruende'));
    }

    public function gruendeStore(Request $request)
    {
        $this->authorizeManage();
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:entsorgung_gruende,name'],
        ]);

        EntsorgungGrund::create(['name' => trim($request->name)]);

        return redirect()->route('entsorgung.listen.gruende')
            ->with('success', 'Entsorgungsgrund wurde hinzugefügt.');
    }

    public function gruendeDestroy(EntsorgungGrund $grund)
    {
        $this->authorizeManage();
        $grund->delete();

        return redirect()->route('entsorgung.listen.gruende')
            ->with('success', 'Entsorgungsgrund wurde entfernt.');
    }
}
