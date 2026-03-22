<?php

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('dashboard displays active announcements ordered by type', function () {
    $user = User::factory()->create(['role' => 'user', 'is_active' => true]);

    // Create announcements in reverse order to test ordering
    $infoAnnouncement = Announcement::factory()->create([
        'type' => 'info',
        'message' => 'Info message',
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
        'is_fixed' => false,
    ]);

    $maintenanceAnnouncement = Announcement::factory()->create([
        'type' => 'maintenance',
        'message' => 'Maintenance message',
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
        'is_fixed' => false,
    ]);

    $criticalAnnouncement = Announcement::factory()->create([
        'type' => 'critical',
        'message' => 'Critical message',
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
        'is_fixed' => false,
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('Critical message');
    $response->assertSee('Maintenance message');
    $response->assertSee('Info message');

    // Verify ordering: critical should appear before maintenance, maintenance before info
    $content = $response->getContent();
    $criticalPos = strpos($content, 'Critical message');
    $maintenancePos = strpos($content, 'Maintenance message');
    $infoPos = strpos($content, 'Info message');

    expect($criticalPos)->toBeLessThan($maintenancePos);
    expect($maintenancePos)->toBeLessThan($infoPos);
});

test('dashboard only displays active announcements', function () {
    $user = User::factory()->create(['role' => 'user', 'is_active' => true]);

    // Create an active announcement
    $activeAnnouncement = Announcement::factory()->create([
        'type' => 'info',
        'message' => 'Active announcement',
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
        'is_fixed' => false,
    ]);

    // Create an announcement that hasn't started yet
    $futureAnnouncement = Announcement::factory()->create([
        'type' => 'info',
        'message' => 'Future announcement',
        'starts_at' => now()->addHour(),
        'ends_at' => now()->addHours(2),
        'is_fixed' => false,
    ]);

    // Create an announcement that has ended
    $pastAnnouncement = Announcement::factory()->create([
        'type' => 'info',
        'message' => 'Past announcement',
        'starts_at' => now()->subHours(2),
        'ends_at' => now()->subHour(),
        'is_fixed' => false,
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('Active announcement');
    $response->assertDontSee('Future announcement');
    $response->assertDontSee('Past announcement');
});

test('dashboard displays fixed announcements within 8 hours', function () {
    $user = User::factory()->create(['role' => 'user', 'is_active' => true]);

    // Create a fixed announcement within 8 hours
    $recentlyFixed = Announcement::factory()->create([
        'type' => 'critical',
        'message' => 'Recently fixed',
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
        'is_fixed' => true,
        'fixed_at' => now()->subHours(4),
    ]);

    // Create a fixed announcement older than 8 hours
    $oldFixed = Announcement::factory()->create([
        'type' => 'critical',
        'message' => 'Old fixed',
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
        'is_fixed' => true,
        'fixed_at' => now()->subHours(9),
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('Recently fixed');
    $response->assertDontSee('Old fixed');
});

test('dashboard shows mark as fixed button for critical announcements to admins', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

    $criticalAnnouncement = Announcement::factory()->create([
        'type' => 'critical',
        'message' => 'Critical issue',
        'is_fixed' => false,
    ]);

    $response = $this->actingAs($admin)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('Mark as Fixed');
});

test('dashboard does not show mark as fixed button for resolved announcements', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

    $resolvedAnnouncement = Announcement::factory()->create([
        'type' => 'critical',
        'message' => 'Resolved issue',
        'is_fixed' => true,
        'fixed_at' => now()->subHour(),
    ]);

    $response = $this->actingAs($admin)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('Resolved');
    $response->assertDontSee('Mark as Fixed');
});

test('dashboard does not show mark as fixed button to regular users', function () {
    $user = User::factory()->create(['role' => 'user', 'is_active' => true]);

    $criticalAnnouncement = Announcement::factory()->create([
        'type' => 'critical',
        'message' => 'Critical issue',
        'is_fixed' => false,
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('Critical issue');
    $response->assertDontSee('Mark as Fixed');
});

test('dashboard shows empty state when no announcements exist', function () {
    $user = User::factory()->create(['role' => 'user', 'is_active' => true]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('Welcome to IT Cockpit');
});

test('dashboard shows module placeholder message when no modules available', function () {
    $user = User::factory()->create(['role' => 'user', 'is_active' => true]);

    // Create an announcement so we see the module message instead of welcome message
    Announcement::factory()->create([
        'type' => 'info',
        'message' => 'Test announcement',
        'is_fixed' => false,
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('No modules available');
});

test('dashboard requires authentication', function () {
    $response = $this->get('/dashboard');

    $response->assertRedirect('/login');
});

test('root route redirects authenticated users to dashboard', function () {
    $user = User::factory()->create(['role' => 'user', 'is_active' => true]);

    $response = $this->actingAs($user)->get('/');

    $response->assertRedirect(route('dashboard'));
});

test('root route requires authentication', function () {
    $response = $this->get('/');

    $response->assertRedirect('/login');
});

test('dashboard fetches widgets from HookManager filtered by user permissions', function () {
    $user = User::factory()->create(['role' => 'user', 'is_active' => true]);

    // Create a simple test widget view
    if (!file_exists(resource_path('views/components'))) {
        mkdir(resource_path('views/components'), 0755, true);
    }
    file_put_contents(
        resource_path('views/components/mock-widget.blade.php'),
        '<div class="mock-widget">Mock Widget</div>'
    );

    // Mock HookManager to return a widget
    $hookManager = Mockery::mock(\App\Services\HookManager::class);
    $hookManager->shouldReceive('getDashboardWidgets')
        ->once()
        ->with(Mockery::on(fn($u) => $u->id === $user->id))
        ->andReturn(collect([
            ['module' => 'test-module', 'viewPath' => 'components.mock-widget']
        ]));

    $this->app->instance(\App\Services\HookManager::class, $hookManager);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('Mock Widget');

    // Clean up
    @unlink(resource_path('views/components/mock-widget.blade.php'));
});

test('dashboard displays widgets for modules user has permission to view', function () {
    $user = User::factory()->create(['role' => 'super-admin', 'is_active' => true]);

    // Register a test widget
    $hookManager = app(\App\Services\HookManager::class);
    $hookManager->registerDashboardWidget('test-module', 'components.test-widget');

    // Create a simple test widget view
    if (!file_exists(resource_path('views/components'))) {
        mkdir(resource_path('views/components'), 0755, true);
    }
    file_put_contents(
        resource_path('views/components/test-widget.blade.php'),
        '<div class="test-widget">Test Widget Content</div>'
    );

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('Test Widget Content');

    // Clean up
    @unlink(resource_path('views/components/test-widget.blade.php'));
});

test('dashboard does not display widgets for modules user lacks permission to view', function () {
    // Create a regular user without super-admin privileges
    $user = User::factory()->create(['role' => 'user', 'is_active' => true]);

    // Register a test widget
    $hookManager = app(\App\Services\HookManager::class);
    $hookManager->registerDashboardWidget('restricted-module', 'components.restricted-widget');

    // Create a simple test widget view
    if (!file_exists(resource_path('views/components'))) {
        mkdir(resource_path('views/components'), 0755, true);
    }
    file_put_contents(
        resource_path('views/components/restricted-widget.blade.php'),
        '<div class="restricted-widget">Restricted Widget Content</div>'
    );

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    // User should not see the restricted widget
    $response->assertDontSee('Restricted Widget Content');

    // Clean up
    @unlink(resource_path('views/components/restricted-widget.blade.php'));
});

