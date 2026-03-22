<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('stellen')) {
            Schema::create('stellen', function (Blueprint $table) {
                $table->id();
                $table->string('bezeichnung');
                $table->foreignId('gruppe_id')->nullable()->constrained('gruppen')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('tvod_bewertung')->nullable();
                $table->decimal('stunden', 4, 1)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stellen');
    }
};
