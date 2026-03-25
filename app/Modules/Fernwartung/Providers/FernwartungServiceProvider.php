<?php

namespace App\Modules\Fernwartung\Providers;

use App\Services\HookManager;
use Illuminate\Support\ServiceProvider;

class FernwartungServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadViewsFrom(__DIR__ . '/../Views', 'fernwartung');

        $hookManager = app(HookManager::class);
        $hookManager->registerSidebarItem('fernwartung', [
            'label'      => 'Fernwartung',
            'route'      => 'fernwartung.index',
            'icon'       => 'heroicon-o-computer-desktop',
            'permission' => 'fernwartung.view',
            'module'     => 'fernwartung',
        ]);
    }
}
