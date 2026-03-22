<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;

test('User model has HasRoles trait', function () {
    $user = User::factory()->create([
        'role' => 'user',
        'is_active' => true,
    ]);

    expect(method_exists($user, 'hasPermissionTo'))->toBeTrue();
    expect(method_exists($user, 'givePermissionTo'))->toBeTrue();
    expect(method_exists($user, 'revokePermissionTo'))->toBeTrue();
});

test('User can be assigned permissions', function () {
    $user = User::factory()->create([
        'role' => 'user',
        'is_active' => true,
    ]);

    $permission = Permission::create([
        'name' => 'module.test.view',
        'guard_name' => 'web',
    ]);

    $user->givePermissionTo($permission);

    expect($user->hasPermissionTo('module.test.view'))->toBeTrue();
});

test('hasModulePermission method works with Spatie permissions', function () {
    $user = User::factory()->create([
        'role' => 'user',
        'is_active' => true,
    ]);

    $viewPermission = Permission::create([
        'name' => 'module.inventory.view',
        'guard_name' => 'web',
    ]);

    $editPermission = Permission::create([
        'name' => 'module.inventory.edit',
        'guard_name' => 'web',
    ]);

    $user->givePermissionTo($viewPermission);

    expect($user->hasModulePermission('inventory', 'view'))->toBeTrue();
    expect($user->hasModulePermission('inventory', 'edit'))->toBeFalse();
});

test('super admin bypasses permission checks', function () {
    $superAdmin = User::factory()->create([
        'role' => 'super-admin',
        'is_active' => true,
    ]);

    // Super admin should have access without explicit permissions
    expect($superAdmin->hasModulePermission('any-module', 'view'))->toBeTrue();
    expect($superAdmin->hasModulePermission('any-module', 'edit'))->toBeTrue();
});
