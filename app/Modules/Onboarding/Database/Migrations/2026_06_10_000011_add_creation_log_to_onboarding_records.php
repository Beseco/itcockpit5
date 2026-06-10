<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onboarding_records', function (Blueprint $table) {
            $table->json('creation_log')->nullable()->after('ad_attributes_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('onboarding_records', function (Blueprint $table) {
            $table->dropColumn('creation_log');
        });
    }
};
