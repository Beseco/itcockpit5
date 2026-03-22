<?php

namespace App\Modules\HH\Providers;

use App\Services\HookManager;
use Illuminate\Support\Facades\Route;
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

        // Load API routes (web routes are loaded by ModuleServiceProvider)
        $this->loadApiRoutes();

        // Register sidebar navigation item
        $hookManager = app(HookManager::class);
        $hookManager->registerSidebarItem('HH', [
            'label' => 'Haushaltsplanung',
            'route' => 'hh.dashboard.index',
            'icon' => 'heroicon-o-banknotes',
            'permission' => 'hh.view',
            'module' => 'hh',
        ]);
    }

    /**
     * Register the HH module API routes under /api/hh with Sanctum auth.
     */
    protected function loadApiRoutes(): void
    {
        $apiRoutesPath = __DIR__ . '/../Routes/api.php';

        if (file_exists($apiRoutesPath)) {
            Route::middleware('api')
                ->prefix('api')
                ->group($apiRoutesPath);
        }
    }
}
