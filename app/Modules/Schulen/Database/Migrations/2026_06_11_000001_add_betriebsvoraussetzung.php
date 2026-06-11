<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('dienstleistungen', 'betriebsvoraussetzung')) {
            Schema::table('dienstleistungen', function (Blueprint $table) {
                $table->boolean('betriebsvoraussetzung')->default(false)->after('is_active');
                $table->index('betriebsvoraussetzung');
            });
        }

        // Verknüpfung: welche Betriebsvoraussetzungen werden für eine
        // Dienstleistung benötigt (rein dokumentarisch, Self-Relation).
        // dropIfExists, falls ein vorheriger Lauf die Tabelle ohne Unique-Index
        // angelegt hat (Tabelle ist neu/leer → Drop unkritisch).
        Schema::dropIfExists('dienstleistung_voraussetzung');
        Schema::create('dienstleistung_voraussetzung', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dienstleistung_id')->constrained('dienstleistungen')->cascadeOnDelete();
            $table->foreignId('voraussetzung_id')->constrained('dienstleistungen')->cascadeOnDelete();
            $table->timestamps();

            // Kurzer Indexname (MySQL-Limit 64 Zeichen).
            $table->unique(['dienstleistung_id', 'voraussetzung_id'], 'dv_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dienstleistung_voraussetzung');

        Schema::table('dienstleistungen', function (Blueprint $table) {
            $table->dropIndex(['betriebsvoraussetzung']);
            $table->dropColumn('betriebsvoraussetzung');
        });
    }
};
