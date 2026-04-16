<?php

namespace App\Modules\Tickets\Providers;

use App\Services\HookManager;
use Illuminate\Support\ServiceProvider;

class TicketsServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        $hookManager = app(HookManager::class);

        $hookManager->registerSidebarItem('tickets', [
            'label'      => 'Tickets',
            'route'      => 'tickets.index',
            'icon'       => 'heroicon-o-ticket',
            'permission' => 'tickets.view',
            'module'     => 'tickets',
        ]);

        $hookManager->registerDashboardWidget('tickets', 'tickets::widget');
    }
}
