<?php

namespace App\Modules\Tickets\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Tickets\Models\TicketsSettings;
use App\Modules\Tickets\Services\ZammadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketsController extends Controller
{
    public function index(Request $request)
    {
        $settings = TicketsSettings::getSingleton();

        if (!$settings->isConfigured()) {
            return view('tickets::index', [
                'tickets'       => collect(),
                'allTickets'    => collect(),
                'configured'    => false,
                'zammadUrl'     => '',
                'users'         => collect(),
                'filters'       => [],
                'statsByOwner'  => collect(),
                'statsByGroup'  => collect(),
                'statsByPriority' => collect(),
            ]);
        }

        $service = new ZammadService();

        // Filter aus Request
        $filterUser   = $request->input('user', 'me');
        $filterStatus = $request->input('status');
        $filterSearch = $request->input('search');
        $showClosed   = $request->boolean('closed');
        $sortBy       = $request->input('sort', 'updated_at');
        $sortDir      = $request->input('sort_dir', 'desc');
        $groupBy      = $request->input('group_by', '');

        // Email fuer Suche bestimmen
        $email = null;
        $unassigned = false;
        if ($filterUser === 'me') {
            $email = Auth::user()->email;
        } elseif ($filterUser === 'unassigned') {
            $unassigned = true;
        } elseif ($filterUser !== 'all') {
            $selectedUser = User::find($filterUser);
            $email = $selectedUser?->email;
        }

        $tickets = $service->searchTickets(
            email: $email,
            unassigned: $unassigned,
            includeClosed: $showClosed,
            state: $filterStatus ?: null,
            search: $filterSearch,
        );

        // Sortierung anwenden
        $priorityOrder = ['1 low' => 1, '1 niedrig' => 1, '2 normal' => 2, '3 high' => 3, '3 hoch' => 3];
        $tickets = match($sortBy) {
            'priority' => $sortDir === 'asc'
                ? $tickets->sortBy(fn($t) => $priorityOrder[strtolower($t['priority'] ?? '')] ?? 5)
                : $tickets->sortByDesc(fn($t) => $priorityOrder[strtolower($t['priority'] ?? '')] ?? 0),
            'state'      => $sortDir === 'asc' ? $tickets->sortBy('state')      : $tickets->sortByDesc('state'),
            'created_at' => $sortDir === 'asc' ? $tickets->sortBy('created_at') : $tickets->sortByDesc('created_at'),
            default      => $sortDir === 'asc' ? $tickets->sortBy('updated_at') : $tickets->sortByDesc('updated_at'),
        };
        $tickets = $tickets->values();

        // Alle offenen Tickets fuer Statistik (ungefiltert nach User)
        $allTickets = $service->searchTickets(email: null, includeClosed: false);

        // User-Liste fuer Dropdown
        $users = User::orderBy('name')->get(['id', 'name', 'email']);

        // Statistiken
        $statsByOwner    = $service->getStatsByOwner($allTickets);
        $statsByGroup    = $service->getStatsByGroup($allTickets);
        $statsByPriority = $service->getStatsByPriority($allTickets);

        return view('tickets::index', [
            'tickets'         => $tickets,
            'allTickets'      => $allTickets,
            'configured'      => true,
            'zammadUrl'       => rtrim($settings->url, '/'),
            'users'           => $users,
            'filters'         => [
                'user'     => $filterUser,
                'status'   => $filterStatus,
                'search'   => $filterSearch,
                'closed'   => $showClosed,
                'sort'     => $sortBy,
                'sort_dir' => $sortDir,
                'group_by' => $groupBy,
            ],
            'statsByOwner'    => $statsByOwner,
            'statsByGroup'    => $statsByGroup,
            'statsByPriority' => $statsByPriority,
        ]);
    }

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
}
