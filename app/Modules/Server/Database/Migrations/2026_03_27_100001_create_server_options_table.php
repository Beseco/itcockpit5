<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_options', function (Blueprint $table) {
            $table->id();
            $table->string('category', 30);
            $table->string('label', 100);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['category', 'sort_order']);
            $table->unique(['category', 'label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_options');
    }
};
