<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wid_advisories', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 100)->unique();
            $table->string('name', 100);
            $table->string('title', 500)->nullable();
            $table->string('classification', 20)->default('keine');
            $table->decimal('temporal_score', 4, 1)->nullable();
            $table->dateTime('published')->nullable();
            $table->string('status', 50)->nullable();
            $table->boolean('no_patch')->default(false);
            $table->boolean('exploit')->default(false);
            $table->dateTime('fetched_at');
            $table->timestamps();

            $table->index('classification');
            $table->index('published');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wid_advisories');
    }
};
