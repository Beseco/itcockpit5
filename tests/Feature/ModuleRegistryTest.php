<?php

use App\Services\ModuleRegistry;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->registry = new ModuleRegistry();
});

test('getRegisteredModules returns empty collection initially', function () {
    $result = $this->registry->getRegisteredModules();
    
    expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->toBeEmpty();
});

test('register adds module to registry', function () {
    $moduleMetadata = [
        'name' => 'Test Module',
        'slug' => 'test-module',
        'version' => '1.0.0'
    ];
    
    $this->registry->register($moduleMetadata);
    
    $modules = $this->registry->getRegisteredModules();
    expect($modules)->toHaveCount(1)
        ->and($modules->get('test-module'))->toBe($moduleMetadata);
});

test('register stores module metadata correctly', function () {
    $moduleMetadata = [
        'name' => 'Inventory Module',
        'slug' => 'inventory',
        'version' => '2.5.0',
        'description' => 'Manages inventory',
        'author' => 'John Doe'
    ];
    
    $this->registry->register($moduleMetadata);
    
    $module = $this->registry->getModuleBySlug('inventory');
    expect($module)->toBe($moduleMetadata)
        ->and($module['name'])->toBe('Inventory Module')
        ->and($module['version'])->toBe('2.5.0')
        ->and($module['description'])->toBe('Manages inventory')
        ->and($module['author'])->toBe('John Doe');
});

test('register can register multiple modules', function () {
    $module1 = [
        'name' => 'Module One',
        'slug' => 'module-one',
        'version' => '1.0.0'
    ];
    
    $module2 = [
        'name' => 'Module Two',
        'slug' => 'module-two',
        'version' => '2.0.0'
    ];
    
    $this->registry->register($module1);
    $this->registry->register($module2);
    
    $modules = $this->registry->getRegisteredModules();
    expect($modules)->toHaveCount(2)
        ->and($modules->get('module-one'))->toBe($module1)
        ->and($modules->get('module-two'))->toBe($module2);
});

test('register does not add module without slug', function () {
    $moduleMetadata = [
        'name' => 'Invalid Module',
        'version' => '1.0.0'
    ];
    
    Log::shouldReceive('error')
        ->once()
        ->with('Cannot register module without slug', \Mockery::type('array'));
    
    $this->registry->register($moduleMetadata);
    
    $modules = $this->registry->getRegisteredModules();
    expect($modules)->toBeEmpty();
});

test('register logs warning when module is already registered', function () {
    $moduleMetadata = [
        'name' => 'Test Module',
        'slug' => 'test-module',
        'version' => '1.0.0'
    ];
    
    Log::shouldReceive('info')->once();
    Log::shouldReceive('warning')
        ->once()
        ->with('Module already registered', ['slug' => 'test-module']);
    
    $this->registry->register($moduleMetadata);
    $this->registry->register($moduleMetadata);
    
    $modules = $this->registry->getRegisteredModules();
    expect($modules)->toHaveCount(1);
});

test('getModuleBySlug returns module metadata when found', function () {
    $moduleMetadata = [
        'name' => 'Test Module',
        'slug' => 'test-module',
        'version' => '1.0.0'
    ];
    
    $this->registry->register($moduleMetadata);
    
    $result = $this->registry->getModuleBySlug('test-module');
    expect($result)->toBe($moduleMetadata);
});

test('getModuleBySlug returns null when module not found', function () {
    $result = $this->registry->getModuleBySlug('non-existent-module');
    
    expect($result)->toBeNull();
});

test('isModuleRegistered returns true for registered module', function () {
    $moduleMetadata = [
        'name' => 'Test Module',
        'slug' => 'test-module',
        'version' => '1.0.0'
    ];
    
    $this->registry->register($moduleMetadata);
    
    expect($this->registry->isModuleRegistered('test-module'))->toBeTrue();
});

test('isModuleRegistered returns false for unregistered module', function () {
    expect($this->registry->isModuleRegistered('non-existent-module'))->toBeFalse();
});

test('register logs successful registration', function () {
    $moduleMetadata = [
        'name' => 'Test Module',
        'slug' => 'test-module',
        'version' => '1.0.0'
    ];
    
    Log::shouldReceive('info')
        ->once()
        ->with('Module registered successfully', [
            'slug' => 'test-module',
            'name' => 'Test Module'
        ]);
    
    $this->registry->register($moduleMetadata);
});

test('register handles module without name field', function () {
    $moduleMetadata = [
        'slug' => 'test-module',
        'version' => '1.0.0'
    ];
    
    Log::shouldReceive('info')
        ->once()
        ->with('Module registered successfully', [
            'slug' => 'test-module',
            'name' => 'Unknown'
        ]);
    
    $this->registry->register($moduleMetadata);
    
    $module = $this->registry->getModuleBySlug('test-module');
    expect($module)->toBe($moduleMetadata);
});

test('getRegisteredModules returns all registered modules', function () {
    $modules = [
        [
            'name' => 'Module A',
            'slug' => 'module-a',
            'version' => '1.0.0'
        ],
        [
            'name' => 'Module B',
            'slug' => 'module-b',
            'version' => '2.0.0'
        ],
        [
            'name' => 'Module C',
            'slug' => 'module-c',
            'version' => '3.0.0'
        ]
    ];
    
    foreach ($modules as $module) {
        $this->registry->register($module);
    }
    
    $registered = $this->registry->getRegisteredModules();
    expect($registered)->toHaveCount(3)
        ->and($registered->keys()->toArray())->toBe(['module-a', 'module-b', 'module-c']);
});

test('register preserves all metadata fields', function () {
    $moduleMetadata = [
        'name' => 'Complex Module',
        'slug' => 'complex-module',
        'version' => '1.2.3',
        'description' => 'A complex module with many features',
        'author' => 'Jane Smith',
        'dependencies' => ['module-a', 'module-b'],
        'custom_field' => 'custom value'
    ];
    
    $this->registry->register($moduleMetadata);
    
    $module = $this->registry->getModuleBySlug('complex-module');
    expect($module)->toBe($moduleMetadata)
        ->and($module)->toHaveKey('dependencies')
        ->and($module)->toHaveKey('custom_field')
        ->and($module['custom_field'])->toBe('custom value');
});

