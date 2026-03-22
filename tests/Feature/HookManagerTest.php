<?php

use App\Models\User;
use App\Services\HookManager;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->hookManager = new HookManager();
});

// Sidebar Item Tests

test('registerSidebarItem stores sidebar item', function () {
    $item = [
        'label' => 'Inventory',
        'route' => 'inventory.index',
        'icon' => 'heroicon-o-cube',
        'permission' => 'module.inventory.view'
    ];
    
    $this->hookManager->registerSidebarItem('inventory', $item);
    
    $user = User::factory()->create(['role' => 'super-admin']);
    $items = $this->hookManager->getSidebarItems($user);
    
    expect($items)->toHaveCount(1)
        ->and($items->first()['label'])->toBe('Inventory')
        ->and($items->first()['route'])->toBe('inventory.index')
        ->and($items->first()['module'])->toBe('inventory');
});

test('registerSidebarItem adds module context to item', function () {
    $item = [
        'label' => 'Test Module',
        'route' => 'test.index'
    ];
    
    $this->hookManager->registerSidebarItem('test-module', $item);
    
    $user = User::factory()->create(['role' => 'super-admin']);
    $items = $this->hookManager->getSidebarItems($user);
    
    expect($items->first()['module'])->toBe('test-module');
});

test('registerSidebarItem does not add item without label', function () {
    $item = [
        'route' => 'test.index'
    ];
    
    Log::shouldReceive('error')
        ->once()
        ->with('Sidebar item missing required fields', \Mockery::type('array'));
    
    $this->hookManager->registerSidebarItem('test-module', $item);
    
    $user = User::factory()->create(['role' => 'super-admin']);
    $items = $this->hookManager->getSidebarItems($user);
    
    expect($items)->toBeEmpty();
});

test('registerSidebarItem does not add item without route', function () {
    $item = [
        'label' => 'Test Module'
    ];
    
    Log::shouldReceive('error')
        ->once()
        ->with('Sidebar item missing required fields', \Mockery::type('array'));
    
    $this->hookManager->registerSidebarItem('test-module', $item);
    
    $user = User::factory()->create(['role' => 'super-admin']);
    $items = $this->hookManager->getSidebarItems($user);
    
    expect($items)->toBeEmpty();
});

test('registerSidebarItem logs successful registration', function () {
    $item = [
        'label' => 'Inventory',
        'route' => 'inventory.index'
    ];
    
    Log::shouldReceive('info')
        ->once()
        ->with('Sidebar item registered', [
            'module' => 'inventory',
            'label' => 'Inventory'
        ]);
    
    $this->hookManager->registerSidebarItem('inventory', $item);
});

test('registerSidebarItem can register multiple items', function () {
    $item1 = ['label' => 'Module One', 'route' => 'module-one.index'];
    $item2 = ['label' => 'Module Two', 'route' => 'module-two.index'];
    
    $this->hookManager->registerSidebarItem('module-one', $item1);
    $this->hookManager->registerSidebarItem('module-two', $item2);
    
    $user = User::factory()->create(['role' => 'super-admin']);
    $items = $this->hookManager->getSidebarItems($user);
    
    expect($items)->toHaveCount(2);
});

// getSidebarItems Permission Tests

test('getSidebarItems returns all items for super admin', function () {
    $item1 = ['label' => 'Module One', 'route' => 'module-one.index'];
    $item2 = ['label' => 'Module Two', 'route' => 'module-two.index'];
    
    $this->hookManager->registerSidebarItem('module-one', $item1);
    $this->hookManager->registerSidebarItem('module-two', $item2);
    
    $user = User::factory()->create(['role' => 'super-admin']);
    $items = $this->hookManager->getSidebarItems($user);
    
    expect($items)->toHaveCount(2);
});

test('getSidebarItems filters items by explicit permission', function () {
    Permission::create(['name' => 'module.inventory.view']);
    
    $item = [
        'label' => 'Inventory',
        'route' => 'inventory.index',
        'permission' => 'module.inventory.view'
    ];
    
    $this->hookManager->registerSidebarItem('inventory', $item);
    
    $userWithPermission = User::factory()->create(['role' => 'user']);
    $userWithPermission->givePermissionTo('module.inventory.view');
    
    $userWithoutPermission = User::factory()->create(['role' => 'user']);
    
    $itemsWithPermission = $this->hookManager->getSidebarItems($userWithPermission);
    $itemsWithoutPermission = $this->hookManager->getSidebarItems($userWithoutPermission);
    
    expect($itemsWithPermission)->toHaveCount(1)
        ->and($itemsWithoutPermission)->toBeEmpty();
});

