<?php

namespace App\Modules\Backup\Providers;

use App\Modules\Backup\Console\Commands\BackupCreateCommand;
use App\Modules\Backup\Models\BackupSettings;
use App\Services\HookManager;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class BackupServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands([BackupCreateCommand::class]);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadViewsFrom(__DIR__ . '/../Views', 'backup');

        $hookManager = app(HookManager::class);
        $hookManager->registerSidebarItem('backup', [
            'label'      => 'Backup',
            'route'      => 'backup.index',
            'icon'       => 'heroicon-o-archive-box',
            'permission' => 'backup.view',
            'module'     => 'backup',
        ]);

        $this->scheduleCommands();
    }

    protected function scheduleCommands(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            // Backup-Zeit aus DB lesen (Standardwert falls Tabelle noch nicht existiert)
            try {
                $time = BackupSettings::getSingleton()->schedule_time ?? '05:00';
            } catch (\Throwable) {
                $time = '05:00';
            }

            $schedule->command('backup:create')->dailyAt($time);
        });
    }
}
