<?php

namespace App\Modules\Vertragsmanagement\Providers;

use App\Modules\Vertragsmanagement\Console\Commands\SendContractRemindersCommand;
use App\Services\HookManager;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class VertragsmanagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands([SendContractRemindersCommand::class]);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadViewsFrom(__DIR__ . '/../Views', 'vertragsmanagement');

        $hookManager = app(HookManager::class);

        $hookManager->registerSidebarItem('vertragsmanagement', [
            'label'      => 'Vertragsmanagement',
            'route'      => 'vertragsmanagement.index',
            'icon'       => 'heroicon-o-document-text',
            'permission' => 'vertragsmanagement.view',
            'module'     => 'vertragsmanagement',
        ]);

        $this->scheduleCommands();
    }

    protected function scheduleCommands(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('contracts:send-reminders')->dailyAt('08:00');
        });
    }
}
