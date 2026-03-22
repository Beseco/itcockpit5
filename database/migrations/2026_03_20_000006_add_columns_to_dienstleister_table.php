<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dienstleister', function (Blueprint $table) {
            // Adresse
            $table->string('strasse')->nullable()->after('firmenname');
            $table->string('plz', 20)->nullable()->after('strasse');
            $table->string('ort')->nullable()->after('plz');
            $table->string('land', 100)->nullable()->default('Deutschland')->after('ort');
            $table->string('website')->nullable()->after('land');

            // Dienstleistung
            $table->string('dienstleister_typ')->nullable()->after('website');
            $table->string('fachgebiet')->nullable()->after('dienstleister_typ');
            $table->text('leistungsbeschreibung')->nullable()->after('fachgebiet');
            $table->boolean('kritischer_dienstleister')->default(false)->after('leistungsbeschreibung');

            // DSGVO
            $table->boolean('verarbeitet_personenbezogene_daten')->default(false)->after('kritischer_dienstleister');
            $table->boolean('av_vertrag_vorhanden')->default(false)->after('verarbeitet_personenbezogene_daten');
            $table->date('av_vertrag_datum')->nullable()->after('av_vertrag_vorhanden');
            $table->string('av_bemerkungen')->nullable()->after('av_vertrag_datum');

            // Status & Bewertung
            $table->string('status', 20)->default('aktiv')->after('av_bemerkungen');
            $table->tinyInteger('bewertung_gesamt')->unsigned()->nullable()->after('status');
            $table->tinyInteger('bewertung_fachlich')->unsigned()->nullable()->after('bewertung_gesamt');
            $table->tinyInteger('bewertung_zuverlaessigkeit')->unsigned()->nullable()->after('bewertung_fachlich');
            $table->boolean('empfehlung')->default(false)->after('bewertung_zuverlaessigkeit');
            $table->text('bewertungsnotiz')->nullable()->after('empfehlung');
            $table->string('verantwortliche_stelle')->nullable()->after('bewertungsnotiz');

            // Timestamps (alte Felder aus Old-System)
            $table->timestamp('angelegt_am')->nullable()->after('verantwortliche_stelle');
            $table->timestamp('aktualisiert_am')->nullable()->after('angelegt_am');

            $table->index('status');
            $table->index('dienstleister_typ');
        });
    }

    public function down(): void
    {
        Schema::table('dienstleister', function (Blueprint $table) {
            $table->dropColumn([
                'strasse', 'plz', 'ort', 'land', 'website',
                'dienstleister_typ', 'fachgebiet', 'leistungsbeschreibung', 'kritischer_dienstleister',
                'verarbeitet_personenbezogene_daten', 'av_vertrag_vorhanden', 'av_vertrag_datum', 'av_bemerkungen',
                'status', 'bewertung_gesamt', 'bewertung_fachlich', 'bewertung_zuverlaessigkeit',
                'empfehlung', 'bewertungsnotiz', 'verantwortliche_stelle', 'angelegt_am', 'aktualisiert_am',
            ]);
        });
    }
};
