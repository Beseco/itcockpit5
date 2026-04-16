<?php

namespace App\Modules\SslCerts\Providers;

use App\Services\HookManager;
use Illuminate\Support\ServiceProvider;

class SslCertsServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadViewsFrom(__DIR__ . '/../Views', 'sslcerts');

        $hookManager = app(HookManager::class);

        $hookManager->registerSidebarItem('sslcerts', [
            'label'      => 'SSL-Zertifikate',
            'route'      => 'sslcerts.index',
            'icon'       => 'heroicon-o-shield-check',
            'permission' => 'module.sslcerts.view',
            'module'     => 'sslcerts',
        ]);
    }
}
