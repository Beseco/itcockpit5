<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Für jede einmalige Bezeichnung eine Stellenbeschreibung anlegen
        $bezeichnungen = DB::table('stellen')
            ->whereNotNull('bezeichnung')
            ->where('bezeichnung', '!=', '')
            ->distinct()
            ->pluck('bezeichnung');

        foreach ($bezeichnungen as $bezeichnung) {
            $sb = DB::table('stellenbeschreibungen')->insertGetId([
                'bezeichnung' => $bezeichnung,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            // Alle Stellen mit dieser Bezeichnung verknüpfen
            DB::table('stellen')
                ->where('bezeichnung', $bezeichnung)
                ->update(['stellenbeschreibung_id' => $sb]);

            // Arbeitsvorgänge dieser Stellen auf Stellenbeschreibung umhängen
            $stelleIds = DB::table('stellen')
                ->where('bezeichnung', $bezeichnung)
                ->pluck('id');

            DB::table('stellen_arbeitsvorgaenge')
                ->whereIn('stelle_id', $stelleIds)
                ->whereNull('stellenbeschreibung_id')
                ->update(['stellenbeschreibung_id' => $sb, 'stelle_id' => null]);
        }
    }

    public function down(): void
    {
        // Rückgängig: AVs wieder der ersten Stelle zuweisen und stellenbeschreibung_id leeren
        DB::table('stellen_arbeitsvorgaenge')
            ->whereNotNull('stellenbeschreibung_id')
            ->update(['stellenbeschreibung_id' => null]);

        DB::table('stellen')->update(['stellenbeschreibung_id' => null]);
        DB::table('stellenbeschreibungen')->delete();
    }
};
