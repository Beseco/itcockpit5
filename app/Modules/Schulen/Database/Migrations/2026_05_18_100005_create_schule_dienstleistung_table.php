<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schule_dienstleistung', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schule_id')->constrained('schulen')->cascadeOnDelete();
            $table->foreignId('dienstleistung_id')->constrained('dienstleistungen')->cascadeOnDelete();
            $table->enum('status', ['aktiv', 'geplant', 'nicht_vorhanden', 'nicht_gewuenscht', 'nicht_moeglich'])
                  ->default('nicht_vorhanden');
            $table->decimal('stunden_override', 8, 2)->nullable();
            $table->string('notizen')->nullable();
            $table->timestamps();

            $table->unique(['schule_id', 'dienstleistung_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schule_dienstleistung');
    }
};
