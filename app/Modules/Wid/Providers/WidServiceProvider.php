<?php

namespace App\Modules\Wid\Providers;

use App\Services\HookManager;
use Illuminate\Support\ServiceProvider;

class WidServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadViewsFrom(__DIR__ . '/../Views', 'wid');

        $hookManager = app(HookManager::class);

        $hookManager->registerSidebarItem('wid', [
            'label'      => 'Sicherheitswarnungen',
            'route'      => 'wid.index',
            'icon'       => 'heroicon-o-shield-exclamation',
            'permission' => 'wid.view',
            'module'     => 'wid',
        ]);

        $hookManager->registerDashboardWidget('wid', 'wid::widget');
    }
}
