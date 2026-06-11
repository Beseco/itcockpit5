<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dienstleistungen', function (Blueprint $table) {
            $table->boolean('betriebsvoraussetzung')->default(false)->after('is_active');
            $table->index('betriebsvoraussetzung');
        });

        // Verknüpfung: welche Betriebsvoraussetzungen werden für eine
        // Dienstleistung benötigt (rein dokumentarisch, Self-Relation).
        Schema::create('dienstleistung_voraussetzung', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dienstleistung_id')->constrained('dienstleistungen')->cascadeOnDelete();
            $table->foreignId('voraussetzung_id')->constrained('dienstleistungen')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['dienstleistung_id', 'voraussetzung_id']);
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
