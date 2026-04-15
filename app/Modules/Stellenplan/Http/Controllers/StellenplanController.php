<?php

namespace App\Modules\Stellenplan\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Gruppe;

class StellenplanController extends Controller
{
    public function index()
    {
        $this->authorize('module.stellenplan.view');

        $gruppen = Gruppe::with([
            'stellen' => fn ($q) => $q->with(['stellenbeschreibung', 'stelleninhaber', 'gruppe'])
                                      ->orderBy('stellennummer'),
        ])->orderBy('name')->get();

        $rootGruppen = Gruppe::roots()->with([
            'childrenRecursive',
            'stellen.stellenbeschreibung',
            'stellen.stelleninhaber',
            'vorgesetzter',
        ])->get();

        $canSeeSensitive = auth()->user()->can('module.stellenplan.view_sensitive');

        return view('stellenplan::index', compact('gruppen', 'rootGruppen', 'canSeeSensitive'));
    }
}
