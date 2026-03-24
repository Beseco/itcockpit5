<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->string('wiederholung_typ', 20)->nullable()->after('erinnerung_gesendet');
            $table->json('wiederholung_config')->nullable()->after('wiederholung_typ');
            $table->date('wiederholung_bis')->nullable()->after('wiederholung_config');
        });
    }

    public function down(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->dropColumn(['wiederholung_typ', 'wiederholung_config', 'wiederholung_bis']);
        });
    }
};
