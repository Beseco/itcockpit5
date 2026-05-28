<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dienstleistung_dienstleister', function (Blueprint $table) {
            $table->foreignId('dienstleistung_id')
                ->constrained('dienstleistungen')
                ->cascadeOnDelete();
            $table->foreignId('dienstleister_id')
                ->constrained('dienstleister')
                ->cascadeOnDelete();
            $table->integer('sort_order')->default(0);

            $table->unique(['dienstleistung_id', 'dienstleister_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dienstleistung_dienstleister');
    }
};
