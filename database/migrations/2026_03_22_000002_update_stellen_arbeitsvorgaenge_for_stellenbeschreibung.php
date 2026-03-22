<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stellen_arbeitsvorgaenge', function (Blueprint $table) {
            $table->unsignedBigInteger('stellenbeschreibung_id')->nullable()->after('id');
            $table->foreign('stellenbeschreibung_id')
                  ->references('id')->on('stellenbeschreibungen')
                  ->onDelete('cascade');

            // stelle_id nullable machen (AVs gehören künftig zur Stellenbeschreibung)
            $table->unsignedBigInteger('stelle_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('stellen_arbeitsvorgaenge', function (Blueprint $table) {
            $table->dropForeign(['stellenbeschreibung_id']);
            $table->dropColumn('stellenbeschreibung_id');
            $table->unsignedBigInteger('stelle_id')->nullable(false)->change();
        });
    }
};
