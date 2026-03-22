<?php

namespace App\Modules\Network\Providers;

use App\Modules\Network\Console\Commands\NetworkScanCommand;
use App\Modules\Network\Services\IpGeneratorService;
use App\Modules\Network\Services\ScannerService;
use App\Services\HookManager;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class NetworkServiceProvider extends ServiceProvider
{
    /**
     * Register services to the container
     */
    public function register(): void
    {
        // Register services
        $this->app->singleton(IpGeneratorService::class);
        $this->app->singleton(ScannerService::class);

        // Register console commands
        $this->commands([
            NetworkScanCommand::class,
        ]);
    }

    /**
     * Bootstrap services - register hooks with the system
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../Views', 'network');

        // Note: Routes are loaded by ModuleServiceProvider
        // to avoid double-registration

        $hookManager = app(HookManager::class);

        // Register sidebar navigation item
        $hookManager->registerSidebarItem('network', [
            'label' => 'Network',
            'route' => 'network.index',
            'icon' => 'heroicon-o-signal',
            'permission' => 'network.view'
        ]);

        // Register dashboard widget
        $hookManager->registerDashboardWidget('network', 'network::components.dashboard-widget');

        // Register permissions
        $hookManager->registerPermission('network', 'view', 'View network module');
        $hookManager->registerPermission('network', 'edit', 'Edit network configuration');

        // Schedule network scan command
        $this->scheduleCommands();
    }

    /**
     * Schedule the network scan command to run every minute
     */
    protected function scheduleCommands(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('network:scan')->everyMinute();
        });
    }
}
