<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offboarding_records', function (Blueprint $table) {
            // Deaktivierung (am datum_ausscheiden)
            $table->string('deaktivierung_token', 64)->unique()->nullable()->after('bestaetigungstoken');
            $table->timestamp('deaktivierung_benachrichtigt_at')->nullable()->after('deaktivierung_token');
            $table->timestamp('deaktivierung_bestaetigt_at')->nullable()->after('deaktivierung_benachrichtigt_at');
            $table->string('deaktivierung_bestaetigt_von')->nullable()->after('deaktivierung_bestaetigt_at');

            // Löschung (datum_ausscheiden + 60 Tage)
            $table->string('loeschung_token', 64)->unique()->nullable()->after('deaktivierung_bestaetigt_von');
            $table->timestamp('loeschung_benachrichtigt_at')->nullable()->after('loeschung_token');
            $table->timestamp('loeschung_bestaetigt_at')->nullable()->after('loeschung_benachrichtigt_at');
            $table->string('loeschung_bestaetigt_von')->nullable()->after('loeschung_bestaetigt_at');
        });
    }

    public function down(): void
    {
        Schema::table('offboarding_records', function (Blueprint $table) {
            $table->dropColumn([
                'deaktivierung_token', 'deaktivierung_benachrichtigt_at',
                'deaktivierung_bestaetigt_at', 'deaktivierung_bestaetigt_von',
                'loeschung_token', 'loeschung_benachrichtigt_at',
                'loeschung_bestaetigt_at', 'loeschung_bestaetigt_von',
            ]);
        });
    }
};
