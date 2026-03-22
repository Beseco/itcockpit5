<?php

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('announcement card displays critical announcement with correct styling', function () {
    $user = User::factory()->create(['role' => 'user', 'is_active' => true]);
    
    $announcement = Announcement::factory()->create([
        'type' => 'critical',
        'message' => 'Critical system issue',
        'is_fixed' => false,
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('Critical system issue');
    $response->assertSee('Critical');
    // Verify red styling is applied
    expect($response->getContent())->toContain('bg-red-100 border-red-500 text-red-900');
});

test('announcement card displays resolved critical announcement with green styling', function () {
    $user = User::factory()->create(['role' => 'user', 'is_active' => true]);
    
    $announcement = Announcement::factory()->create([
        'type' => 'critical',
        'message' => 'Resolved critical issue',
        'is_fixed' => true,
        'fixed_at' => now()->subHour(),
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('Resolved critical issue');
    $response->assertSee('(Resolved)');
    // Verify green styling is applied
    expect($response->getContent())->toContain('bg-green-100 border-green-500 text-green-900');
});

test('announcement card displays maintenance announcement with yellow styling', function () {
    $user = User::factory()->create(['role' => 'user', 'is_active' => true]);
    
    $announcement = Announcement::factory()->create([
        'type' => 'maintenance',
        'message' => 'Scheduled maintenance',
        'is_fixed' => false,
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('Scheduled maintenance');
    $response->assertSee('Maintenance');
    // Verify yellow styling is applied
    expect($response->getContent())->toContain('bg-yellow-100 border-yellow-500 text-yellow-900');
});

test('announcement card displays info announcement with blue styling', function () {
    $user = User::factory()->create(['role' => 'user', 'is_active' => true]);
    
    $announcement = Announcement::factory()->create([
        'type' => 'info',
        'message' => 'Information message',
        'is_fixed' => false,
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('Information message');
    $response->assertSee('Info');
    // Verify blue styling is applied
    expect($response->getContent())->toContain('bg-blue-100 border-blue-500 text-blue-900');
});

test('announcement card displays timestamps when provided', function () {
    $user = User::factory()->create(['role' => 'user', 'is_active' => true]);
    
    $startsAt = now()->subHour();
    $endsAt = now()->addHour();
    
    $announcement = Announcement::factory()->create([
        'type' => 'info',
        'message' => 'Timed announcement',
        'starts_at' => $startsAt,
        'ends_at' => $endsAt,
        'is_fixed' => false,
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('From: ' . $startsAt->format('M d, Y H:i'));
    $response->assertSee('To: ' . $endsAt->format('M d, Y H:i'));
});

test('announcement card displays fixed_at timestamp when announcement is resolved', function () {
    $user = User::factory()->create(['role' => 'user', 'is_active' => true]);
    
    $fixedAt = now()->subHours(2);
    
    $announcement = Announcement::factory()->create([
        'type' => 'critical',
        'message' => 'Fixed issue',
        'is_fixed' => true,
        'fixed_at' => $fixedAt,
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('Fixed at: ' . $fixedAt->format('M d, Y H:i'));
});

test('announcement card shows mark as fixed button for critical announcements to admins', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    
    $announcement = Announcement::factory()->create([
        'type' => 'critical',
        'message' => 'Critical issue',
        'is_fixed' => false,
    ]);

    $response = $this->actingAs($admin)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('Mark as Fixed');
});

test('announcement card shows mark as fixed button for critical announcements to super admins', function () {
    $superAdmin = User::factory()->create(['role' => 'super-admin', 'is_active' => true]);
    
    $announcement = Announcement::factory()->create([
        'type' => 'critical',
        'message' => 'Critical issue',
        'is_fixed' => false,
    ]);

    $response = $this->actingAs($superAdmin)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('Mark as Fixed');
});

test('announcement card does not show mark as fixed button to regular users', function () {
    $user = User::factory()->create(['role' => 'user', 'is_active' => true]);
    
    $announcement = Announcement::factory()->create([
        'type' => 'critical',
        'message' => 'Critical issue',
        'is_fixed' => false,
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('Critical issue');
    $response->assertDontSee('Mark as Fixed');
});

test('announcement card does not show mark as fixed button for resolved announcements', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    
    $announcement = Announcement::factory()->create([
        'type' => 'critical',
        'message' => 'Resolved issue',
        'is_fixed' => true,
        'fixed_at' => now()->subHour(),
    ]);

    $response = $this->actingAs($admin)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('Resolved issue');
    $response->assertDontSee('Mark as Fixed');
});

test('announcement card does not show mark as fixed button for non-critical announcements', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    
    $maintenanceAnnouncement = Announcement::factory()->create([
        'type' => 'maintenance',
        'message' => 'Maintenance work',
        'is_fixed' => false,
    ]);
    
    $infoAnnouncement = Announcement::factory()->create([
        'type' => 'info',
        'message' => 'Info message',
        'is_fixed' => false,
    ]);

    $response = $this->actingAs($admin)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('Maintenance work');
    $response->assertSee('Info message');
    $response->assertDontSee('Mark as Fixed');
});

test('announcement card includes Alpine.js interactive elements', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    
    $announcement = Announcement::factory()->create([
        'type' => 'critical',
        'message' => 'Critical issue',
        'is_fixed' => false,
    ]);

    $response = $this->actingAs($admin)->get('/dashboard');

    $response->assertStatus(200);
    // Verify Alpine.js directives are present
    $content = $response->getContent();
    expect($content)->toContain('x-data');
    expect($content)->toContain('x-show');
    expect($content)->toContain('@click');
});