test('getSidebarItems filters items by default module permission', function () {
    Permission::create(['name' => 'module.inventory.view']);
    
    $item = [
        'label' => 'Inventory',
        'route' => 'inventory.index'
    ];
    
    $this->hookManager->registerSidebarItem('inventory', $item);
    
    $userWithPermission = User::factory()->create(['role' => 'user']);
    $userWithPermission->givePermissionTo('module.inventory.view');
    
    $userWithoutPermission = User::factory()->create(['role' => 'user']);
    
    $itemsWithPermission = $this->hookManager->getSidebarItems($userWithPermission);
    $itemsWithoutPermission = $this->hookManager->getSidebarItems($userWithoutPermission);
    
    expect($itemsWithPermission)->toHaveCount(1)
        ->and($itemsWithoutPermission)->toBeEmpty();
});

test('getSidebarItems returns empty collection when user has no permissions', function () {
    $item = ['label' => 'Inventory', 'route' => 'inventory.index'];
    
    $this->hookManager->registerSidebarItem('inventory', $item);
    
    $user = User::factory()->create(['role' => 'user']);
    $items = $this->hookManager->getSidebarItems($user);
    
    expect($items)->toBeEmpty();
});

// Dashboard Widget Tests

test('registerDashboardWidget stores widget', function () {
    $this->hookManager->registerDashboardWidget('inventory', 'inventory::widget');
    
    $user = User::factory()->create(['role' => 'super-admin']);
    $widgets = $this->hookManager->getDashboardWidgets($user);
    
    expect($widgets)->toHaveCount(1)
        ->and($widgets->first()['module'])->toBe('inventory')
        ->and($widgets->first()['viewPath'])->toBe('inventory::widget');
});

test('registerDashboardWidget does not add widget without view path', function () {
    Log::shouldReceive('error')
        ->once()
        ->with('Dashboard widget missing view path', ['module' => 'test-module']);
    
    $this->hookManager->registerDashboardWidget('test-module', '');
    
    $user = User::factory()->create(['role' => 'super-admin']);
    $widgets = $this->hookManager->getDashboardWidgets($user);
    
    expect($widgets)->toBeEmpty();
});

test('registerDashboardWidget logs successful registration', function () {
    Log::shouldReceive('info')
        ->once()
        ->with('Dashboard widget registered', [
            'module' => 'inventory',
            'viewPath' => 'inventory::widget'
        ]);
    
    $this->hookManager->registerDashboardWidget('inventory', 'inventory::widget');
});

test('registerDashboardWidget can register multiple widgets', function () {
    $this->hookManager->registerDashboardWidget('module-one', 'module-one::widget');
    $this->hookManager->registerDashboardWidget('module-two', 'module-two::widget');
    
    $user = User::factory()->create(['role' => 'super-admin']);
    $widgets = $this->hookManager->getDashboardWidgets($user);
    
    expect($widgets)->toHaveCount(2);
});

// getDashboardWidgets Permission Tests

test('getDashboardWidgets returns all widgets for super admin', function () {
    $this->hookManager->registerDashboardWidget('module-one', 'module-one::widget');
    $this->hookManager->registerDashboardWidget('module-two', 'module-two::widget');
    
    $user = User::factory()->create(['role' => 'super-admin']);
    $widgets = $this->hookManager->getDashboardWidgets($user);
    
    expect($widgets)->toHaveCount(2);
});

test('getDashboardWidgets filters widgets by module permission', function () {
    Permission::create(['name' => 'module.inventory.view']);
    
    $this->hookManager->registerDashboardWidget('inventory', 'inventory::widget');
    
    $userWithPermission = User::factory()->create(['role' => 'user']);
    $userWithPermission->givePermissionTo('module.inventory.view');
    
    $userWithoutPermission = User::factory()->create(['role' => 'user']);
    
    $widgetsWithPermission = $this->hookManager->getDashboardWidgets($userWithPermission);
    $widgetsWithoutPermission = $this->hookManager->getDashboardWidgets($userWithoutPermission);
    
    expect($widgetsWithPermission)->toHaveCount(1)
        ->and($widgetsWithoutPermission)->toBeEmpty();
});

test('getDashboardWidgets returns empty collection when user has no permissions', function () {
    $this->hookManager->registerDashboardWidget('inventory', 'inventory::widget');
    
    $user = User::factory()->create(['role' => 'user']);
    $widgets = $this->hookManager->getDashboardWidgets($user);
    
    expect($widgets)->toBeEmpty();
});

