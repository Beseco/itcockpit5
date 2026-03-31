<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration 0004 wurde nach dem ersten Deployment geändert (user_id → ad_user_id,
 * dienstleister_id entfernt). Falls 0004 in der alten Version lief, korrigiert
 * diese Migration den Datenbankzustand.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ad_user_id hinzufügen falls noch nicht vorhanden
        if (!Schema::hasColumn('entsorgungen', 'ad_user_id')) {
            Schema::table('entsorgungen', function (Blueprint $table) {
                $table->foreignId('ad_user_id')
                    ->nullable()
                    ->after('user')
                    ->constrained('adusers')
                    ->nullOnDelete();
            });
        }

        // user_id entfernen (war alte Version von Migration 0004)
        if (Schema::hasColumn('entsorgungen', 'user_id')) {
            Schema::table('entsorgungen', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            });
        }

        // dienstleister_id entfernen (falls Migration 0005 dies noch nicht getan hat)
        if (Schema::hasColumn('entsorgungen', 'dienstleister_id')) {
            Schema::table('entsorgungen', function (Blueprint $table) {
                $table->dropForeign(['dienstleister_id']);
                $table->dropColumn('dienstleister_id');
            });
        }

        // entsorgungsgrund hinzufügen falls noch nicht vorhanden
        if (!Schema::hasColumn('entsorgungen', 'entsorgungsgrund')) {
            Schema::table('entsorgungen', function (Blueprint $table) {
                $table->string('entsorgungsgrund')->nullable()->after('grundschutzgrund');
            });
        }
    }

    public function down(): void
    {
        // Nicht reversibel - Spalten wurden in verschiedenen Versionen unterschiedlich angelegt
    }
};
