<?php

use App\Models\User;
use App\Models\AuditLog;

beforeEach(function () {
    // Create test users
    $this->superAdmin = User::factory()->create([
        'role' => 'super-admin',
        'is_active' => true,
    ]);

    $this->admin = User::factory()->create([
        'role' => 'admin',
        'is_active' => true,
    ]);

    $this->user = User::factory()->create([
        'role' => 'user',
        'is_active' => true,
    ]);
});

// Index Tests
test('super-admin can view audit logs index page', function () {
    $this->actingAs($this->superAdmin)
        ->get(route('audit-logs.index'))
        ->assertStatus(200)
        ->assertViewIs('audit-logs.index');
});

test('admin cannot access audit logs index page', function () {
    $this->actingAs($this->admin)
        ->get(route('audit-logs.index'))
        ->assertStatus(403);
});

test('standard user cannot access audit logs index page', function () {
    $this->actingAs($this->user)
        ->get(route('audit-logs.index'))
        ->assertStatus(403);
});

test('audit logs index displays logs with pagination', function () {
    // Create some audit logs
    AuditLog::factory()->count(25)->create();

    $response = $this->actingAs($this->superAdmin)
        ->get(route('audit-logs.index'));

    $response->assertStatus(200)
        ->assertViewHas('logs');
});

test('audit logs can be filtered by module', function () {
    AuditLog::factory()->create(['module' => 'User']);
    AuditLog::factory()->create(['module' => 'Announcement']);
    AuditLog::factory()->create(['module' => 'Core']);

    $response = $this->actingAs($this->superAdmin)
        ->get(route('audit-logs.index', ['module' => 'User']));

    $response->assertStatus(200);
});

test('audit logs can be filtered by user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    AuditLog::factory()->create(['user_id' => $user1->id]);
    AuditLog::factory()->create(['user_id' => $user2->id]);

    $response = $this->actingAs($this->superAdmin)
        ->get(route('audit-logs.index', ['user_id' => $user1->id]));

    $response->assertStatus(200);
});

test('audit logs can be filtered by date range', function () {
    AuditLog::factory()->create(['created_at' => now()->subDays(5)]);
    AuditLog::factory()->create(['created_at' => now()->subDays(2)]);
    AuditLog::factory()->create(['created_at' => now()]);

    $response = $this->actingAs($this->superAdmin)
        ->get(route('audit-logs.index', [
            'date_from' => now()->subDays(3)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ]));

    $response->assertStatus(200);
});

// Show Tests
test('super-admin can view audit log details', function () {
    $log = AuditLog::factory()->create();

    $this->actingAs($this->superAdmin)
        ->get(route('audit-logs.show', $log))
        ->assertStatus(200)
        ->assertViewIs('audit-logs.show')
        ->assertViewHas('auditLog', $log);
});

test('admin cannot view audit log details', function () {
    $log = AuditLog::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('audit-logs.show', $log))
        ->assertStatus(403);
});

test('standard user cannot view audit log details', function () {
    $log = AuditLog::factory()->create();

    $this->actingAs($this->user)
        ->get(route('audit-logs.show', $log))
        ->assertStatus(403);
});

test('audit log show page displays all log information', function () {
    $log = AuditLog::factory()->create([
        'module' => 'User',
        'action' => 'User created',
        'payload' => ['user_id' => 1, 'user_email' => 'test@example.com'],
    ]);

    $response = $this->actingAs($this->superAdmin)
        ->get(route('audit-logs.show', $log));

    $response->assertStatus(200)
        ->assertSee($log->module)
        ->assertSee($log->action);
});

test('audit log index provides modules and users for filters', function () {
    AuditLog::factory()->create(['module' => 'User']);
    AuditLog::factory()->create(['module' => 'Announcement']);

    $response = $this->actingAs($this->superAdmin)
        ->get(route('audit-logs.index'));

    $response->assertStatus(200)
        ->assertViewHas('modules')
        ->assertViewHas('users');
});
