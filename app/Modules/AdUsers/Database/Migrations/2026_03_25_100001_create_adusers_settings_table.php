<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adusers_settings', function (Blueprint $table) {
            $table->id();
            $table->string('server')->default('');
            $table->unsignedSmallInteger('port')->default(389);
            $table->string('base_dn')->default('');
            $table->string('bind_dn')->nullable();
            $table->text('bind_password')->nullable();
            $table->boolean('anonymous_bind')->default(false);
            $table->boolean('use_ssl')->default(false);
            $table->unsignedSmallInteger('sync_interval_hours')->default(24);
            $table->unsignedSmallInteger('max_inactive_days')->default(90);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adusers_settings');
    }
};
