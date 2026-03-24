<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erinnerungsmail', function (Blueprint $table) {
            $table->string('intervall_typ', 20)->default('days')->after('nextsend');
            $table->json('intervall_config')->nullable()->after('intervall_typ');
        });

        // Bestehende Daten migrieren
        $map = [60 => 'minutes', 3600 => 'hours', 86400 => 'days'];
        foreach (\App\Models\ReminderMail::all() as $r) {
            $typ = $map[$r->intervall_faktor] ?? 'days';
            $r->update([
                'intervall_typ'    => $typ,
                'intervall_config' => json_encode(['every' => (int)$r->intervall_nummer]),
            ]);
        }

        Schema::table('erinnerungsmail', function (Blueprint $table) {
            $table->dropColumn(['intervall_nummer', 'intervall_faktor']);
        });
    }

    public function down(): void
    {
        Schema::table('erinnerungsmail', function (Blueprint $table) {
            $table->integer('intervall_nummer')->default(1);
            $table->integer('intervall_faktor')->default(86400);
        });

        Schema::table('erinnerungsmail', function (Blueprint $table) {
            $table->dropColumn(['intervall_typ', 'intervall_config']);
        });
    }
};
