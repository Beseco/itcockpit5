<?php

use App\Models\User;
use App\Models\Announcement;
use App\Models\AuditLog;

beforeEach(function () {
    // Create an admin user for testing
    $this->admin = User::factory()->create([
        'role' => 'admin',
        'is_active' => true,
    ]);

    $this->superAdmin = User::factory()->create([
        'role' => 'super-admin',
        'is_active' => true,
    ]);
});

// Index Tests
test('admin can view announcements index page', function () {
    $this->actingAs($this->admin)
        ->get(route('announcements.index'))
        ->assertStatus(200)
        ->assertViewIs('announcements.index');
});

test('super-admin can view announcements index page', function () {
    $this->actingAs($this->superAdmin)
        ->get(route('announcements.index'))
        ->assertStatus(200)
        ->assertViewIs('announcements.index');
});

test('standard user cannot access announcements index page', function () {
    $user = User::factory()->create(['role' => 'user', 'is_active' => true]);

    $this->actingAs($user)
        ->get(route('announcements.index'))
        ->assertStatus(403);
});

// Create Tests
test('admin can view create announcement form', function () {
    $this->actingAs($this->admin)
        ->get(route('announcements.create'))
        ->assertStatus(200)
        ->assertViewIs('announcements.create');
});

test('admin can create a new announcement', function () {
    $announcementData = [
        'type' => 'info',
        'message' => 'This is a test announcement',
        'starts_at' => now()->format('Y-m-d H:i:s'),
        'ends_at' => now()->addDays(7)->format('Y-m-d H:i:s'),
    ];

    $response = $this->actingAs($this->admin)
        ->post(route('announcements.store'), $announcementData);

    $response->assertRedirect(route('announcements.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('announcements', [
        'type' => 'info',
        'message' => 'This is a test announcement',
    ]);
});

test('announcement creation logs audit entry', function () {
    $announcementData = [
        'type' => 'critical',
        'message' => 'Critical system alert',
    ];

    $this->actingAs($this->admin)
        ->post(route('announcements.store'), $announcementData);

    $this->assertDatabaseHas('audit_logs', [
        'module' => 'Announcement',
        'action' => 'Announcement created',
    ]);
});

test('announcement creation validates required fields', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('announcements.store'), []);

    $response->assertSessionHasErrors(['type', 'message']);
});

test('announcement creation validates type is valid', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('announcements.store'), [
            'type' => 'invalid-type',
            'message' => 'Test message',
        ]);

    $response->assertSessionHasErrors(['type']);
});

test('announcement creation validates ends_at is after starts_at', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('announcements.store'), [
            'type' => 'info',
            'message' => 'Test message',
            'starts_at' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'ends_at' => now()->format('Y-m-d H:i:s'),
        ]);

    $response->assertSessionHasErrors(['ends_at']);
});

test('announcement can be created without dates', function () {
    $announcementData = [
        'type' => 'maintenance',
        'message' => 'Scheduled maintenance',
    ];

    $response = $this->actingAs($this->admin)
        ->post(route('announcements.store'), $announcementData);

    $response->assertRedirect(route('announcements.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('announcements', [
        'type' => 'maintenance',
        'message' => 'Scheduled maintenance',
    ]);
});

// Update Tests
test('admin can view edit announcement form', function () {
    $announcement = Announcement::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('announcements.edit', $announcement))
        ->assertStatus(200)
        ->assertViewIs('announcements.edit');
});

test('admin can update announcement', function () {
    $announcement = Announcement::factory()->create([
        'type' => 'info',
        'message' => 'Old message',
    ]);

    $response = $this->actingAs($this->admin)
        ->put(route('announcements.update', $announcement), [
            'type' => 'critical',
            'message' => 'Updated message',
        ]);

    $response->assertRedirect(route('announcements.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('announcements', [
        'id' => $announcement->id,
        'type' => 'critical',
        'message' => 'Updated message',
    ]);
});

test('announcement update logs audit entry', function () {
    $announcement = Announcement::factory()->create();

    $this->actingAs($this->admin)
        ->put(route('announcements.update', $announcement), [
            'type' => 'maintenance',
            'message' => 'Updated message',
        ]);

    $this->assertDatabaseHas('audit_logs', [
        'module' => 'Announcement',
        'action' => 'Announcement updated',
    ]);
});

test('announcement update validates ends_at is after starts_at', function () {
    $announcement = Announcement::factory()->create();

    $response = $this->actingAs($this->admin)
        ->put(route('announcements.update', $announcement), [
            'type' => 'info',
            'message' => 'Test message',
            'starts_at' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'ends_at' => now()->format('Y-m-d H:i:s'),
        ]);

    $response->assertSessionHasErrors(['ends_at']);
});

