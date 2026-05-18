<?php

namespace App\Modules\Schulen\Providers;

use App\Services\HookManager;
use Illuminate\Support\ServiceProvider;

class SchulenServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadViewsFrom(__DIR__ . '/../Views', 'schulen');

        $hookManager = app(HookManager::class);
        $hookManager->registerSidebarItem('schulen', [
            'label'      => 'Schulen',
            'route'      => 'schulen.matrix',
            'icon'       => 'heroicon-o-academic-cap',
            'permission' => 'schulen.view',
            'module'     => 'schulen',
        ]);
    }
}
