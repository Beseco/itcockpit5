<?php

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
    
    $this->scanner = new ModuleScanner();
});

afterEach(function () {
    // Clean up test modules directory
    if (File::isDirectory($this->testModulesPath)) {
        File::deleteDirectory($this->testModulesPath);
    }
});

test('scan returns empty array when Modules directory does not exist', function () {
    // Remove the Modules directory
    File::deleteDirectory($this->testModulesPath);
    
    $result = $this->scanner->scan();
    
    expect($result)->toBeArray()->toBeEmpty();
});

test('scan returns empty array when Modules directory is empty', function () {
    $result = $this->scanner->scan();
    
    expect($result)->toBeArray()->toBeEmpty();
});

test('scan returns valid module metadata for a properly structured module', function () {
    // Create a valid test module
    $modulePath = $this->testModulesPath . '/TestModule';
    File::makeDirectory($modulePath . '/Providers', 0755, true);
    
    // Create module.json
    $moduleJson = [
        'name' => 'Test Module',
        'slug' => 'test-module',
        'version' => '1.0.0',
        'description' => 'A test module',
        'author' => 'Test Author'
    ];
    File::put($modulePath . '/module.json', json_encode($moduleJson, JSON_PRETTY_PRINT));
    
    // Create ServiceProvider
    File::put($modulePath . '/Providers/TestModuleServiceProvider.php', '<?php namespace App\Modules\TestModule\Providers; class TestModuleServiceProvider {}');
    
    $result = $this->scanner->scan();
    
    expect($result)->toBeArray()
        ->toHaveCount(1)
        ->and($result[0])->toHaveKeys(['name', 'slug', 'version'])
        ->and($result[0]['name'])->toBe('Test Module')
        ->and($result[0]['slug'])->toBe('test-module')
        ->and($result[0]['version'])->toBe('1.0.0');
});

test('scan returns multiple valid modules', function () {
    // Create first module
    $module1Path = $this->testModulesPath . '/Module1';
    File::makeDirectory($module1Path . '/Providers', 0755, true);
    File::put($module1Path . '/module.json', json_encode([
        'name' => 'Module One',
        'slug' => 'module-one',
        'version' => '1.0.0'
    ]));
    File::put($module1Path . '/Providers/Module1ServiceProvider.php', '<?php class Module1ServiceProvider {}');
    
    // Create second module
    $module2Path = $this->testModulesPath . '/Module2';
    File::makeDirectory($module2Path . '/Providers', 0755, true);
    File::put($module2Path . '/module.json', json_encode([
        'name' => 'Module Two',
        'slug' => 'module-two',
        'version' => '2.0.0'
    ]));
    File::put($module2Path . '/Providers/Module2ServiceProvider.php', '<?php class Module2ServiceProvider {}');
    
    $result = $this->scanner->scan();
    
    expect($result)->toBeArray()->toHaveCount(2);
});

test('validateModule returns false when module.json is missing', function () {
    $modulePath = $this->testModulesPath . '/InvalidModule';
    File::makeDirectory($modulePath . '/Providers', 0755, true);
    File::put($modulePath . '/Providers/InvalidModuleServiceProvider.php', '<?php class InvalidModuleServiceProvider {}');
    
    Log::shouldReceive('error')
        ->once()
        ->with('Module missing module.json', \Mockery::type('array'));
    
    $result = $this->scanner->validateModule($modulePath);
    
    expect($result)->toBeFalse();
});

test('validateModule returns false when module.json has invalid JSON', function () {
    $modulePath = $this->testModulesPath . '/InvalidModule';
    File::makeDirectory($modulePath . '/Providers', 0755, true);
    File::put($modulePath . '/module.json', '{invalid json}');
    File::put($modulePath . '/Providers/InvalidModuleServiceProvider.php', '<?php class InvalidModuleServiceProvider {}');
    
    Log::shouldReceive('error')
        ->once()
        ->with('Module has invalid JSON in module.json', \Mockery::type('array'));
    
    $result = $this->scanner->validateModule($modulePath);
    
    expect($result)->toBeFalse();
});

test('validateModule returns false when name field is missing', function () {
    $modulePath = $this->testModulesPath . '/InvalidModule';
    File::makeDirectory($modulePath . '/Providers', 0755, true);
    File::put($modulePath . '/module.json', json_encode([
        'slug' => 'invalid-module',
        'version' => '1.0.0'
    ]));
    File::put($modulePath . '/Providers/InvalidModuleServiceProvider.php', '<?php class InvalidModuleServiceProvider {}');
    
    Log::shouldReceive('error')
        ->once()
        ->with('Module missing required field: name', \Mockery::type('array'));
    
    $result = $this->scanner->validateModule($modulePath);
    
    expect($result)->toBeFalse();
});

