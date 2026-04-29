<?php

namespace App\Modules\OrgChart\Providers;

use App\Services\HookManager;
use Illuminate\Support\ServiceProvider;

class OrgChartServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        $hookManager = app(HookManager::class);

        $hookManager->registerSidebarItem('orgchart', [
            'label'      => 'Organigramm',
            'route'      => 'orgchart.index',
            'icon'       => 'heroicon-o-building-office-2',
            'permission' => 'orgchart.view',
            'module'     => 'orgchart',
        ]);

        $hookManager->registerDashboardWidget('orgchart', 'orgchart::widget');
    }
}
