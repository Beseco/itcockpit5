<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abteilung_revision_settings', function (Blueprint $table) {
            $table->boolean('enabled')->default(false)->after('new_app_email');
            $table->unsignedTinyInteger('interval_weeks')->default(1)->after('enabled');
            $table->unsignedTinyInteger('weekday')->default(5)->after('interval_weeks');
            $table->unsignedTinyInteger('hour')->default(8)->after('weekday');
            $table->timestamp('last_sent_at')->nullable()->after('hour');
        });
    }

    public function down(): void
    {
        Schema::table('abteilung_revision_settings', function (Blueprint $table) {
            $table->dropColumn(['enabled', 'interval_weeks', 'weekday', 'hour', 'last_sent_at']);
        });
    }
};
