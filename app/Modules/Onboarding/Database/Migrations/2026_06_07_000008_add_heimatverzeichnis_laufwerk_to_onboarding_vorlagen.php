<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onboarding_vorlagen', function (Blueprint $table) {
            $table->string('heimatverzeichnis_laufwerk', 3)->nullable()->after('heimatverzeichnis_pattern');
        });
    }

    public function down(): void
    {
        Schema::table('onboarding_vorlagen', function (Blueprint $table) {
            $table->dropColumn('heimatverzeichnis_laufwerk');
        });
    }
};
