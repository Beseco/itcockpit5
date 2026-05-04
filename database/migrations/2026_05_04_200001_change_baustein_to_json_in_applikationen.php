<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bestehende Einzelwerte in JSON-Array umwandeln
        DB::table('applikationen')
            ->whereNotNull('baustein')
            ->where('baustein', '!=', '')
            ->where('baustein', 'NOT LIKE', '[%')
            ->get(['id', 'baustein'])
            ->each(function ($row) {
                DB::table('applikationen')
                    ->where('id', $row->id)
                    ->update(['baustein' => json_encode([$row->baustein])]);
            });

        Schema::table('applikationen', function (Blueprint $table) {
            $table->text('baustein')->nullable()->change();
        });
    }

    public function down(): void
    {
        // JSON-Array zurück auf ersten Wert reduzieren
        DB::table('applikationen')
            ->whereNotNull('baustein')
            ->get(['id', 'baustein'])
            ->each(function ($row) {
                $decoded = json_decode($row->baustein, true);
                $value = is_array($decoded) ? ($decoded[0] ?? null) : $row->baustein;
                DB::table('applikationen')->where('id', $row->id)->update(['baustein' => $value]);
            });
    }
};
