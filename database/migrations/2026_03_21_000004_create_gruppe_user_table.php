<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('gruppe_user')) {
            Schema::create('gruppe_user', function (Blueprint $table) {
                $table->foreignId('gruppe_id')->constrained('gruppen')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->primary(['gruppe_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gruppe_user');
    }
};
