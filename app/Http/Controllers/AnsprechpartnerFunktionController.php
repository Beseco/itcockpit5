<?php

namespace App\Http\Controllers;

use App\Models\AnsprechpartnerFunktion;
use Illuminate\Http\Request;

class AnsprechpartnerFunktionController extends Controller
{
    public function store(Request $request)
    {
        $this->authorize('dienstleister.edit');

        $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:dienstleister_ansprechpartner_funktionen,name'],
        ]);

        $funktion = AnsprechpartnerFunktion::create([
            'name'       => trim($request->name),
            'sort_order' => (AnsprechpartnerFunktion::max('sort_order') ?? 0) + 10,
        ]);

        return response()->json($funktion);
    }

    public function destroy(AnsprechpartnerFunktion $funktion)
    {
        $this->authorize('dienstleister.edit');

        $funktion->delete();

        return response()->json(['ok' => true]);
    }
}
