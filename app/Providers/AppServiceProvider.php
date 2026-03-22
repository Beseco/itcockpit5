<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\AuditLogger::class, function ($app) {
            return new \App\Services\AuditLogger();
        });

        $this->app->singleton(\App\Services\HookManager::class, function ($app) {
            return new \App\Services\HookManager();
        });

        // Temporary: Register Network module command directly
        // TODO: Fix module service provider loading
        $this->commands([
            \App\Modules\Network\Console\Commands\NetworkScanCommand::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // SuperAdminGate: Superadministrator hat alle Rechte
        Gate::before(function (User $user, string $ability) {
            if ($user->hasRole('Superadministrator')) {
                return true;
            }
        });

        // Share module navigation items with sidebar view
        view()->composer('layouts.sidebar', function ($view) {
            if (auth()->check()) {
                $hookManager = app(\App\Services\HookManager::class);
                $moduleNavItems = $hookManager->getSidebarItems(auth()->user());
                $view->with('moduleNavItems', $moduleNavItems);
            }
        });
    }
}
