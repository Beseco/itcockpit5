<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('onboarding_settings', function (Blueprint $table) {
            // Globale Standard-Vorgaben – Vorlagen erben diese, sofern sie das Feld leer lassen.
            $table->string('default_samaccountname_pattern')->nullable()->after('supervisor_mail_body');
            $table->string('default_upn_pattern')->nullable()->after('default_samaccountname_pattern');
            $table->string('default_profilpfad_pattern', 500)->nullable()->after('default_upn_pattern');
            $table->string('default_heimatverzeichnis_pattern', 500)->nullable()->after('default_profilpfad_pattern');
            $table->string('default_heimatverzeichnis_laufwerk', 3)->nullable()->after('default_heimatverzeichnis_pattern');
            $table->string('default_anmeldeskript')->nullable()->after('default_heimatverzeichnis_laufwerk');
            $table->json('default_laufwerke')->nullable()->after('default_anmeldeskript');
        });
    }

    public function down(): void
    {
        Schema::table('onboarding_settings', function (Blueprint $table) {
            $table->dropColumn([
                'default_samaccountname_pattern',
                'default_upn_pattern',
                'default_profilpfad_pattern',
                'default_heimatverzeichnis_pattern',
                'default_heimatverzeichnis_laufwerk',
                'default_anmeldeskript',
                'default_laufwerke',
            ]);
        });
    }
};
