<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Applikation;
use App\Models\Aufgabe;
use App\Models\Dienstleister;
use App\Models\Gruppe;
use App\Models\Module;
use App\Models\Order;
use App\Models\ReminderMail;
use App\Models\ReminderMailLog;
use App\Models\Stelle;
use App\Services\HookManager;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private HookManager $hookManager) {}

    public function index(): View
    {
        $user = auth()->user();

        $announcements = Announcement::active()
            ->orderBy('created_at', 'desc')
            ->get()
            ->sortBy(fn($a) => match($a->type) {
                'critical'    => 1,
                'maintenance' => 2,
                default       => 3,
            })
            ->values();

        // Kachel-Definitionen für alle bekannten Module
        $allTiles = [
            'orders' => [
                'label'       => 'Bestellverwaltung',
                'description' => 'Bestellungen, Kostenstellen & Sachkonten',
                'route'       => 'orders.index',
                'permission'  => 'orders.view',
                'color'       => 'indigo',
                'stat'        => Order::where('status', '!=', 6)->count() . ' offene Bestellungen',
                'icon'        => 'cart',
            ],
            'dienstleister' => [
                'label'       => 'Dienstleister',
                'description' => 'Lieferanten und Dienstleister verwalten',
                'route'       => 'dienstleister.index',
                'permission'  => 'dienstleister.view',
                'color'       => 'blue',
                'stat'        => Dienstleister::where('status', 'aktiv')->count() . ' aktive Einträge',
                'icon'        => 'building',
            ],
            'reminders' => [
                'label'        => 'Erinnerungsmails',
                'description'  => 'Automatische Erinnerungsmails verwalten',
                'route'        => 'reminders.index',
                'permission'   => 'reminders.view',
                'color'        => 'amber',
                'stat'         => ReminderMail::active()->count() . ' aktive Erinnerungen',
                'icon'         => 'clock',
                'scheduler_ok' => ($lastHb = ReminderMailLog::where('typ', 4)->latest()->value('created_at'))
                    && \Carbon\Carbon::parse($lastHb)->diffInMinutes(now()) < 2,
            ],
            'announcements' => [
                'label'       => 'Ankündigungen',
                'description' => 'Systemmitteilungen und Wartungshinweise',
                'route'       => 'announcements.index',
                'permission'  => 'announcements.view',
                'color'       => 'green',
                'stat'        => Announcement::active()->count() . ' aktive Ankündigungen',
                'icon'        => 'megaphone',
            ],
            'server' => [
                'label'       => 'Server',
                'description' => 'Serververwaltung und LDAP-Synchronisation',
                'route'       => 'server.index',
                'permission'  => 'server.view',
                'color'       => 'indigo',
                'stat'        => \App\Modules\Server\Models\Server::produktiv()->count() . ' produktive Server',
                'icon'        => 'monitor',
            ],
            'applikationen' => [
                'label'       => 'Applikationen',
                'description' => 'IT-Anwendungen und BSI-Schutzbedarf',
                'route'       => 'applikationen.index',
                'permission'  => 'applikationen.view',
                'color'       => 'blue',
                'stat'        => Applikation::count() . ' Anwendungen',
                'icon'        => 'monitor',
            ],
            'aufgaben' => [
                'label'       => 'Rollen & Aufgaben',
                'description' => 'Zuständigkeitsmatrix der IuK',
                'route'       => 'aufgaben.index',
                'permission'  => 'base.aufgaben.view',
                'color'       => 'indigo',
                'stat'        => Aufgabe::count() . ' Aufgaben',
                'icon'        => 'checklist',
            ],
            'stellen' => [
                'label'       => 'Stellenbeschreibungen',
                'description' => 'Stellen, TVöD-Bewertungen und Arbeitsvorgänge',
                'route'       => 'stellen.index',
                'permission'  => 'base.stellen.view',
                'color'       => 'amber',
                'stat'        => Stelle::count() . ' Stellen',
                'icon'        => 'briefcase',
            ],
            'gruppen' => [
                'label'       => 'Gruppen',
                'description' => 'Organisationsgruppen und Rollenvererbung',
                'route'       => 'gruppen.index',
                'permission'  => 'base.gruppen.view',
                'color'       => 'green',
                'stat'        => Gruppe::count() . ' Gruppen',
                'icon'        => 'users',
            ],
        ];

        // Nur aktive Module anzeigen, für die der User Berechtigung hat
        $activeModuleNames = Module::where('is_active', true)->pluck('name')->toArray();

        $tiles = collect($allTiles)->filter(function ($tile, $moduleName) use ($user, $activeModuleNames) {
            if (!in_array($moduleName, $activeModuleNames)) {
                return false;
            }
            if ($user->isSuperAdmin()) {
                return true;
            }
            return $user->hasPermissionTo($tile['permission']);
        });

        $widgets = $this->hookManager->getDashboardWidgets($user);

        return view('dashboard', compact('announcements', 'tiles', 'widgets'));
    }
}
