<?php

use App\Providers\ModuleServiceProvider;
use App\Services\ModuleRegistry;
use App\Services\ModuleScanner;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    // Create a temporary modules directory for testing
    $this->testModulesPath = app_path('Modules');
    
    // Clean up any existing test modules
    if (File::isDirectory($this->testModulesPath)) {
        File::deleteDirectory($this->testModulesPath);
    }
    
    File::makeDirectory($this->testModulesPath, 0755, true);
});

afterEach(function () {
    // Clean up test modules directory
    if (File::isDirectory($this->testModulesPath)) {
        File::deleteDirectory($this->testModulesPath);
    }
});

test('ModuleServiceProvider binds ModuleScanner as singleton', function () {
    $provider = new ModuleServiceProvider($this->app);
    $provider->register();
    
    $scanner1 = app(ModuleScanner::class);
    $scanner2 = app(ModuleScanner::class);
    
    expect($scanner1)->toBeInstanceOf(ModuleScanner::class)
        ->and($scanner1)->toBe($scanner2); // Same instance
});

test('ModuleServiceProvider binds ModuleRegistry as singleton', function () {
    $provider = new ModuleServiceProvider($this->app);
    $provider->register();
    
    $registry1 = app(ModuleRegistry::class);
    $registry2 = app(ModuleRegistry::class);
    
    expect($registry1)->toBeInstanceOf(ModuleRegistry::class)
        ->and($registry1)->toBe($registry2); // Same instance
});

test('ModuleServiceProvider boot discovers and registers modules', function () {
    // Create a valid test module
    $modulePath = $this->testModulesPath . '/TestModule';
    File::makeDirectory($modulePath . '/Providers', 0755, true);
    
    $moduleJson = [
        'name' => 'TestModule',
        'slug' => 'test-module',
        'version' => '1.0.0'
    ];
    File::put($modulePath . '/module.json', json_encode($moduleJson));
    File::put($modulePath . '/Providers/TestModuleServiceProvider.php', '<?php namespace App\Modules\TestModule\Providers; use Illuminate\Support\ServiceProvider; class TestModuleServiceProvider extends ServiceProvider { public function register() {} public function boot() {} }');
    
    // Register and boot the provider
    $provider = new ModuleServiceProvider($this->app);
    $provider->register();
    $provider->boot();
    
    // Verify module was registered
    $registry = app(ModuleRegistry::class);
    expect($registry->isModuleRegistered('test-module'))->toBeTrue();
});

test('ModuleServiceProvider continues loading modules when one fails', function () {
    // Create a valid module
    $validModulePath = $this->testModulesPath . '/ValidModule';
    File::makeDirectory($validModulePath . '/Providers', 0755, true);
    File::put($validModulePath . '/module.json', json_encode([
        'name' => 'ValidModule',
        'slug' => 'valid-module',
        'version' => '1.0.0'
    ]));
    File::put($validModulePath . '/Providers/ValidModuleServiceProvider.php', '<?php namespace App\Modules\ValidModule\Providers; use Illuminate\Support\ServiceProvider; class ValidModuleServiceProvider extends ServiceProvider { public function register() {} public function boot() {} }');
    
    // Create an invalid module (missing slug)
    $invalidModulePath = $this->testModulesPath . '/InvalidModule';
    File::makeDirectory($invalidModulePath . '/Providers', 0755, true);
    File::put($invalidModulePath . '/module.json', json_encode([
        'name' => 'InvalidModule',
        'version' => '1.0.0'
        // Missing slug
    ]));
    File::put($invalidModulePath . '/Providers/InvalidModuleServiceProvider.php', '<?php use Illuminate\Support\ServiceProvider; class InvalidModuleServiceProvider extends ServiceProvider { public function register() {} public function boot() {} }');
    
    Log::shouldReceive('error')->atLeast()->once();
    Log::shouldReceive('info')->atLeast()->once();
    Log::shouldReceive('warning')->zeroOrMoreTimes();
    
    // Register and boot the provider
    $provider = new ModuleServiceProvider($this->app);
    $provider->register();
    $provider->boot();
    
    // Verify valid module was registered despite invalid module
    $registry = app(ModuleRegistry::class);
    expect($registry->isModuleRegistered('valid-module'))->toBeTrue();
});

test('ModuleServiceProvider registers module routes when Routes/web.php exists', function () {
    $modulePath = $this->testModulesPath . '/TestModule';
    File::makeDirectory($modulePath . '/Providers', 0755, true);
    File::makeDirectory($modulePath . '/Routes', 0755, true);
    
    $moduleJson = [
        'name' => 'TestModule',
        'slug' => 'test-module',
        'version' => '1.0.0'
    ];
    File::put($modulePath . '/module.json', json_encode($moduleJson));
    File::put($modulePath . '/Providers/TestModuleServiceProvider.php', '<?php namespace App\Modules\TestModule\Providers; use Illuminate\Support\ServiceProvider; class TestModuleServiceProvider extends ServiceProvider { public function register() {} public function boot() {} }');
    
    // Create a simple route file
    File::put($modulePath . '/Routes/web.php', "<?php\nuse Illuminate\Support\Facades\Route;\nRoute::get('/', function() { return 'test'; });");
    
    Log::shouldReceive('info')->atLeast()->once();
    
    // Register and boot the provider
    $provider = new ModuleServiceProvider($this->app);
    $provider->register();
    $provider->boot();
    
    // Verify routes were registered (check route exists)
    $routes = collect(app('router')->getRoutes())->map(fn($route) => $route->uri());
    expect($routes->contains('test-module'))->toBeTrue();
});

