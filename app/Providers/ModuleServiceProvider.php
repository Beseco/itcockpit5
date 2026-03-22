<?php

namespace App\Providers;

use App\Services\ModuleRegistry;
use App\Services\ModuleScanner;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register services to the container
     */
    public function register(): void
    {
        // Bind ModuleScanner as singleton
        $this->app->singleton(ModuleScanner::class, function ($app) {
            return new ModuleScanner();
        });

        // Bind ModuleRegistry as singleton
        $this->app->singleton(ModuleRegistry::class, function ($app) {
            return new ModuleRegistry();
        });
    }

    /**
     * Bootstrap services - discover and register modules
     */
    public function boot(): void
    {
        $scanner = $this->app->make(ModuleScanner::class);
        $registry = $this->app->make(ModuleRegistry::class);

        // Scan for modules
        $modules = $scanner->scan();

        // Register each discovered module
        foreach ($modules as $moduleMetadata) {
            try {
                $this->registerModule($moduleMetadata, $registry);
            } catch (\Exception $e) {
                Log::error('Failed to register module', [
                    'module' => $moduleMetadata['name'] ?? 'Unknown',
                    'error' => $e->getMessage()
                ]);
                // Continue loading other modules
            }
        }
    }

    /**
     * Register a single module with Laravel
     *
     * @param array $moduleMetadata Module metadata from module.json
     * @param ModuleRegistry $registry Module registry instance
     * @return void
     */
    private function registerModule(array $moduleMetadata, ModuleRegistry $registry): void
    {
        $moduleName = $moduleMetadata['name'] ?? 'Unknown';
        $slug = $moduleMetadata['slug'] ?? null;

        if (!$slug) {
            Log::error('Module missing slug, cannot register', ['module' => $moduleName]);
            return;
        }

        // Register module in registry
        $registry->register($moduleMetadata);

        // Get module path
        $modulePath = app_path("Modules/{$moduleName}");

        // Register module routes
        $this->registerModuleRoutes($modulePath, $slug);

        // Register module views
        $this->registerModuleViews($modulePath, $slug);

        // Load module ServiceProvider
        $this->loadModuleServiceProvider($moduleName, $modulePath);
    }

    /**
     * Register module routes from Routes/web.php
     *
     * @param string $modulePath Path to module directory
     * @param string $slug Module slug for route prefix
     * @return void
     */
    private function registerModuleRoutes(string $modulePath, string $slug): void
    {
        $routesPath = $modulePath . '/Routes/web.php';

        if (File::exists($routesPath)) {
            Route::middleware('web')
                ->prefix($slug)
                ->name("{$slug}.")
                ->group($routesPath);

            Log::info('Module routes registered', ['slug' => $slug]);
        }
    }

    /**
     * Register module views from Views/ directory
     *
     * @param string $modulePath Path to module directory
     * @param string $slug Module slug for view namespace
     * @return void
     */
    private function registerModuleViews(string $modulePath, string $slug): void
    {
        $viewsPath = $modulePath . '/Views';

        if (File::isDirectory($viewsPath)) {
            $this->loadViewsFrom($viewsPath, $slug);
            Log::info('Module views registered', ['slug' => $slug]);
        }
    }

    /**
     * Load and register module ServiceProvider
     *
     * @param string $moduleName Module name
     * @param string $modulePath Path to module directory
     * @return void
     */
    private function loadModuleServiceProvider(string $moduleName, string $modulePath): void
    {
        $serviceProviderClass = "App\\Modules\\{$moduleName}\\Providers\\{$moduleName}ServiceProvider";

        if (class_exists($serviceProviderClass)) {
            $this->app->register($serviceProviderClass);
            Log::info('Module ServiceProvider loaded', ['module' => $moduleName]);
        }
    }
}
