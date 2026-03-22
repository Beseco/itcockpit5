<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create super-admin user
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $superAdmin->assignRole('Superadministrator');

        // Create sample admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin.user@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $admin->assignRole('Admin');

        // Create sample standard user (viewer)
        $user = User::create([
            'name' => 'Standard User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $user->assignRole('Viewer');
    }
}
