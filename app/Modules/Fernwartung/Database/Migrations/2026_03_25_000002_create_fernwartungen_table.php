<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fernwartungen', function (Blueprint $table) {
            $table->id();
            $table->string('externer_name');
            $table->string('firma');
            $table->foreignId('beobachter_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('beobachter_name')->nullable();
            $table->string('ziel');
            $table->string('tool');
            $table->date('datum');
            $table->string('beginn', 5);
            $table->string('ende', 5)->nullable();
            $table->text('grund');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fernwartungen');
    }
};
