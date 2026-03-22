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

        return view('stellenplan::index', compact('gruppen'));
    }
}
