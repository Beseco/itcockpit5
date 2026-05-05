<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abteilungen', function (Blueprint $table) {
            $table->string('kuerzel', 20)->nullable()->after('kurzzeichen');
            $table->string('ad_path', 500)->nullable()->after('kuerzel');
            $table->unsignedInteger('ad_member_count')->nullable()->after('ad_path');
            $table->timestamp('ad_member_count_updated_at')->nullable()->after('ad_member_count');
        });
    }

    public function down(): void
    {
        Schema::table('abteilungen', function (Blueprint $table) {
            $table->dropColumn(['kuerzel', 'ad_path', 'ad_member_count', 'ad_member_count_updated_at']);
        });
    }
};
