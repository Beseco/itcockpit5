<?php

namespace App\Modules\Onboarding\Providers;

use App\Services\HookManager;
use Illuminate\Support\ServiceProvider;

class OnboardingServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadViewsFrom(__DIR__ . '/../Views', 'onboarding');

        $hookManager = app(HookManager::class);

        $hookManager->registerSidebarItem('onboarding', [
            'label'      => 'Onboarding',
            'route'      => 'onboarding.index',
            'icon'       => 'heroicon-o-user-plus',
            'permission' => 'onboarding.view',
            'module'     => 'onboarding',
        ]);
    }
}
