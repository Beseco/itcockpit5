<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ENUM um 'importiert' erweitern
        DB::statement("ALTER TABLE offboarding_records MODIFY COLUMN status
            ENUM('ausstehend','bestaetigung_angefragt','bestaetigt','abgeschlossen','importiert')
            NOT NULL DEFAULT 'ausstehend'");

        // Alle importierten Datensätze auf 'importiert' setzen
        DB::table('offboarding_records')
            ->whereNotNull('legacy_id')
            ->whereNotIn('status', ['abgeschlossen'])
            ->update(['status' => 'importiert']);
    }

    public function down(): void
    {
        DB::table('offboarding_records')
            ->where('status', 'importiert')
            ->update(['status' => 'ausstehend']);

        DB::statement("ALTER TABLE offboarding_records MODIFY COLUMN status
            ENUM('ausstehend','bestaetigung_angefragt','bestaetigt','abgeschlossen')
            NOT NULL DEFAULT 'ausstehend'");
    }
};