// Delete Tests
test('admin can delete an announcement', function () {
    $announcement = Announcement::factory()->create();

    $response = $this->actingAs($this->admin)
        ->delete(route('announcements.destroy', $announcement));

    $response->assertRedirect(route('announcements.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('announcements', [
        'id' => $announcement->id,
    ]);
});

test('announcement deletion logs audit entry', function () {
    $announcement = Announcement::factory()->create(['message' => 'To be deleted']);

    $this->actingAs($this->admin)
        ->delete(route('announcements.destroy', $announcement));

    $this->assertDatabaseHas('audit_logs', [
        'module' => 'Announcement',
        'action' => 'Announcement deleted',
    ]);
});

// Mark as Fixed Tests
test('admin can mark critical announcement as fixed', function () {
    $announcement = Announcement::factory()->create([
        'type' => 'critical',
        'is_fixed' => false,
        'fixed_at' => null,
    ]);

    $response = $this->actingAs($this->admin)
        ->post(route('announcements.mark-as-fixed', $announcement));

    $response->assertRedirect()
        ->assertSessionHas('success');

    $announcement->refresh();
    expect($announcement->is_fixed)->toBeTrue();
    expect($announcement->fixed_at)->not->toBeNull();
});

test('mark as fixed logs audit entry', function () {
    $announcement = Announcement::factory()->create([
        'type' => 'critical',
        'is_fixed' => false,
    ]);

    $this->actingAs($this->admin)
        ->post(route('announcements.mark-as-fixed', $announcement));

    $this->assertDatabaseHas('audit_logs', [
        'module' => 'Announcement',
        'action' => 'Announcement marked as fixed',
    ]);
});

test('mark as fixed sets fixed_at timestamp', function () {
    $announcement = Announcement::factory()->create([
        'type' => 'critical',
        'is_fixed' => false,
        'fixed_at' => null,
    ]);

    $beforeTime = now()->subSecond();
    
    $this->actingAs($this->admin)
        ->post(route('announcements.mark-as-fixed', $announcement));

    $announcement->refresh();
    
    expect($announcement->fixed_at)->not->toBeNull();
    expect($announcement->fixed_at->isAfter($beforeTime))->toBeTrue();
});

// Show Tests
test('admin can view announcement details', function () {
    $announcement = Announcement::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('announcements.show', $announcement))
        ->assertStatus(200)
        ->assertViewIs('announcements.show')
        ->assertViewHas('announcement', $announcement);
});

// Authorization Tests
test('unauthenticated user cannot access announcement routes', function () {
    $announcement = Announcement::factory()->create();

    $this->get(route('announcements.index'))->assertRedirect(route('login'));
    $this->get(route('announcements.create'))->assertRedirect(route('login'));
    $this->post(route('announcements.store'), [])->assertRedirect(route('login'));
    $this->get(route('announcements.edit', $announcement))->assertRedirect(route('login'));
    $this->put(route('announcements.update', $announcement), [])->assertRedirect(route('login'));
    $this->delete(route('announcements.destroy', $announcement))->assertRedirect(route('login'));
    $this->post(route('announcements.mark-as-fixed', $announcement))->assertRedirect(route('login'));
});

test('standard user cannot create announcements', function () {
    $user = User::factory()->create(['role' => 'user', 'is_active' => true]);

    $response = $this->actingAs($user)
        ->post(route('announcements.store'), [
            'type' => 'info',
            'message' => 'Test message',
        ]);

    $response->assertStatus(403);
});

test('standard user cannot update announcements', function () {
    $user = User::factory()->create(['role' => 'user', 'is_active' => true]);
    $announcement = Announcement::factory()->create();

    $response = $this->actingAs($user)
        ->put(route('announcements.update', $announcement), [
            'type' => 'info',
            'message' => 'Updated message',
        ]);

    $response->assertStatus(403);
});

test('standard user cannot delete announcements', function () {
    $user = User::factory()->create(['role' => 'user', 'is_active' => true]);
    $announcement = Announcement::factory()->create();

    $response = $this->actingAs($user)
        ->delete(route('announcements.destroy', $announcement));

    $response->assertStatus(403);
});

test('standard user cannot mark announcements as fixed', function () {
    $user = User::factory()->create(['role' => 'user', 'is_active' => true]);
    $announcement = Announcement::factory()->create(['type' => 'critical']);

    $response = $this->actingAs($user)
        ->post(route('announcements.mark-as-fixed', $announcement));

    $response->assertStatus(403);
});
