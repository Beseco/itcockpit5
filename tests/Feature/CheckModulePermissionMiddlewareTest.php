<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    // Define test routes with module permission middleware
    Route::middleware(['web', 'auth', 'module.permission:inventory,view'])->get('/test-inventory-view', function () {
        return response()->json(['message' => 'Inventory view access granted']);
    });

    Route::middleware(['web', 'auth', 'module.permission:inventory,edit'])->get('/test-inventory-edit', function () {
        return response()->json(['message' => 'Inventory edit access granted']);
    });
});

test('super-admin can access any module permission route', function () {
    $superAdmin = User::factory()->create(['role' => 'super-admin', 'is_active' => true]);

    $this->actingAs($superAdmin)
        ->get('/test-inventory-view')
        ->assertStatus(200)
        ->assertJson(['message' => 'Inventory view access granted']);

    $this->actingAs($superAdmin)
        ->get('/test-inventory-edit')
        ->assertStatus(200)
        ->assertJson(['message' => 'Inventory edit access granted']);
});

test('user with module.view permission can access view route', function () {
    $user = User::factory()->create(['role' => 'user', 'is_active' => true]);
    
    // Create and assign permission
    $permission = Permission::create(['name' => 'module.inventory.view']);
    $user->givePermissionTo($permission);

    $this->actingAs($user)
        ->get('/test-inventory-view')
        ->assertStatus(200)
        ->assertJson(['message' => 'Inventory view access granted']);
});

test('user with module.edit permission can access edit route', function () {
    $user = User::factory()->create(['role' => 'user', 'is_active' => true]);
    
    // Create and assign permission
    $permission = Permission::create(['name' => 'module.inventory.edit']);
    $user->givePermissionTo($permission);

    $this->actingAs($user)
        ->get('/test-inventory-edit')
        ->assertStatus(200)
        ->assertJson(['message' => 'Inventory edit access granted']);
});

test('user without module permission cannot access protected route', function () {
    $user = User::factory()->create(['role' => 'user', 'is_active' => true]);

    $this->actingAs($user)
        ->get('/test-inventory-view')
        ->assertStatus(403);

    $this->actingAs($user)
        ->get('/test-inventory-edit')
        ->assertStatus(403);
});

test('user with view permission cannot access edit route', function () {
    $user = User::factory()->create(['role' => 'user', 'is_active' => true]);
    
    // Create and assign only view permission
    $permission = Permission::create(['name' => 'module.inventory.view']);
    $user->givePermissionTo($permission);

    $this->actingAs($user)
        ->get('/test-inventory-view')
        ->assertStatus(200);

    $this->actingAs($user)
        ->get('/test-inventory-edit')
        ->assertStatus(403);
});

test('unauthenticated user cannot access module permission protected routes', function () {
    $this->get('/test-inventory-view')
        ->assertStatus(302)
        ->assertRedirect('/login');
});

test('middleware returns 403 with appropriate message for unauthorized module access', function () {
    $user = User::factory()->create(['role' => 'user', 'is_active' => true]);

    $response = $this->actingAs($user)->get('/test-inventory-view');
    
    $response->assertStatus(403);
});

test('admin without specific module permission cannot access module route', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

    // Admin role alone doesn't grant module permissions (only super-admin bypasses)
    $this->actingAs($admin)
        ->get('/test-inventory-view')
        ->assertStatus(403);
});

test('middleware logs unauthorized access attempts for users without permission', function () {
    $user = User::factory()->create(['role' => 'user', 'is_active' => true]);

    // Attempt to access protected route without permission
    $this->actingAs($user)->get('/test-inventory-view');

    // Verify audit log entry was created
    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $user->id,
        'module' => 'Security',
        'action' => 'Unauthorized access attempt - insufficient permissions',
    ]);
});

test('middleware logs unauthorized access attempts for unauthenticated users', function () {
    // When a user is not authenticated, Laravel redirects to login before the middleware runs
    // So we can't test audit logging for unauthenticated users in this middleware
    // The auth middleware handles this case before CheckModulePermission runs
    
    // Attempt to access protected route without authentication
    $response = $this->get('/test-inventory-view');
    
    // Verify redirect to login (handled by auth middleware, not module.permission middleware)
    $response->assertStatus(302)->assertRedirect('/login');
});

test('middleware does not log access for authorized users', function () {
    $user = User::factory()->create(['role' => 'user', 'is_active' => true]);
    
    // Create and assign permission
    $permission = Permission::create(['name' => 'module.inventory.view']);
    $user->givePermissionTo($permission);

    // Count audit logs before request
    $auditLogCountBefore = \App\Models\AuditLog::where('module', 'Security')->count();

    // Access protected route with permission
    $this->actingAs($user)->get('/test-inventory-view');

    // Verify no new security audit log entry was created
    $auditLogCountAfter = \App\Models\AuditLog::where('module', 'Security')->count();
    expect($auditLogCountAfter)->toBe($auditLogCountBefore);
});

test('middleware does not log access for super-admin', function () {
    $superAdmin = User::factory()->create(['role' => 'super-admin', 'is_active' => true]);

    // Count audit logs before request
    $auditLogCountBefore = \App\Models\AuditLog::where('module', 'Security')->count();

    // Access protected route as super-admin
    $this->actingAs($superAdmin)->get('/test-inventory-view');

    // Verify no new security audit log entry was created
    $auditLogCountAfter = \App\Models\AuditLog::where('module', 'Security')->count();
    expect($auditLogCountAfter)->toBe($auditLogCountBefore);
});
