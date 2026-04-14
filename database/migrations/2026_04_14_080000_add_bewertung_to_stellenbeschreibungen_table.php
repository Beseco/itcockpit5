<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stellenbeschreibungen', function (Blueprint $table) {
            $table->date('bewertet_am')->nullable()->after('bezeichnung');
            $table->string('bewertungsergebnis')->nullable()->after('bewertet_am');
        });
    }

    public function down(): void
    {
        Schema::table('stellenbeschreibungen', function (Blueprint $table) {
            $table->dropColumn(['bewertet_am', 'bewertungsergebnis']);
        });
    }
};
