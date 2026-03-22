<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('gruppe_role')) {
            Schema::create('gruppe_role', function (Blueprint $table) {
                $table->foreignId('gruppe_id')->constrained('gruppen')->cascadeOnDelete();
                $table->unsignedBigInteger('role_id');
                $table->primary(['gruppe_id', 'role_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gruppe_role');
    }
};
