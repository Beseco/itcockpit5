<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schul_typen', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('farbe_klassen', 100)->default('bg-gray-100 text-gray-800');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schul_typen');
    }
};