test('ModuleServiceProvider registers module views when Views directory exists', function () {
    $modulePath = $this->testModulesPath . '/TestModule';
    File::makeDirectory($modulePath . '/Providers', 0755, true);
    File::makeDirectory($modulePath . '/Views', 0755, true);
    
    $moduleJson = [
        'name' => 'TestModule',
        'slug' => 'test-module',
        'version' => '1.0.0'
    ];
    File::put($modulePath . '/module.json', json_encode($moduleJson));
    File::put($modulePath . '/Providers/TestModuleServiceProvider.php', '<?php namespace App\Modules\TestModule\Providers; use Illuminate\Support\ServiceProvider; class TestModuleServiceProvider extends ServiceProvider { public function register() {} public function boot() {} }');
    
    // Create a test view
    File::put($modulePath . '/Views/test.blade.php', '<div>Test View</div>');
    
    Log::shouldReceive('info')->atLeast()->once();
    
    // Register and boot the provider
    $provider = new ModuleServiceProvider($this->app);
    $provider->register();
    $provider->boot();
    
    // Verify views were registered
    expect(view()->exists('test-module::test'))->toBeTrue();
});

test('ModuleServiceProvider handles modules without routes gracefully', function () {
    $modulePath = $this->testModulesPath . '/TestModule';
    File::makeDirectory($modulePath . '/Providers', 0755, true);
    
    $moduleJson = [
        'name' => 'TestModule',
        'slug' => 'test-module',
        'version' => '1.0.0'
    ];
    File::put($modulePath . '/module.json', json_encode($moduleJson));
    File::put($modulePath . '/Providers/TestModuleServiceProvider.php', '<?php namespace App\Modules\TestModule\Providers; use Illuminate\Support\ServiceProvider; class TestModuleServiceProvider extends ServiceProvider { public function register() {} public function boot() {} }');
    
    // Don't create Routes directory
    
    Log::shouldReceive('info')->atLeast()->once();
    
    // Register and boot the provider
    $provider = new ModuleServiceProvider($this->app);
    $provider->register();
    $provider->boot();
    
    // Verify module was still registered
    $registry = app(ModuleRegistry::class);
    expect($registry->isModuleRegistered('test-module'))->toBeTrue();
});

test('ModuleServiceProvider handles modules without views gracefully', function () {
    $modulePath = $this->testModulesPath . '/TestModule';
    File::makeDirectory($modulePath . '/Providers', 0755, true);
    
    $moduleJson = [
        'name' => 'TestModule',
        'slug' => 'test-module',
        'version' => '1.0.0'
    ];
    File::put($modulePath . '/module.json', json_encode($moduleJson));
    File::put($modulePath . '/Providers/TestModuleServiceProvider.php', '<?php namespace App\Modules\TestModule\Providers; use Illuminate\Support\ServiceProvider; class TestModuleServiceProvider extends ServiceProvider { public function register() {} public function boot() {} }');
    
    // Don't create Views directory
    
    Log::shouldReceive('info')->atLeast()->once();
    
    // Register and boot the provider
    $provider = new ModuleServiceProvider($this->app);
    $provider->register();
    $provider->boot();
    
    // Verify module was still registered
    $registry = app(ModuleRegistry::class);
    expect($registry->isModuleRegistered('test-module'))->toBeTrue();
});

test('ModuleServiceProvider logs errors for failed module registration', function () {
    // Create a module with missing slug (will cause registration to fail)
    $modulePath = $this->testModulesPath . '/BadModule';
    File::makeDirectory($modulePath . '/Providers', 0755, true);
    
    $moduleJson = [
        'name' => 'BadModule',
        'version' => '1.0.0'
        // Missing slug
    ];
    File::put($modulePath . '/module.json', json_encode($moduleJson));
    File::put($modulePath . '/Providers/BadModuleServiceProvider.php', '<?php class BadModuleServiceProvider {}');
    
    Log::shouldReceive('error')->atLeast()->once();
    Log::shouldReceive('info')->zeroOrMoreTimes();
    Log::shouldReceive('warning')->zeroOrMoreTimes();
    
    // Register and boot the provider
    $provider = new ModuleServiceProvider($this->app);
    $provider->register();
    $provider->boot();
    
    // Verify module was not registered
    $registry = app(ModuleRegistry::class);
    expect($registry->isModuleRegistered('bad-module'))->toBeFalse();
});
