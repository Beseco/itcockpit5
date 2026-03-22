<?php

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Hash;

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
test('admin can view users index page', function () {
    $this->actingAs($this->admin)
        ->get(route('users.index'))
        ->assertStatus(200)
        ->assertViewIs('users.index');
});

test('super-admin can view users index page', function () {
    $this->actingAs($this->superAdmin)
        ->get(route('users.index'))
        ->assertStatus(200)
        ->assertViewIs('users.index');
});

test('standard user cannot access users index page', function () {
    $user = User::factory()->create(['role' => 'user', 'is_active' => true]);

    $this->actingAs($user)
        ->get(route('users.index'))
        ->assertStatus(403);
});

test('users index can be filtered by role', function () {
    User::factory()->create(['role' => 'user', 'is_active' => true]);
    User::factory()->create(['role' => 'admin', 'is_active' => true]);

    $response = $this->actingAs($this->admin)
        ->get(route('users.index', ['role' => 'user']));

    $response->assertStatus(200);
});

test('users index can be filtered by active status', function () {
    User::factory()->create(['is_active' => true]);
    User::factory()->create(['is_active' => false]);

    $response = $this->actingAs($this->admin)
        ->get(route('users.index', ['active' => '1']));

    $response->assertStatus(200);
});

// Create Tests
test('admin can view create user form', function () {
    $this->actingAs($this->admin)
        ->get(route('users.create'))
        ->assertStatus(200)
        ->assertViewIs('users.create');
});

test('admin can create a new user', function () {
    $userData = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'user',
        'is_active' => true,
    ];

    $response = $this->actingAs($this->admin)
        ->post(route('users.store'), $userData);

    $response->assertRedirect(route('users.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('users', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'role' => 'user',
        'is_active' => true,
    ]);

    // Verify password is hashed
    $user = User::where('email', 'test@example.com')->first();
    expect(Hash::check('password123', $user->password))->toBeTrue();
});

test('user creation logs audit entry', function () {
    $userData = [
        'name' => 'Test User',
        'email' => 'audit@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'user',
        'is_active' => true,
    ];

    $this->actingAs($this->admin)
        ->post(route('users.store'), $userData);

    $this->assertDatabaseHas('audit_logs', [
        'module' => 'User',
        'action' => 'User created',
    ]);
});

test('user creation validates required fields', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('users.store'), []);

    $response->assertSessionHasErrors(['name', 'email', 'password', 'role']);
});

test('user creation validates unique email', function () {
    $existingUser = User::factory()->create(['email' => 'existing@example.com']);

    $response = $this->actingAs($this->admin)
        ->post(route('users.store'), [
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user',
        ]);

    $response->assertSessionHasErrors(['email']);
});

test('user creation validates password confirmation', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('users.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different',
            'role' => 'user',
        ]);

    $response->assertSessionHasErrors(['password']);
});

test('user creation validates role is valid', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('users.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'invalid-role',
        ]);

    $response->assertSessionHasErrors(['role']);
});

// Update Tests
test('admin can view edit user form', function () {
    $user = User::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('users.edit', $user))
        ->assertStatus(200)
        ->assertViewIs('users.edit');
});

test('admin can update user information', function () {
    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
        'role' => 'user',
    ]);

    $response = $this->actingAs($this->admin)
        ->put(route('users.update', $user), [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'role' => 'admin',
            'is_active' => true,
        ]);

    $response->assertRedirect(route('users.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'New Name',
        'email' => 'new@example.com',
        'role' => 'admin',
    ]);
});

test('user update logs audit entry with changes', function () {
    $user = User::factory()->create([
        'name' => 'Old Name',
        'role' => 'user',
    ]);

    $this->actingAs($this->admin)
        ->put(route('users.update', $user), [
            'name' => 'New Name',
            'email' => $user->email,
            'role' => 'admin',
            'is_active' => $user->is_active ? 1 : 0,
        ]);

    $auditLog = AuditLog::where('module', 'User')
        ->where('action', 'User updated')
        ->latest()
        ->first();

    expect($auditLog)->not->toBeNull();
    expect($auditLog->payload)->toHaveKey('changes');
});

