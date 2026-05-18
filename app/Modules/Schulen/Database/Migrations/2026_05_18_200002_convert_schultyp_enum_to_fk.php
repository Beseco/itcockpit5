<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Standardtypen anlegen und IDs merken
        $typen = [
            ['name' => 'Realschule',      'farbe_klassen' => 'bg-blue-100 text-blue-800',   'sort_order' => 1],
            ['name' => 'Gymnasium',       'farbe_klassen' => 'bg-purple-100 text-purple-800','sort_order' => 2],
            ['name' => 'Sonstige Schule', 'farbe_klassen' => 'bg-gray-100 text-gray-800',    'sort_order' => 3],
        ];

        $idMap = []; // alte enum-Werte → neue IDs
        $slugMap = ['Realschule' => 'realschule', 'Gymnasium' => 'gymnasium', 'Sonstige Schule' => 'sonstige'];

        foreach ($typen as $t) {
            $id = DB::table('schul_typen')->insertGetId(array_merge($t, [
                'created_at' => now(), 'updated_at' => now(),
            ]));
            $enumValue = $slugMap[$t['name']];
            $idMap[$enumValue] = $id;
        }

        // Neue FK-Spalte hinzufügen
        Schema::table('schulen', function (Blueprint $table) {
            $table->foreignId('schul_typ_id')->nullable()->after('schultyp')
                  ->constrained('schul_typen')->nullOnDelete();
        });

        // Bestehende Daten migrieren
        foreach ($idMap as $enumVal => $typId) {
            DB::table('schulen')->where('schultyp', $enumVal)->update(['schul_typ_id' => $typId]);
        }

        // Alte Enum-Spalte entfernen
        Schema::table('schulen', function (Blueprint $table) {
            $table->dropColumn('schultyp');
        });

        Schema::table('schulen', function (Blueprint $table) {
            $table->index('schul_typ_id');
        });
    }

    public function down(): void
    {
        Schema::table('schulen', function (Blueprint $table) {
            $table->enum('schultyp', ['realschule', 'gymnasium', 'sonstige'])
                  ->default('sonstige')->after('kurzname');
        });

        // Rückmigration: FK → Enum (nur für die 3 Standardtypen)
        $typen = DB::table('schul_typen')->get()->keyBy('id');
        $nameToEnum = ['Realschule' => 'realschule', 'Gymnasium' => 'gymnasium', 'Sonstige Schule' => 'sonstige'];

        foreach ($typen as $typ) {
            $enumVal = $nameToEnum[$typ->name] ?? 'sonstige';
            DB::table('schulen')->where('schul_typ_id', $typ->id)->update(['schultyp' => $enumVal]);
        }

        Schema::table('schulen', function (Blueprint $table) {
            $table->dropConstrainedForeignId('schul_typ_id');
        });
    }
};
