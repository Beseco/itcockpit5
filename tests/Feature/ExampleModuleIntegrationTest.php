<?php

use App\Models\User;
use App\Providers\ModuleServiceProvider;
use App\Services\HookManager;
use App\Services\ModuleRegistry;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    // Ensure the Example module exists
    $this->exampleModulePath = app_path('Modules/Example');
    
    // Register and boot the ModuleServiceProvider to discover modules
    $provider = new ModuleServiceProvider($this->app);
    $provider->register();
    $provider->boot();
});

test('Example module is discovered and registered', function () {
    $registry = app(ModuleRegistry::class);
    
    expect($registry->isModuleRegistered('example'))->toBeTrue();
    
    $module = $registry->getModuleBySlug('example');
    expect($module)->not->toBeNull()
        ->and($module['name'])->toBe('Example')
        ->and($module['slug'])->toBe('example')
        ->and($module['version'])->toBe('1.0.0');
});

test('Example module registers sidebar item', function () {
    $hookManager = app(HookManager::class);
    
    // Create a super admin user to see all sidebar items
    $superAdmin = User::factory()->create(['role' => 'super-admin']);
    
    $sidebarItems = $hookManager->getSidebarItems($superAdmin);
    
    $exampleItem = $sidebarItems->firstWhere('module', 'example');
    
    expect($exampleItem)->not->toBeNull()
        ->and($exampleItem['label'])->toBe('Example Module')
        ->and($exampleItem['route'])->toBe('example.index')
        ->and($exampleItem['icon'])->toBe('heroicon-o-cube')
        ->and($exampleItem['permission'])->toBe('module.example.view');
});

test('Example module sidebar item appears for users with permission', function () {
    $hookManager = app(HookManager::class);
    
    // Create permission
    Permission::create(['name' => 'module.example.view']);
    
    // Create a standard user with example module view permission
    $user = User::factory()->create(['role' => 'user']);
    $user->givePermissionTo('module.example.view');
    
    $sidebarItems = $hookManager->getSidebarItems($user);
    
    $exampleItem = $sidebarItems->firstWhere('module', 'example');
    
    expect($exampleItem)->not->toBeNull()
        ->and($exampleItem['label'])->toBe('Example Module');
});

test('Example module sidebar item does not appear for users without permission', function () {
    $hookManager = app(HookManager::class);
    
    // Create permission first so it exists
    Permission::create(['name' => 'module.example.view']);
    
    // Create a standard user without example module permission (don't assign it)
    $user = User::factory()->create(['role' => 'user']);
    
    $sidebarItems = $hookManager->getSidebarItems($user);
    
    $exampleItem = $sidebarItems->firstWhere('module', 'example');
    
    expect($exampleItem)->toBeNull();
});

test('Example module registers dashboard widget', function () {
    $hookManager = app(HookManager::class);
    
    // Create a super admin user to see all widgets
    $superAdmin = User::factory()->create(['role' => 'super-admin']);
    
    $widgets = $hookManager->getDashboardWidgets($superAdmin);
    
    $exampleWidget = $widgets->firstWhere('module', 'example');
    
    expect($exampleWidget)->not->toBeNull()
        ->and($exampleWidget['viewPath'])->toBe('example::widget');
});

test('Example module dashboard widget appears for users with permission', function () {
    $hookManager = app(HookManager::class);
    
    // Create permission
    Permission::create(['name' => 'module.example.view']);
    
    // Create a standard user with example module view permission
    $user = User::factory()->create(['role' => 'user']);
    $user->givePermissionTo('module.example.view');
    
    $widgets = $hookManager->getDashboardWidgets($user);
    
    $exampleWidget = $widgets->firstWhere('module', 'example');
    
    expect($exampleWidget)->not->toBeNull()
        ->and($exampleWidget['viewPath'])->toBe('example::widget');
});

test('Example module dashboard widget does not appear for users without permission', function () {
    $hookManager = app(HookManager::class);
    
    // Create a standard user without example module permission
    $user = User::factory()->create(['role' => 'user']);
    
    $widgets = $hookManager->getDashboardWidgets($user);
    
    $exampleWidget = $widgets->firstWhere('module', 'example');
    
    expect($exampleWidget)->toBeNull();
});

test('Example module registers permissions', function () {
    $hookManager = app(HookManager::class);
    
    $permissions = $hookManager->getRegisteredPermissions();
    
    $viewPermission = $permissions->firstWhere('name', 'module.example.view');
    $editPermission = $permissions->firstWhere('name', 'module.example.edit');
    
    expect($viewPermission)->not->toBeNull()
        ->and($viewPermission['module'])->toBe('example')
        ->and($viewPermission['permission'])->toBe('view')
        ->and($viewPermission['description'])->toBe('View example module');
    
    expect($editPermission)->not->toBeNull()
        ->and($editPermission['module'])->toBe('example')
        ->and($editPermission['permission'])->toBe('edit')
        ->and($editPermission['description'])->toBe('Edit example module');
});

test('Example module routes are registered', function () {
    $routes = collect(Route::getRoutes())->map(fn($route) => $route->getName());
    
    expect($routes->contains('example.index'))->toBeTrue();
});

test('Example module routes are accessible with proper authentication and permission', function () {
    // Create permission
    Permission::create(['name' => 'module.example.view']);
    
    // Create a user with permission
    $user = User::factory()->create(['role' => 'user']);
    $user->givePermissionTo('module.example.view');
    
    $response = $this->actingAs($user)->get(route('example.index'));
    
    $response->assertStatus(200)
        ->assertSee('Example Module')
        ->assertSee('Welcome to the Example Module');
});

test('Example module routes are blocked for users without permission', function () {
    // Create a user without permission
    $user = User::factory()->create(['role' => 'user']);
    
    $response = $this->actingAs($user)->get(route('example.index'));
    
    $response->assertStatus(403);
});

test('Example module routes are accessible for super admin without explicit permission', function () {
    // Create a super admin user
    $superAdmin = User::factory()->create(['role' => 'super-admin']);
    
    $response = $this->actingAs($superAdmin)->get(route('example.index'));
    
    $response->assertStatus(200)
        ->assertSee('Example Module');
});

test('Example module views are registered', function () {
    expect(view()->exists('example::index'))->toBeTrue()
        ->and(view()->exists('example::widget'))->toBeTrue();
});

test('Example module widget view renders correctly', function () {
    $widgetView = view('example::widget')->render();
    
    expect($widgetView)->toContain('Example Module')
        ->and($widgetView)->toContain('Active')
        ->and($widgetView)->toContain('1.0.0')
        ->and($widgetView)->toContain('View Module');
});

test('Example module index view renders correctly for authenticated user', function () {
    // Create permission
    Permission::create(['name' => 'module.example.view']);
    
    $user = User::factory()->create(['role' => 'user']);
    $user->givePermissionTo('module.example.view');
    
    $this->actingAs($user);
    
    $indexView = view('example::index')->render();
    
    expect($indexView)->toContain('Example Module')
        ->and($indexView)->toContain('Welcome to the Example Module')
        ->and($indexView)->toContain('Module Information')
        ->and($indexView)->toContain('Your Permissions');
});
