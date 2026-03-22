<?php

namespace App\Modules\Stellenplan\Providers;

use App\Services\HookManager;
use Illuminate\Support\ServiceProvider;

class StellenplanServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $hookManager = app(HookManager::class);

        $hookManager->registerSidebarItem('Stellenplan', [
            'label'      => 'Stellenplan',
            'route'      => 'stellenplan.index',
            'icon'       => 'heroicon-o-clipboard-document-list',
            'permission' => 'module.stellenplan.view',
            'module'     => 'stellenplan',
        ]);

        $hookManager->registerDashboardWidget('stellenplan', 'stellenplan::widget');
    }
}
