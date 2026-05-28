<?php

namespace App\Modules\Feedback\Providers;

use App\Services\HookManager;
use Illuminate\Support\ServiceProvider;

class FeedbackServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadViewsFrom(__DIR__ . '/../Views', 'feedback');

        $hookManager = app(HookManager::class);

        $hookManager->registerSidebarItem('feedback', [
            'label'      => 'Feedback',
            'route'      => 'feedback.admin.dashboard',
            'icon'       => 'heroicon-o-chat-bubble-oval-left',
            'permission' => 'feedback.view',
            'module'     => 'feedback',
        ]);

        $hookManager->registerPermission('feedback', 'view',   'Feedback-Statistik einsehen');
        $hookManager->registerPermission('feedback', 'edit',   'Alle Bewertungen und Kommentare einsehen');
        $hookManager->registerPermission('feedback', 'delete', 'Einzelne Bewertungen löschen');
    }
}
