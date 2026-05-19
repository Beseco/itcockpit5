<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wid_advisories', function (Blueprint $table) {
            $table->dateTime('published_original')->nullable()->after('published')
                ->comment('Originales Erstveröffentlichungsdatum aus dem Detail-Endpoint');
        });
    }

    public function down(): void
    {
        Schema::table('wid_advisories', function (Blueprint $table) {
            $table->dropColumn('published_original');
        });
    }
};
