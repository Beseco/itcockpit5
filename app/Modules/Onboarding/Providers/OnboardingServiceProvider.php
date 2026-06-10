<?php

namespace App\Modules\Onboarding\Providers;

use App\Models\Abteilung;
use App\Modules\Onboarding\Observers\AbteilungObserver;
use Illuminate\Support\ServiceProvider;

class OnboardingServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadViewsFrom(__DIR__ . '/../Views', 'onboarding');

        // Jede Organisationseinheit erhält automatisch genau eine Vorlage.
        Abteilung::observe(AbteilungObserver::class);
    }
}
