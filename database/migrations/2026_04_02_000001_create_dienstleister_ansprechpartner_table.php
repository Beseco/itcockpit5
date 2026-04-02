<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dienstleister_ansprechpartner', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dienstleister_id')->constrained('dienstleister')->cascadeOnDelete();
            $table->string('anrede', 20)->nullable();
            $table->string('vorname', 100)->nullable();
            $table->string('nachname', 100);
            $table->string('funktion', 100)->nullable();
            $table->string('telefon', 50)->nullable();
            $table->string('handy', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->text('notiz')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dienstleister_ansprechpartner');
    }
};
