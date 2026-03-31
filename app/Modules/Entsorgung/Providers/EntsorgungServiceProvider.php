<?php

namespace App\Modules\Entsorgung\Providers;

use App\Services\HookManager;
use Illuminate\Support\ServiceProvider;

class EntsorgungServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadViewsFrom(__DIR__ . '/../Views', 'entsorgung');

        $hookManager = app(HookManager::class);

        $hookManager->registerSidebarItem('entsorgung', [
            'label'      => 'Entsorgung',
            'route'      => 'entsorgung.index',
            'icon'       => 'heroicon-o-trash',
            'permission' => 'entsorgung.view',
            'module'     => 'entsorgung',
        ]);

        $hookManager->registerPermission('entsorgung', 'view',   'Entsorgungsliste einsehen');
        $hookManager->registerPermission('entsorgung', 'edit',   'Entsorgungseinträge anlegen');
        $hookManager->registerPermission('entsorgung', 'delete', 'Entsorgungseinträge löschen');
    }
}
