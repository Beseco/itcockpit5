<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adusers_settings', function (Blueprint $table) {
            $table->string('ou_deaktiviert',  500)->nullable()->after('max_inactive_days');
            $table->string('ou_elternzeit',   500)->nullable()->after('ou_deaktiviert');
            $table->string('ou_altersteilzeit', 500)->nullable()->after('ou_elternzeit');
        });
    }

    public function down(): void
    {
        Schema::table('adusers_settings', function (Blueprint $table) {
            $table->dropColumn(['ou_deaktiviert', 'ou_elternzeit', 'ou_altersteilzeit']);
        });
    }
};
