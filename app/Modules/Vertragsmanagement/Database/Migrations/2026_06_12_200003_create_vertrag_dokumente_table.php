<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vertrag_dokumente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vertrag_id')->constrained('vertraege')->cascadeOnDelete();
            $table->string('dateiname');
            $table->string('pfad');
            $table->unsignedBigInteger('groesse')->default(0);
            $table->string('mime_type')->nullable();
            $table->foreignId('hochgeladen_von')->nullable()
                  ->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vertrag_dokumente');
    }
};
