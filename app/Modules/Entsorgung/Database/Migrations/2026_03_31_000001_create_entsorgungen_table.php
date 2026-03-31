<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entsorgungen', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('modell');
            $table->string('hersteller');
            $table->string('typ')->nullable();
            $table->string('inventar');
            $table->string('entsorger');
            $table->string('user')->nullable();
            $table->boolean('grundschutz')->default(true);
            $table->text('grundschutzgrund')->nullable();
            $table->date('datum');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entsorgungen');
    }
};
