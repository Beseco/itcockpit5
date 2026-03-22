<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('permission_scopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
            $table->string('scope_type', 100);
            $table->unsignedBigInteger('scope_id');
            $table->timestamps();

            // Unique constraint to prevent duplicate scope assignments
            $table->unique(['user_id', 'permission_id', 'scope_type', 'scope_id'], 'unique_permission_scope');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_scopes');
    }
};
