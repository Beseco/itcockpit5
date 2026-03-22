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
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['info', 'maintenance', 'critical']);
            $table->text('message');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_fixed')->default(false);
            $table->timestamp('fixed_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('type');
            $table->index('is_fixed');
            $table->index('starts_at');
            $table->index('ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
