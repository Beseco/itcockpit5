<?php

namespace App\Modules\Baramundi\Providers;

use App\Modules\Baramundi\Console\Commands\BaraDiagnoseCommand;
use App\Modules\Baramundi\Console\Commands\BaraScanCommand;
use App\Modules\Baramundi\Models\BaraSettings;
use App\Modules\Baramundi\Services\DownloaderRegistry;
use App\Modules\Baramundi\Services\Downloaders\BatchDownloader;
use App\Modules\Baramundi\Services\Downloaders\HttpDownloader;
use App\Modules\Baramundi\Services\Downloaders\PowerShellDownloader;
use App\Services\HookManager;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class BaramundiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DownloaderRegistry::class, function () {
            $registry = new DownloaderRegistry();
            $registry->register(new HttpDownloader());
            $registry->register(new PowerShellDownloader());
            $registry->register(new BatchDownloader());
            return $registry;
        });

        $this->commands([BaraScanCommand::class, BaraDiagnoseCommand::class]);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadViewsFrom(__DIR__ . '/../Views', 'baramundi');

        $hookManager = app(HookManager::class);

        $hookManager->registerSidebarItem('baramundi', [
            'label'      => 'Baramundi',
            'route'      => 'baramundi.index',
            'icon'       => 'heroicon-o-arrow-down-tray',
            'permission' => 'baramundi.view',
            'module'     => 'baramundi',
        ]);

        $this->scheduleCommands();
    }

    protected function scheduleCommands(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            try {
                $interval = BaraSettings::getSingleton()->scan_interval_minutes ?? 15;
                $interval = (int) max(1, min(1440, $interval));
            } catch (\Throwable) {
                $interval = 15;
            }

            // cron("*/N * * * *") läuft korrekt für Werte 1–60; bei >60 Min empfiehlt sich stündlicher Betrieb
            $cronInterval = min($interval, 60);
            $schedule->command('bara:scan')->cron("*/{$cronInterval} * * * *");
        });
    }
}