test('admin can update user password', function () {
    $user = User::factory()->create(['password' => Hash::make('oldpassword')]);
    $oldPasswordHash = $user->getAuthPassword();

    $response = $this->actingAs($this->admin)
        ->put(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'role' => $user->role,
            'is_active' => $user->is_active ? 1 : 0,
        ]);

    $response->assertRedirect(route('users.index'));

    $user->refresh();
    
    // Check that the password was actually changed
    expect(Hash::check('oldpassword', $user->password))->toBeFalse();
    expect(Hash::check('newpassword123', $user->password))->toBeTrue();
});

test('user update validates unique email except current user', function () {
    $user1 = User::factory()->create(['email' => 'user1@example.com']);
    $user2 = User::factory()->create(['email' => 'user2@example.com']);

    // Should fail - email belongs to another user
    $response = $this->actingAs($this->admin)
        ->put(route('users.update', $user1), [
            'name' => $user1->name,
            'email' => 'user2@example.com',
            'role' => $user1->role,
            'is_active' => $user1->is_active ? 1 : 0,
        ]);

    $response->assertSessionHasErrors(['email']);

    // Refresh user to get latest data
    $user1->refresh();

    // Should succeed - same email as current user
    $response = $this->actingAs($this->admin)
        ->from(route('users.edit', $user1))
        ->put(route('users.update', $user1), [
            'name' => 'Updated Name',
            'email' => 'user1@example.com',
            'role' => $user1->role,
            'is_active' => $user1->is_active ? 1 : 0,
        ]);

    $response->assertRedirect(route('users.index'));
});

// Delete Tests
test('admin can delete a user', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($this->admin)
        ->delete(route('users.destroy', $user));

    $response->assertRedirect(route('users.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('users', [
        'id' => $user->id,
    ]);
});

test('user deletion logs audit entry', function () {
    $user = User::factory()->create(['email' => 'delete@example.com']);

    $this->actingAs($this->admin)
        ->delete(route('users.destroy', $user));

    $this->assertDatabaseHas('audit_logs', [
        'module' => 'User',
        'action' => 'User deleted',
    ]);
});

test('user cannot delete themselves', function () {
    $response = $this->actingAs($this->admin)
        ->delete(route('users.destroy', $this->admin));

    $response->assertRedirect(route('users.index'))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('users', [
        'id' => $this->admin->id,
    ]);
});

// Toggle Active Tests
test('admin can toggle user active status', function () {
    $user = User::factory()->create(['is_active' => true]);

    $response = $this->actingAs($this->admin)
        ->post(route('users.toggle-active', $user));

    $response->assertRedirect(route('users.index'))
        ->assertSessionHas('success');

    $user->refresh();
    expect($user->is_active)->toBeFalse();
});

test('toggle active logs audit entry', function () {
    $user = User::factory()->create(['is_active' => true]);

    $this->actingAs($this->admin)
        ->post(route('users.toggle-active', $user));

    $this->assertDatabaseHas('audit_logs', [
        'module' => 'User',
        'action' => 'User deactivated',
    ]);

    // Create a new user for activation test
    $inactiveUser = User::factory()->create(['is_active' => false]);

    $this->actingAs($this->admin)
        ->post(route('users.toggle-active', $inactiveUser));

    $this->assertDatabaseHas('audit_logs', [
        'module' => 'User',
        'action' => 'User activated',
    ]);
});

test('toggle active can activate inactive user', function () {
    $user = User::factory()->create(['is_active' => false]);

    $this->actingAs($this->admin)
        ->post(route('users.toggle-active', $user));

    $user->refresh();
    expect($user->is_active)->toBeTrue();
});

// Show Tests
test('admin can view user details', function () {
    $user = User::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('users.show', $user))
        ->assertStatus(200)
        ->assertViewIs('users.show')
        ->assertViewHas('user', $user);
});

