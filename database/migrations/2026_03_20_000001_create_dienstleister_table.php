<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('dienstleister')) {
            Schema::create('dienstleister', function (Blueprint $table) {
                $table->id();
                $table->string('firmenname');
                $table->string('ansprechpartner')->nullable();
                $table->string('telefon')->nullable();
                $table->string('email')->nullable();
                $table->string('webseite')->nullable();
                $table->text('bemerkungen')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dienstleister');
    }
};
