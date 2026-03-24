<?php

namespace App\Modules\Calendar\Providers;

use App\Services\HookManager;
use Illuminate\Support\ServiceProvider;

class CalendarServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');


        $hookManager = app(HookManager::class);

        $hookManager->registerSidebarItem('Calendar', [
            'label'      => 'Kalender',
            'route'      => 'calendar.index',
            'icon'       => 'heroicon-o-calendar-days',
            'permission' => 'module.calendar.view',
            'module'     => 'calendar',
        ]);
    }
}
