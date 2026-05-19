<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wid_settings', function (Blueprint $table) {
            $table->id();
            $table->text('api_key')->nullable();
            $table->string('api_url', 500)->default('https://wid.lsi.bybn.de/content');
            $table->boolean('enabled')->default(false);
            $table->integer('max_items')->default(20);
            $table->string('min_classification', 20)->default('keine');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wid_settings');
    }
};
