<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schulen', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('schultyp', ['realschule', 'gymnasium', 'sonstige'])->default('sonstige');
            $table->string('strasse')->nullable();
            $table->string('plz', 10)->nullable();
            $table->string('ort')->nullable();
            $table->string('telefon')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->text('notizen')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('schultyp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schulen');
    }
};
