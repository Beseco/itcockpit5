<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_vorlagen', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('beschreibung')->nullable();
            $table->foreignId('abteilung_id')->nullable()->constrained('abteilungen')->nullOnDelete();
            $table->string('samaccountname_pattern')->default('%nachname%%F%');
            $table->string('upn_pattern')->default('%vorname%.%nachname%@kreis-fs.de');
            $table->string('rufnummer_praefix')->nullable();
            $table->string('fax_praefix')->nullable();
            $table->string('strasse')->nullable();
            $table->string('plz')->nullable();
            $table->string('ort')->nullable();
            $table->string('profilpfad_pattern')->nullable();
            $table->string('heimatverzeichnis_pattern')->nullable();
            $table->string('anmeldeskript')->nullable();
            $table->json('laufwerke')->nullable();
            $table->string('abteilung_ad')->nullable();
            $table->string('firma')->nullable();
            $table->foreignId('vorgesetzter_ad_user_id')->nullable()->constrained('adusers')->nullOnDelete();
            $table->text('welcome_mail_override')->nullable();
            $table->text('supervisor_mail_override')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_vorlagen');
    }
};
