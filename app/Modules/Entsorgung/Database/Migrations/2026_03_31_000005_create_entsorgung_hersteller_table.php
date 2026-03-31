<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entsorgung_hersteller', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        $now = now();
        DB::table('entsorgung_hersteller')->insert(array_map(fn($name) => [
            'name'       => $name,
            'created_at' => $now,
            'updated_at' => $now,
        ], [
            'Apple',
            'Canon',
            'Dell',
            'Fujitsu',
            'HP',
            'Kyocera',
            'Lenovo',
            'LG',
            'Samsung',
            'Xerox',
        ]));

        // dienstleister_id-Spalte entfernen falls vorhanden
        if (Schema::hasColumn('entsorgungen', 'dienstleister_id')) {
            Schema::table('entsorgungen', function (Blueprint $table) {
                $table->dropForeign(['dienstleister_id']);
                $table->dropColumn('dienstleister_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('entsorgung_hersteller');
    }
};
