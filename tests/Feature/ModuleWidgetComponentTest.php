<?php

use App\Models\User;
use App\Services\HookManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;

uses(RefreshDatabase::class);

test('module widget component renders with correct Tailwind card styling', function () {
    $user = User::factory()->create(['role' => 'super-admin', 'is_active' => true]);
    
    // Create a test view for the widget
    View::addLocation(resource_path('views'));
    
    // Register a test widget
    $hookManager = app(HookManager::class);
    $hookManager->registerDashboardWidget('test-module', 'test-widget');
    
    // Create the test widget view
    $testWidgetPath = resource_path('views/test-widget.blade.php');
    file_put_contents($testWidgetPath, '<div>Test Widget Content</div>');
    
    $response = $this->actingAs($user)->get('/dashboard');
    
    $response->assertStatus(200);
    
    // Verify Tailwind card styling classes are present
    $content = $response->getContent();
    expect($content)->toContain('bg-white');
    expect($content)->toContain('overflow-hidden');
    expect($content)->toContain('shadow-sm');
    expect($content)->toContain('sm:rounded-lg');
    
    // Clean up
    if (file_exists($testWidgetPath)) {
        unlink($testWidgetPath);
    }
});

test('module widget component includes the provided view path', function () {
    $user = User::factory()->create(['role' => 'super-admin', 'is_active' => true]);
    
    // Register a test widget
    $hookManager = app(HookManager::class);
    $hookManager->registerDashboardWidget('test-module', 'test-widget-content');
    
    // Create the test widget view with specific content
    $testWidgetPath = resource_path('views/test-widget-content.blade.php');
    file_put_contents($testWidgetPath, '<h3>Module Widget Title</h3><p>Module widget body content</p>');
    
    $response = $this->actingAs($user)->get('/dashboard');
    
    $response->assertStatus(200);
    $response->assertSee('Module Widget Title');
    $response->assertSee('Module widget body content');
    
    // Clean up
    if (file_exists($testWidgetPath)) {
        unlink($testWidgetPath);
    }
});

test('module widget component applies padding to content area', function () {
    $user = User::factory()->create(['role' => 'super-admin', 'is_active' => true]);
    
    // Register a test widget
    $hookManager = app(HookManager::class);
    $hookManager->registerDashboardWidget('test-module', 'test-widget-padding');
    
    // Create the test widget view
    $testWidgetPath = resource_path('views/test-widget-padding.blade.php');
    file_put_contents($testWidgetPath, '<div>Padded Content</div>');
    
    $response = $this->actingAs($user)->get('/dashboard');
    
    $response->assertStatus(200);
    
    // Verify padding class is present
    $content = $response->getContent();
    expect($content)->toContain('p-6');
    
    // Clean up
    if (file_exists($testWidgetPath)) {
        unlink($testWidgetPath);
    }
});

test('module widget component renders multiple widgets in grid layout', function () {
    $user = User::factory()->create(['role' => 'super-admin', 'is_active' => true]);
    
    // Register multiple test widgets
    $hookManager = app(HookManager::class);
    $hookManager->registerDashboardWidget('module-one', 'widget-one');
    $hookManager->registerDashboardWidget('module-two', 'widget-two');
    
    // Create test widget views
    $widgetOnePath = resource_path('views/widget-one.blade.php');
    $widgetTwoPath = resource_path('views/widget-two.blade.php');
    file_put_contents($widgetOnePath, '<div>Widget One Content</div>');
    file_put_contents($widgetTwoPath, '<div>Widget Two Content</div>');
    
    $response = $this->actingAs($user)->get('/dashboard');
    
    $response->assertStatus(200);
    $response->assertSee('Widget One Content');
    $response->assertSee('Widget Two Content');
    
    // Verify grid layout classes are present
    $content = $response->getContent();
    expect($content)->toContain('grid');
    expect($content)->toContain('grid-cols-1');
    expect($content)->toContain('md:grid-cols-2');
    expect($content)->toContain('lg:grid-cols-3');
    expect($content)->toContain('gap-6');
    
    // Clean up
    if (file_exists($widgetOnePath)) {
        unlink($widgetOnePath);
    }
    if (file_exists($widgetTwoPath)) {
        unlink($widgetTwoPath);
    }
});
