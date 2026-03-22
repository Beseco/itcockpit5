<?php

namespace App\Modules\Example\Providers;

use App\Services\HookManager;
use Illuminate\Support\ServiceProvider;

class ExampleServiceProvider extends ServiceProvider
{
    /**
     * Register services to the container
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services - register hooks with the system
     */
    public function boot(): void
    {
        $hookManager = app(HookManager::class);

        // Register sidebar navigation item
        $hookManager->registerSidebarItem('example', [
            'label' => 'Example Module',
            'route' => 'example.index',
            'icon' => 'heroicon-o-cube',
            'permission' => 'example.view',
            'module' => 'example'
        ]);

        // Register dashboard widget
        $hookManager->registerDashboardWidget('example', 'example::widget');

        // Register permissions
        $hookManager->registerPermission('example', 'view', 'View example module');
        $hookManager->registerPermission('example', 'edit', 'Edit example module');
    }
}
