<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entsorgung_typen', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('entsorgung_gruende', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        $now = now();

        DB::table('entsorgung_typen')->insert(array_map(fn($name) => [
            'name'       => $name,
            'created_at' => $now,
            'updated_at' => $now,
        ], [
            'Notebook',
            'Desktop-PC',
            'Workstation',
            'Monitor',
            'Drucker',
            'Multifunktionsgerät',
            'Scanner',
            'Server',
            'Switch',
            'Router',
            'Firewall',
            'USV',
            'Tablet',
            'Smartphone',
            'Beamer',
            'Telefon / VoIP',
            'Thin Client',
            'Sonstiges',
        ]));

        DB::table('entsorgung_gruende')->insert(array_map(fn($name) => [
            'name'       => $name,
            'created_at' => $now,
            'updated_at' => $now,
        ], [
            'End of Life (EOL)',
            'End of Support (EOS)',
            'Keine Ersatzteile mehr verfügbar',
            'Irreparabler Defekt',
            'Hardware zu schwach',
            'Keine Sicherheitsupdates mehr verfügbar',
            'Reparaturkosten übersteigen den Zeitwert',
        ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('entsorgung_gruende');
        Schema::dropIfExists('entsorgung_typen');
    }
};
