<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('stellen_arbeitsvorgaenge')) {
            Schema::create('stellen_arbeitsvorgaenge', function (Blueprint $table) {
                $table->id();
                $table->foreignId('stelle_id')->constrained('stellen')->cascadeOnDelete();
                $table->string('betreff');
                $table->text('beschreibung')->nullable();
                $table->unsignedTinyInteger('anteil')->default(0);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stellen_arbeitsvorgaenge');
    }
};