test('validateModule returns false when slug field is missing', function () {
    $modulePath = $this->testModulesPath . '/InvalidModule';
    File::makeDirectory($modulePath . '/Providers', 0755, true);
    File::put($modulePath . '/module.json', json_encode([
        'name' => 'Invalid Module',
        'version' => '1.0.0'
    ]));
    File::put($modulePath . '/Providers/InvalidModuleServiceProvider.php', '<?php class InvalidModuleServiceProvider {}');
    
    Log::shouldReceive('error')
        ->once()
        ->with('Module missing required field: slug', \Mockery::type('array'));
    
    $result = $this->scanner->validateModule($modulePath);
    
    expect($result)->toBeFalse();
});

test('validateModule returns false when version field is missing', function () {
    $modulePath = $this->testModulesPath . '/InvalidModule';
    File::makeDirectory($modulePath . '/Providers', 0755, true);
    File::put($modulePath . '/module.json', json_encode([
        'name' => 'Invalid Module',
        'slug' => 'invalid-module'
    ]));
    File::put($modulePath . '/Providers/InvalidModuleServiceProvider.php', '<?php class InvalidModuleServiceProvider {}');
    
    Log::shouldReceive('error')
        ->once()
        ->with('Module missing required field: version', \Mockery::type('array'));
    
    $result = $this->scanner->validateModule($modulePath);
    
    expect($result)->toBeFalse();
});

test('validateModule returns false when ServiceProvider is missing', function () {
    $modulePath = $this->testModulesPath . '/InvalidModule';
    File::makeDirectory($modulePath, 0755, true);
    File::put($modulePath . '/module.json', json_encode([
        'name' => 'Invalid Module',
        'slug' => 'invalid-module',
        'version' => '1.0.0'
    ]));
    
    Log::shouldReceive('error')
        ->once()
        ->with('Module ServiceProvider not found', \Mockery::type('array'));
    
    $result = $this->scanner->validateModule($modulePath);
    
    expect($result)->toBeFalse();
});

test('validateModule returns true for a valid module', function () {
    $modulePath = $this->testModulesPath . '/ValidModule';
    File::makeDirectory($modulePath . '/Providers', 0755, true);
    File::put($modulePath . '/module.json', json_encode([
        'name' => 'Valid Module',
        'slug' => 'valid-module',
        'version' => '1.0.0'
    ]));
    File::put($modulePath . '/Providers/ValidModuleServiceProvider.php', '<?php class ValidModuleServiceProvider {}');
    
    $result = $this->scanner->validateModule($modulePath);
    
    expect($result)->toBeTrue();
});

test('scan skips invalid modules and continues with valid ones', function () {
    // Create a valid module
    $validModulePath = $this->testModulesPath . '/ValidModule';
    File::makeDirectory($validModulePath . '/Providers', 0755, true);
    File::put($validModulePath . '/module.json', json_encode([
        'name' => 'Valid Module',
        'slug' => 'valid-module',
        'version' => '1.0.0'
    ]));
    File::put($validModulePath . '/Providers/ValidModuleServiceProvider.php', '<?php class ValidModuleServiceProvider {}');
    
    // Create an invalid module (missing module.json)
    $invalidModulePath = $this->testModulesPath . '/InvalidModule';
    File::makeDirectory($invalidModulePath . '/Providers', 0755, true);
    File::put($invalidModulePath . '/Providers/InvalidModuleServiceProvider.php', '<?php class InvalidModuleServiceProvider {}');
    
    Log::shouldReceive('error')->once();
    Log::shouldReceive('info')->once();
    
    $result = $this->scanner->scan();
    
    // Should only return the valid module
    expect($result)->toBeArray()
        ->toHaveCount(1)
        ->and($result[0]['slug'])->toBe('valid-module');
});

test('scan logs errors for invalid modules', function () {
    $invalidModulePath = $this->testModulesPath . '/InvalidModule';
    File::makeDirectory($invalidModulePath, 0755, true);
    
    Log::shouldReceive('error')
        ->once()
        ->with('Module missing module.json', \Mockery::type('array'));
    
    $result = $this->scanner->scan();
    
    expect($result)->toBeArray()->toBeEmpty();
});

test('scan includes optional metadata fields when present', function () {
    $modulePath = $this->testModulesPath . '/TestModule';
    File::makeDirectory($modulePath . '/Providers', 0755, true);
    
    $moduleJson = [
        'name' => 'Test Module',
        'slug' => 'test-module',
        'version' => '1.0.0',
        'description' => 'A comprehensive test module',
        'author' => 'John Doe'
    ];
    File::put($modulePath . '/module.json', json_encode($moduleJson));
    File::put($modulePath . '/Providers/TestModuleServiceProvider.php', '<?php class TestModuleServiceProvider {}');
    
    $result = $this->scanner->scan();
    
    expect($result)->toBeArray()
        ->toHaveCount(1)
        ->and($result[0])->toHaveKey('description')
        ->and($result[0])->toHaveKey('author')
        ->and($result[0]['description'])->toBe('A comprehensive test module')
        ->and($result[0]['author'])->toBe('John Doe');
});
