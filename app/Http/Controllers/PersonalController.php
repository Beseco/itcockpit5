<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AufgabeZuweisung;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PersonalController extends Controller
{
    public function index()
    {
        $user = Auth::user()->load([
            'gruppen.roles',
            'gruppen.users',
            'stellen.stellenbeschreibung.arbeitsvorgaenge',
            'stellen.gruppe',
        ]);

        // Eigene Bestellungen (via buyer_username, kein FK in it_orders)
        $bestellungen = Order::where('buyer_username', $user->name)
            ->with(['vendor', 'costCenter'])
            ->orderByDesc('order_date')
            ->limit(10)
            ->get();

        // Aktive Ankündigungen (nicht user-spezifisch)
        $ankuendigungen = Announcement::active()
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // Aufgaben wo User als Admin oder Stellvertreter eingetragen ist
        $aufgabenZuweisungen = AufgabeZuweisung::where('admin_user_id', $user->id)
            ->orWhere('stellvertreter_user_id', $user->id)
            ->with(['aufgabe', 'gruppe'])
            ->get();

        // Stelle des Users (erste, falls vorhanden)
        $stelle = $user->stellen()->with(['gruppe', 'stellenbeschreibung.arbeitsvorgaenge'])->first();

        return view('personal.index', compact(
            'user',
            'bestellungen',
            'ankuendigungen',
            'aufgabenZuweisungen',
            'stelle'
        ));
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
        ]);

        $user = Auth::user();

        // Delete old avatar
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar_path' => $path]);

        return back()->with('success', 'Profilbild wurde aktualisiert.');
    }
}