// Permission Registration Tests

test('registerPermission stores permission metadata', function () {
    $this->hookManager->registerPermission('inventory', 'view', 'View inventory module');
    
    $permissions = $this->hookManager->getRegisteredPermissions();
    
    expect($permissions)->toHaveCount(1)
        ->and($permissions->first()['name'])->toBe('module.inventory.view')
        ->and($permissions->first()['module'])->toBe('inventory')
        ->and($permissions->first()['permission'])->toBe('view')
        ->and($permissions->first()['description'])->toBe('View inventory module');
});

test('registerPermission formats permission name correctly', function () {
    $this->hookManager->registerPermission('test-module', 'edit', 'Edit test module');
    
    $permissions = $this->hookManager->getRegisteredPermissions();
    
    expect($permissions->first()['name'])->toBe('module.test-module.edit');
});

test('registerPermission does not add permission without module', function () {
    Log::shouldReceive('error')
        ->once()
        ->with('Permission registration missing required fields', \Mockery::type('array'));
    
    $this->hookManager->registerPermission('', 'view', 'Test permission');
    
    $permissions = $this->hookManager->getRegisteredPermissions();
    
    expect($permissions)->toBeEmpty();
});

test('registerPermission does not add permission without permission name', function () {
    Log::shouldReceive('error')
        ->once()
        ->with('Permission registration missing required fields', \Mockery::type('array'));
    
    $this->hookManager->registerPermission('test-module', '', 'Test permission');
    
    $permissions = $this->hookManager->getRegisteredPermissions();
    
    expect($permissions)->toBeEmpty();
});

test('registerPermission logs successful registration', function () {
    Log::shouldReceive('info')
        ->once()
        ->with('Permission registered', [
            'module' => 'inventory',
            'permission' => 'module.inventory.view',
            'description' => 'View inventory module'
        ]);
    
    $this->hookManager->registerPermission('inventory', 'view', 'View inventory module');
});

test('registerPermission can register multiple permissions', function () {
    $this->hookManager->registerPermission('inventory', 'view', 'View inventory');
    $this->hookManager->registerPermission('inventory', 'edit', 'Edit inventory');
    $this->hookManager->registerPermission('users', 'view', 'View users');
    
    $permissions = $this->hookManager->getRegisteredPermissions();
    
    expect($permissions)->toHaveCount(3);
});

test('getRegisteredPermissions returns all registered permissions', function () {
    $this->hookManager->registerPermission('module-a', 'view', 'View module A');
    $this->hookManager->registerPermission('module-b', 'edit', 'Edit module B');
    
    $permissions = $this->hookManager->getRegisteredPermissions();
    
    expect($permissions)->toHaveCount(2)
        ->and($permissions->pluck('name')->toArray())->toBe([
            'module.module-a.view',
            'module.module-b.edit'
        ]);
});

// Integration Tests

test('HookManager can be resolved from container', function () {
    $hookManager = app(HookManager::class);
    
    expect($hookManager)->toBeInstanceOf(HookManager::class);
});

test('HookManager is singleton in container', function () {
    $instance1 = app(HookManager::class);
    $instance2 = app(HookManager::class);
    
    expect($instance1)->toBe($instance2);
});

test('complete module registration workflow', function () {
    Permission::create(['name' => 'module.inventory.view']);
    Permission::create(['name' => 'module.inventory.edit']);
    
    // Register module hooks
    $this->hookManager->registerSidebarItem('inventory', [
        'label' => 'Inventory',
        'route' => 'inventory.index',
        'icon' => 'heroicon-o-cube'
    ]);
    
    $this->hookManager->registerDashboardWidget('inventory', 'inventory::widget');
    
    $this->hookManager->registerPermission('inventory', 'view', 'View inventory module');
    $this->hookManager->registerPermission('inventory', 'edit', 'Edit inventory module');
    
    // Create user with view permission
    $user = User::factory()->create(['role' => 'user']);
    $user->givePermissionTo('module.inventory.view');
    
    // Verify user can see sidebar and widget
    $sidebarItems = $this->hookManager->getSidebarItems($user);
    $widgets = $this->hookManager->getDashboardWidgets($user);
    $permissions = $this->hookManager->getRegisteredPermissions();
    
    expect($sidebarItems)->toHaveCount(1)
        ->and($widgets)->toHaveCount(1)
        ->and($permissions)->toHaveCount(2);
});
