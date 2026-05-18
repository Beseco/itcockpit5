<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dienstleistungen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dienst_kategorie_id')->nullable()->constrained('dienst_kategorien')->nullOnDelete();
            $table->string('name');
            $table->text('beschreibung')->nullable();
            $table->string('dokumentation_url')->nullable();
            $table->enum('stunden_modus', ['jahresstunden', 'wochenstunden'])->default('jahresstunden');
            $table->decimal('stunden_wert', 8, 2)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('dienst_kategorie_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dienstleistungen');
    }
};
