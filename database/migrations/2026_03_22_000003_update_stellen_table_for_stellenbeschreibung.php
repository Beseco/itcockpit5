<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stellen', function (Blueprint $table) {
            $table->unsignedBigInteger('stellenbeschreibung_id')->nullable()->after('id');
            $table->foreign('stellenbeschreibung_id')
                  ->references('id')->on('stellenbeschreibungen')
                  ->onDelete('set null');

            $table->string('haushalt_bewertung', 50)->nullable()->after('tvod_bewertung');
            $table->string('bes_gruppe', 50)->nullable()->after('haushalt_bewertung');
            $table->decimal('belegung', 5, 2)->nullable()->after('bes_gruppe');
            $table->decimal('gesamtarbeitszeit', 5, 2)->nullable()->after('belegung');
            $table->decimal('anteil_stelle', 5, 2)->nullable()->after('gesamtarbeitszeit');

            // bezeichnung nullable machen (wird künftig aus Stellenbeschreibung bezogen)
            $table->string('bezeichnung')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('stellen', function (Blueprint $table) {
            $table->dropForeign(['stellenbeschreibung_id']);
            $table->dropColumn([
                'stellenbeschreibung_id',
                'haushalt_bewertung',
                'bes_gruppe',
                'belegung',
                'gesamtarbeitszeit',
                'anteil_stelle',
            ]);
            $table->string('bezeichnung')->nullable(false)->change();
        });
    }
};
