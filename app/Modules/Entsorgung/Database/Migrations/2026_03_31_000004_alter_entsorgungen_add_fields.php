<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entsorgungen', function (Blueprint $table) {
            $table->foreignId('dienstleister_id')
                ->nullable()
                ->after('hersteller')
                ->constrained('dienstleister')
                ->nullOnDelete();

            $table->foreignId('ad_user_id')
                ->nullable()
                ->after('user')
                ->constrained('adusers')
                ->nullOnDelete();

            $table->string('entsorgungsgrund')->nullable()->after('grundschutzgrund');
        });
    }

    public function down(): void
    {
        Schema::table('entsorgungen', function (Blueprint $table) {
            $table->dropForeign(['dienstleister_id']);
            $table->dropColumn('dienstleister_id');
            $table->dropForeign(['ad_user_id']);
            $table->dropColumn('ad_user_id');
            $table->dropColumn('entsorgungsgrund');
        });
    }
};
