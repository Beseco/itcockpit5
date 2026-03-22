<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Migrates existing users from the old enum role system to Spatie roles.
     */
    public function up(): void
    {
        // Get all users with their old roles
        $users = DB::table('users')->get();

        foreach ($users as $user) {
            // Check if user already has roles assigned
            $existingRoles = DB::table('model_has_roles')
                ->where('model_type', 'App\\Models\\User')
                ->where('model_id', $user->id)
                ->count();

            // Skip if user already has roles
            if ($existingRoles > 0) {
                continue;
            }

            // Map old enum roles to new Spatie roles
            $roleName = match ($user->role) {
                'super-admin', 'admin' => 'Admin',
                'user' => 'Viewer',
                default => 'Viewer',
            };

            // Check if role exists
            $role = Role::where('name', $roleName)->first();
            
            if ($role) {
                // Assign role to user via model_has_roles table
                DB::table('model_has_roles')->insert([
                    'role_id' => $role->id,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $user->id,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove all user role assignments
        DB::table('model_has_roles')
            ->where('model_type', 'App\\Models\\User')
            ->delete();
    }
};
