<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schulen_kontakte', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schule_id')->constrained('schulen')->cascadeOnDelete();
            $table->enum('rolle', ['rektor', 'konrektor', 'sekretaerin', 'systembetreuer', 'sonstige'])->default('sonstige');
            $table->string('vorname');
            $table->string('nachname');
            $table->string('telefon')->nullable();
            $table->string('email')->nullable();
            $table->string('notizen')->nullable();
            $table->timestamps();

            $table->index('schule_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schulen_kontakte');
    }
};
