<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adusers', function (Blueprint $table) {
            $table->id();
            $table->string('samaccountname')->unique();
            $table->string('vorname')->nullable();
            $table->string('nachname')->nullable();
            $table->string('anzeigename')->nullable();
            $table->string('email')->nullable();
            $table->string('organisation')->nullable();
            $table->string('abteilung')->nullable();
            $table->string('telefon')->nullable();
            $table->string('distinguished_name')->nullable();
            $table->boolean('ad_vorhanden')->default(true);
            $table->boolean('ad_aktiv')->default(true);
            $table->timestamp('letzter_import_at')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->index('ad_vorhanden');
            $table->index('ad_aktiv');
            $table->index('letzter_import_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adusers');
    }
};
