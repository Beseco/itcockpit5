<?php

namespace App\Modules\Server\Providers;

use App\Modules\Server\Console\Commands\SyncServers;
use App\Services\HookManager;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class ServerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands([SyncServers::class]);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadViewsFrom(__DIR__ . '/../Views', 'server');

        $hookManager = app(HookManager::class);
        $hookManager->registerSidebarItem('server', [
            'label'      => 'Server',
            'route'      => 'server.index',
            'icon'       => 'heroicon-o-server',
            'permission' => 'server.view',
            'module'     => 'server',
        ]);

        $this->scheduleCommands();
    }

    protected function scheduleCommands(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('server:sync')->daily();
        });
    }
}
