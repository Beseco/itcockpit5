<?php

namespace App\Modules\AdUsers\Providers;

use App\Modules\AdUsers\Console\Commands\ImportLegacyOffboarding;
use App\Modules\AdUsers\Console\Commands\SyncAdUsers;
use App\Services\HookManager;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class AdUsersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands([SyncAdUsers::class, ImportLegacyOffboarding::class]);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadViewsFrom(__DIR__ . '/../Views', 'adusers');

        $hookManager = app(HookManager::class);
        $hookManager->registerSidebarItem('adusers', [
            'label'      => 'AD-Benutzer',
            'route'      => 'adusers.index',
            'icon'       => 'heroicon-o-users',
            'permission' => 'adusers.view',
            'module'     => 'adusers',
        ]);

        $this->scheduleCommands();
    }

    protected function scheduleCommands(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('adusers:sync')->hourly();
        });
    }
}
