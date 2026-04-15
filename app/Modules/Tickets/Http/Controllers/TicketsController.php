<?php

namespace App\Modules\Tickets\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tickets\Models\TicketsSettings;
use App\Modules\Tickets\Services\ZammadService;
use Illuminate\Support\Facades\Auth;

class TicketsController extends Controller
{
    /**
     * Debug: Rohe API-Antwort anzeigen (temporär)
     */
    public function debug()
    {
        $settings = TicketsSettings::getSingleton();

        if (!$settings->isConfigured()) {
            return response()->json(['error' => 'Nicht konfiguriert']);
        }

        $service = new ZammadService();
        $service->clearCache(Auth::user()->email);

        return response()->json([
            'email'    => Auth::user()->email,
            'raw'      => $service->debugSearch(Auth::user()->email),
            'parsed'   => $service->getTicketsForUser(Auth::user()->email),
        ]);
    }

    public function index()
    {
        $settings = TicketsSettings::getSingleton();

        if (!$settings->isConfigured()) {
            return view('tickets::index', [
                'tickets'    => collect(),
                'configured' => false,
                'zammadUrl'  => '',
            ]);
        }

        $service = new ZammadService();
        $tickets = $service->getTicketsForUser(Auth::user()->email);

        return view('tickets::index', [
            'tickets'    => $tickets,
            'configured' => true,
            'zammadUrl'  => rtrim($settings->url, '/'),
        ]);
    }
}
