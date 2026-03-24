<?php

namespace App\Modules\Calendar\Http\Controllers;

use App\Models\User;
use App\Modules\Calendar\Models\CalendarEvent;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CalendarController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // ICS-Token on-demand generieren
        if (!$user->ics_token) {
            $user->ics_token = Str::random(64);
            $user->save();
        }

        $allUsers   = User::orderBy('name')->get(['id', 'name', 'email']);
        $eventTypen = CalendarEvent::TYPEN;
        $erinnerungOptionen = CalendarEvent::ERINNERUNG_OPTIONEN;
        $icsToken   = $user->ics_token;

        return view('calendar::index', compact('allUsers', 'eventTypen', 'erinnerungOptionen', 'icsToken'));
    }

    public function generateIcsToken(Request $request)
    {
        $user = Auth::user();
        $user->update(['ics_token' => Str::random(64)]);

        return response()->json(['token' => $user->ics_token]);
    }
}
