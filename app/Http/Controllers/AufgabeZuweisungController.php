<?php

namespace App\Http\Controllers;

use App\Models\AufgabeZuweisung;
use App\Models\Gruppe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AufgabeZuweisungController extends Controller
{
    public function update(Request $request, AufgabeZuweisung $zuweisung)
    {
        $user = Auth::user();

        $canEdit = $user->hasModulePermission('base', 'aufgaben.edit');

        $isVorgesetzter = $zuweisung->gruppe_id !== null
            && Gruppe::where('id', $zuweisung->gruppe_id)
                ->where('vorgesetzter_user_id', $user->id)
                ->exists();

        if (!$canEdit && !$isVorgesetzter) {
            abort(403, 'Keine Berechtigung, diese Zuweisung zu bearbeiten.');
        }

        $validated = $request->validate([
            'admin_user_id'          => 'nullable|exists:users,id',
            'stellvertreter_user_id' => 'nullable|exists:users,id',
        ]);

        $zuweisung->update([
            'admin_user_id'          => $validated['admin_user_id'] ?? null,
            'stellvertreter_user_id' => $validated['stellvertreter_user_id'] ?? null,
        ]);

        return back()->with('success', 'Zuweisung wurde aktualisiert.');
    }
}
