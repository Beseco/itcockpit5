<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applikationen', function (Blueprint $table) {
            $table->foreignId('abteilung_id')
                  ->nullable()
                  ->after('sg')
                  ->constrained('abteilungen')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('applikationen', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Abteilung::class, 'abteilung_id');
            $table->dropColumn('abteilung_id');
        });
    }
};
