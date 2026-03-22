<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('aufgaben_zuweisungen')) {
            Schema::create('aufgaben_zuweisungen', function (Blueprint $table) {
                $table->id();
                $table->foreignId('aufgabe_id')->constrained('aufgaben')->cascadeOnDelete();
                $table->foreignId('gruppe_id')->nullable()->constrained('gruppen')->nullOnDelete();
                $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('stellvertreter_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('aufgaben_zuweisungen');
    }
};
