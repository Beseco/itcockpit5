<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Muster-Felder dürfen jetzt leer sein → leer bedeutet "globale Vorgabe erben".
        Schema::table('onboarding_vorlagen', function (Blueprint $table) {
            $table->string('samaccountname_pattern')->nullable()->default(null)->change();
            $table->string('upn_pattern')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('onboarding_vorlagen', function (Blueprint $table) {
            $table->string('samaccountname_pattern')->default('%nachname%%F%')->change();
            $table->string('upn_pattern')->default('%vorname%.%nachname%@kreis-fs.de')->change();
        });
    }
};
