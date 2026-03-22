<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Removes the legacy `role` enum column from the users table.
     * Roles are now managed via spatie/laravel-permission.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the index first (required for SQLite compatibility)
            $table->dropIndex('users_role_index');
            $table->dropColumn('role');
        });
    }

    /**
     * Reverse the migrations.
     * Re-adds the `role` enum column with its original definition.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['super-admin', 'admin', 'user'])->default('user')->after('id');
        });
    }
};
