<?php

namespace App\Providers;

use App\Services\HookManager;
use Illuminate\Support\ServiceProvider;

class UserManagementServiceProvider extends ServiceProvider
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
        $hookManager->registerSidebarItem('users', [
            'label' => 'Benutzerverwaltung',
            'route' => 'users.index',
            'icon' => 'heroicon-o-users',
            'permission' => 'base.users.view',
        ]);

        // Register permissions
        $hookManager->registerPermission('users', 'view', 'Benutzerverwaltung anzeigen');
        $hookManager->registerPermission('users', 'create', 'Benutzer anlegen');
        $hookManager->registerPermission('users', 'edit', 'Benutzer bearbeiten');
        $hookManager->registerPermission('users', 'delete', 'Benutzer löschen');
    }
}
