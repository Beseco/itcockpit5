<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dienstleister_ansprechpartner_funktionen', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('dienstleister_ansprechpartner_funktionen')->insert([
            ['name' => 'ITler',           'sort_order' => 10, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Support',         'sort_order' => 20, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Entwickler',      'sort_order' => 30, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Vertrieb',        'sort_order' => 40, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Projektleitung',  'sort_order' => 50, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Geschäftsführung','sort_order' => 60, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Buchhaltung',     'sort_order' => 70, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sonstiges',       'sort_order' => 99, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('dienstleister_ansprechpartner_funktionen');
    }
};
