<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dienstleistung_zustaendigkeiten')) {
            return;
        }

        Schema::create('dienstleistung_zustaendigkeiten', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dienstleistung_id')
                ->constrained('dienstleistungen')
                ->cascadeOnDelete();
            $table->string('aufgabe', 200);
            $table->string('lra_it', 200)->nullable();
            $table->string('schule_sb', 200)->nullable();
            $table->string('externer_dl', 200)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('dienstleistung_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dienstleistung_zustaendigkeiten');
    }
};
