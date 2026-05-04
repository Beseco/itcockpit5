<?php

namespace App\Modules\HH\Providers;

use App\Services\HookManager;
use Illuminate\Support\ServiceProvider;

class HHServiceProvider extends ServiceProvider
{
    /**
     * Register services to the container.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../Views', 'hh');

        // Register sidebar navigation item
        $hookManager = app(HookManager::class);
        $hookManager->registerSidebarItem('hh', [
            'label' => 'Haushaltsplanung',
            'route' => 'hh.dashboard.index',
            'route_active_pattern' => 'hh.*',
            'icon' => 'heroicon-o-banknotes',
            'permission' => 'hh.view',
            'module' => 'hh',
        ]);
    }

}
