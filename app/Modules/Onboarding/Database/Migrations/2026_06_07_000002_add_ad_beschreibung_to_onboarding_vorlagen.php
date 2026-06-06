<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onboarding_vorlagen', function (Blueprint $table) {
            $table->string('ad_beschreibung', 1024)->nullable()->after('abteilung_ad');
        });
    }

    public function down(): void
    {
        Schema::table('onboarding_vorlagen', function (Blueprint $table) {
            $table->dropColumn('ad_beschreibung');
        });
    }
};
