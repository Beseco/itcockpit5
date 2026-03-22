<?php

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Hash;

test('UserSeeder creates super-admin user with correct attributes', function () {
    $this->seed(UserSeeder::class);

    $superAdmin = User::where('email', 'admin@example.com')->first();

    expect($superAdmin)->not->toBeNull()
        ->and($superAdmin->name)->toBe('Super Admin')
        ->and($superAdmin->role)->toBe('super-admin')
        ->and($superAdmin->is_active)->toBeTrue()
        ->and($superAdmin->password)->not->toBe('password') // Ensure it's hashed
        ->and(Hash::check('password', $superAdmin->password))->toBeTrue(); // Verify hash is correct
});

test('UserSeeder creates admin user with correct attributes', function () {
    $this->seed(UserSeeder::class);

    $admin = User::where('email', 'admin.user@example.com')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->name)->toBe('Admin User')
        ->and($admin->role)->toBe('admin')
        ->and($admin->is_active)->toBeTrue()
        ->and($admin->password)->not->toBe('password')
        ->and(Hash::check('password', $admin->password))->toBeTrue();
});

test('UserSeeder creates standard user with correct attributes', function () {
    $this->seed(UserSeeder::class);

    $user = User::where('email', 'user@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Standard User')
        ->and($user->role)->toBe('user')
        ->and($user->is_active)->toBeTrue()
        ->and($user->password)->not->toBe('password')
        ->and(Hash::check('password', $user->password))->toBeTrue();
});

test('UserSeeder creates exactly three users', function () {
    $this->seed(UserSeeder::class);

    $userCount = User::count();

    expect($userCount)->toBe(3);
});

test('UserSeeder hashes all passwords using bcrypt', function () {
    $this->seed(UserSeeder::class);

    $users = User::all();

    foreach ($users as $user) {
        // Bcrypt hashes start with $2y$ or $2a$
        expect($user->password)->toMatch('/^\$2[ay]\$/');
    }
});
