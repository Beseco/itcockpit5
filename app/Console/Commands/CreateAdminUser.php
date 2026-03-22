<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

class CreateAdminUser extends Command
{
    protected $signature = 'user:create-admin 
                            {--email= : Admin email address}
                            {--password= : Admin password}
                            {--name= : Admin name}';
    
    protected $description = 'Create an admin user with all permissions';

    public function handle(): int
    {
        $email = $this->option('email') ?: $this->ask('Email address', 'admin@example.com');
        $name = $this->option('name') ?: $this->ask('Name', 'Admin');
        $password = $this->option('password') ?: $this->secret('Password (leave empty for "password")') ?: 'password';

        // Check if user already exists
        $user = User::where('email', $email)->first();
        
        if ($user) {
            $this->warn("User with email {$email} already exists!");
            
            if ($this->confirm('Do you want to reset the password and permissions for this user?')) {
                $user->password = Hash::make($password);
                $user->role = 'super-admin';
                $user->is_active = true;
                $user->save();
                
                $this->info("Password reset and role updated to super-admin for {$email}");
            } else {
                return 1;
            }
        } else {
            // Create new user
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'role' => 'super-admin',
                'is_active' => true,
            ]);

            $this->info("Super Admin user created successfully!");
        }

        // Create all necessary permissions if they don't exist
        $this->info("Setting up permissions...");
        
        $permissions = [
            'module.network.view',
            'module.network.edit',
            'module.example.view',
            'module.example.edit',
        ];

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
            
            if (!$user->hasPermissionTo($permission)) {
                $user->givePermissionTo($permission);
                $this->line("  ✓ Granted: {$permissionName}");
            }
        }

        $this->info("All permissions granted!");
        $this->displayCredentials($email, $password);

        return 0;
    }

    private function displayCredentials(string $email, string $password): void
    {
        $this->newLine();
        $this->info('╔════════════════════════════════════════════════════════════════╗');
        $this->info('║                    Login Credentials                           ║');
        $this->info('╚════════════════════════════════════════════════════════════════╝');
        $this->line("  Email:    {$email}");
        $this->line("  Password: {$password}");
        $this->line("  Role:     Super Admin (full access to all modules)");
        $this->newLine();
        $this->warn('⚠️  Please change the password after first login!');
    }
}
